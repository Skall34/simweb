<?php
require_once __DIR__ . '/../includes/require_admin.php';
require_once __DIR__ . '/../includes/log_func.php';
require_once __DIR__ . '/../includes/fonctions_financieres.php';
require_once __DIR__ . '/../includes/mail_utils.php';

$successMessage = '';
$errorMessage = '';

// ROUTING DES FORMULAIRES : 'buy' ou 'sell'
$form_action = $_POST['form_action'] ?? ($_GET['action'] ?? '');

// --- TRAITEMENT D'UNE VENTE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $form_action === 'sell' && isset($_POST['avion_id'])) {
    $logFile = dirname(__DIR__) . '/scripts/logs/admin_flotte.log';
    $avion_id = intval($_POST['avion_id']);
    logMsg('[VENTE] Début traitement vente appareil, avion_id=' . $avion_id, $logFile);
    try {
        // Récupérer le reste à payer et infos financières dans FLOTTE
        $stmtFinance = $pdo->prepare("SELECT reste_a_payer, nb_annees_credit FROM FLOTTE WHERE id = :avion_id");
        $stmtFinance->execute(['avion_id' => $avion_id]);
        $rowFinance = $stmtFinance->fetch(PDO::FETCH_ASSOC);
        $reste_a_payer = $rowFinance['reste_a_payer'];
        $nb_annees_credit = $rowFinance['nb_annees_credit'];
        logMsg("Reste à payer récupéré pour avion_id=$avion_id : $reste_a_payer", $logFile);

        // Calculer la recette de vente
        if ($reste_a_payer > 0) {
            $recette_vente = $reste_a_payer;
            logMsg("Mode crédit : recette_vente = reste à payer = $recette_vente", $logFile);
        } else {
            $stmtPrix = $pdo->prepare("SELECT ft.cout_appareil FROM FLOTTE f JOIN FLEET_TYPE ft ON f.fleet_type = ft.id WHERE f.id = :avion_id");
            $stmtPrix->execute(['avion_id' => $avion_id]);
            $prix_neuf = $stmtPrix->fetchColumn();
            $recette_vente = round($prix_neuf * 0.8, 2);
            logMsg("Mode comptant : prix neuf = $prix_neuf, recette_vente (80%) = $recette_vente", $logFile);
        }

        // Mettre à jour FLOTTE après vente
        $stmtUpdateF = $pdo->prepare("UPDATE FLOTTE SET actif = 0, status = 1, etat = 0, date_vente = :date_vente, recette_vente = :recette_vente, reste_a_payer = 0, remboursement = :remboursement, nb_annees_credit = 0 WHERE id = :id");
        $stmtUpdateF->execute([
            'date_vente' => date('Y-m-d'),
            'recette_vente' => $recette_vente,
            'remboursement' => $recette_vente,
            'id' => $avion_id
        ]);
        logMsg("FLOTTE mis à jour pour avion_id=$avion_id", $logFile);

        // Enregistrer la vente dans finances_recettes
        $stmtImmat = $pdo->prepare("SELECT immat FROM FLOTTE WHERE id = :id");
        $stmtImmat->execute(['id' => $avion_id]);
        $immat_vendue = $stmtImmat->fetchColumn();
        $callsign_vendeur = isset($_SESSION['callsign']) ? $_SESSION['callsign'] : '';
        $commentaire_finance = "Vente appareil $immat_vendue par $callsign_vendeur";
        mettreAJourRecettes($recette_vente, null, $immat_vendue, $callsign_vendeur, 'vente', $commentaire_finance);
        logMsg("Vente enregistrée dans finances_recettes pour immat=$immat_vendue, montant=$recette_vente", $logFile);

        // redirect PRG
        logMsg("[VENTE] Vente terminée pour immat=$immat_vendue", $logFile);
        header('Location: admin_flotte.php?vente=ok&immat=' . urlencode($immat_vendue));
        exit;
    } catch (PDOException $e) {
        $errorMessage = "Erreur lors de la vente : " . htmlspecialchars($e->getMessage());
        logMsg("[ERREUR] Vente échouée pour avion_id=$avion_id : " . $e->getMessage(), $logFile);
    }
}

// --- TRAITEMENT D'ACHAT (SIGNER LE BON DE COMMANDE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $form_action === 'buy') {
    $logFile = dirname(__DIR__) . '/scripts/logs/admin_flotte.log';
    logMsg('[FLEET] Début traitement achat appareil', $logFile);

    $fleet_type_id = intval($_POST['fleet_type'] ?? 0);
    // récupération des catégories/prix plus bas via query
    $immat = strtoupper(trim($_POST['immat'] ?? ''));
    $localisation = strtoupper(trim($_POST['localisation'] ?? ''));
    $hub = strtoupper(trim($_POST['hub'] ?? ''));
    $achat_mode = $_POST['achat_mode'] ?? 'comptant';
    $nb_annees_credit = ($achat_mode === 'credit') ? intval($_POST['nb_annees_credit'] ?? 0) : 0;
    $taux_percent = ($achat_mode === 'credit') ? floatval($_POST['taux_percent'] ?? 0) : 0;

    logMsg("Vérification existence immatriculation : $immat", $logFile);

    // Récupérer catégories/prix depuis FLEET_TYPE
    $fleetTypeCategories = [];
    try {
        $stmtFT = $pdo->query("SELECT id, type, cout_appareil FROM FLEET_TYPE");
        $fts = $stmtFT->fetchAll(PDO::FETCH_ASSOC);
        foreach ($fts as $f) {
            $fleetTypeCategories[$f['id']] = $f['type'];
        }
    } catch (PDOException $e) {
        // ignore
    }
    $categorie = isset($fleetTypeCategories[$fleet_type_id]) ? $fleetTypeCategories[$fleet_type_id] : '';

    // Validation
    if (
        $fleet_type_id === 0 || $categorie === '' || $immat === '' ||
        strlen($immat) > 10 ||
        strlen($localisation) > 4 || !preg_match('/^[A-Z0-9]{0,4}$/', $localisation) ||
        strlen($hub) > 4 || !preg_match('/^[A-Z0-9]{0,4}$/', $hub) ||
        ($achat_mode === 'credit' && ($nb_annees_credit <= 0 || $taux_percent <= 0))
    ) {
        $errorMessage = "Tous les champs obligatoires doivent être remplis correctement avec les formats demandés.";
    } else {
        try {
            // Vérifier si l'immatriculation existe déjà
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM FLOTTE WHERE immat = :immat");
            $stmt->execute(['immat' => $immat]);
            if ($stmt->fetchColumn() > 0) {
                logMsg("[ERREUR] Immatriculation déjà existante : $immat", $logFile);
                $errorMessage = "Un avion avec cette immatriculation existe déjà.";
            } else {
                // Récupérer le prix d'achat dans FLEET_TYPE
                $stmtPrix = $pdo->prepare("SELECT cout_appareil FROM FLEET_TYPE WHERE id = :fleet_type_id");
                $stmtPrix->execute(['fleet_type_id' => $fleet_type_id]);
                $prix_achat = $stmtPrix->fetchColumn();

                if ($achat_mode === 'comptant') {
                    $date_achat = date('Y-m-d');
                    $recettes = 0;
                    $nb_annees_credit = 0;
                    $taux_percent = 0;
                    $remboursement = 0;
                    $traite_payee_cumulee = 0;
                    $reste_a_payer = 0;
                } else {
                    $date_achat = date('Y-m-d');
                    $recettes = 0;
                    $remboursement = 0;
                    $traite_payee_cumulee = 0;
                    $reste_a_payer = $prix_achat;
                }

                $mode_achat_db = ($achat_mode === 'credit') ? 'credit' : 'comptant';
                $sql = "
                    INSERT INTO FLOTTE (
                        fleet_type, immat, localisation, hub,
                        status, etat, dernier_utilisateur, fuel_restant,
                        compteur_immo, en_vol, nb_maintenance, Actif,
                        date_achat, recettes, nb_annees_credit, taux_percent, remboursement, traite_payee_cumulee, reste_a_payer, mode_achat
                    ) VALUES (
                        :fleet_type, :immat, :localisation, :hub,
                        0, 100, NULL, NULL,
                        0, 0, 0, 1,
                        :date_achat, :recettes, :nb_annees_credit, :taux_percent, :remboursement, :traite_payee_cumulee, :reste_a_payer, :mode_achat
                    )
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'fleet_type' => $fleet_type_id,
                    'immat' => $immat,
                    'localisation' => $localisation ?: null,
                    'hub' => $hub ?: null,
                    'date_achat' => $date_achat,
                    'recettes' => $recettes,
                    'nb_annees_credit' => $nb_annees_credit,
                    'taux_percent' => $taux_percent,
                    'remboursement' => $remboursement,
                    'traite_payee_cumulee' => $traite_payee_cumulee,
                    'reste_a_payer' => $reste_a_payer,
                    'mode_achat' => $mode_achat_db
                ]);
                $avion_id = $pdo->lastInsertId();
                logMsg("Appareil inséré en base, id=$avion_id", $logFile);

                // Enregistrer l'achat dans finances_depenses
                $callsign_acheteur = isset($_SESSION['callsign']) ? $_SESSION['callsign'] : '';
                $commentaire_finance = "Achat appareil $immat par $callsign_acheteur";
                mettreAJourDepenses($prix_achat, $avion_id, $immat, $callsign_acheteur, 'achat', $commentaire_finance);
                logMsg("Achat enregistré dans finances_depenses pour immat=$immat, montant=$prix_achat", $logFile);
                $successMessage = "L'appareil $immat a été acheté avec succès.";

                // Envoi du mail récapitulatif
                $mailSubject = "Nouvel achat d'appareil";
                $mailBody = '<h3>Nouvel achat d\'appareil</h3>' .
                    '<ul>' .
                    '<li><strong>Immatriculation :</strong> ' . htmlspecialchars($immat) . '</li>' .
                    '<li><strong>Catégorie :</strong> ' . htmlspecialchars($categorie) . '</li>' .
                    '<li><strong>Fleet type :</strong> ' . htmlspecialchars($fleet_type_id) . '</li>' .
                    '<li><strong>Localisation :</strong> ' . htmlspecialchars($localisation) . '</li>' .
                    '<li><strong>Hub :</strong> ' . htmlspecialchars($hub) . '</li>' .
                    '<li><strong>Prix d\'achat :</strong> ' . number_format($prix_achat, 2, ',', ' ') . ' €</li>' .
                    '<li><strong>Mode d\'achat :</strong> ' . ($achat_mode === 'credit' ? 'Crédit' : 'Comptant') . '</li>' .
                    ($achat_mode === 'credit' ? '<li><strong>Années crédit :</strong> ' . $nb_annees_credit . '</li><li><strong>Taux :</strong> ' . $taux_percent . '%</li>' : '') .
                    '</ul>';
                $to = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : null;
                if ($to) {
                    $mailResult = sendSummaryMail($mailSubject, $mailBody, $to);
                    if ($mailResult === true) {
                        $successMessage .= '<br><span style="color:green;">Un mail de notification a été envoyé à l\'administrateur.</span>';
                    } else {
                        $successMessage .= '<br><span style="color:orange;">Mail non envoyé : ' . htmlspecialchars($mailResult) . '</span>';
                    }
                }

                // Reset POST to avoid re-processing
                $_POST = [];
            }
        } catch (PDOException $e) {
            $errorMessage = "Erreur SQL : " . htmlspecialchars($e->getMessage());
        }
    }
}

// Après traitements, inclure header/menu pour affichage
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';

// Pour l'interface Achat : récupérer fleet types
$fleetTypes = [];
$fleetTypePrices = [];
$fleetTypeCategories = [];
try {
    $stmt = $pdo->query("SELECT id, fleet_type, type, cout_appareil FROM FLEET_TYPE ORDER BY fleet_type");
    $fleetTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($fleetTypes as $ft) {
        $fleetTypePrices[$ft['id']] = $ft['cout_appareil'];
        $fleetTypeCategories[$ft['id']] = $ft['type'];
    }
    // formatted strings for client display: space thousands, comma decimals
    $fleetTypePricesFormatted = [];
    foreach ($fleetTypePrices as $k => $v) {
        $fleetTypePricesFormatted[$k] = number_format((float)$v, 2, ',', ' ');
    }
} catch (PDOException $e) {
    $errorMessage = "Erreur lors de la récupération des types de flotte : " . htmlspecialchars($e->getMessage());
}

// Pour l'interface Vente : récupérer la flotte active
try {
    $stmt = $pdo->query("SELECT id, immat, localisation, hub, fleet_type, reste_a_payer, date_achat, date_vente, recette_vente, recettes, nb_annees_credit FROM FLOTTE WHERE actif = 1 ORDER BY immat");
    $flotte_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $flotte = [];
    foreach ($flotte_raw as $avion) {
        if (!is_null($avion['reste_a_payer']) && $avion['reste_a_payer'] > 0) {
            $prix_vente_prevu = round($avion['reste_a_payer'] * 0.9, 2);
            $mode_achat = 'crédit';
        } else {
            $prix_neuf = null;
            if (!empty($avion['fleet_type'])) {
                $stmtPrix = $pdo->prepare("SELECT cout_appareil FROM FLEET_TYPE WHERE id = :ftid");
                $stmtPrix->execute(['ftid' => $avion['fleet_type']]);
                $prix_neuf = $stmtPrix->fetchColumn();
            }
            $prix_vente_prevu = $prix_neuf ? round($prix_neuf * 0.7, 2) : '';
            $mode_achat = 'comptant';
        }
        $avion['prix_vente_prevu'] = $prix_vente_prevu;
        $avion['mode_achat'] = $mode_achat;
        $flotte[] = $avion;
    }
} catch (PDOException $e) {
    $errorMessage = "Erreur lors de la récupération de la flotte : " . htmlspecialchars($e->getMessage());
    $flotte = [];
}
?>

<main style="display:flex; gap:40px; align-items:flex-start;">
    <section style="flex:1; min-width:360px; max-width:520px;">
        <h2>Acheter un appareil</h2>

        <?php if ($successMessage): ?>
            <p style="color: green; font-weight:bold;"><?= $successMessage ?></p>
        <?php elseif ($errorMessage): ?>
            <p style="color: red; font-weight:bold;"><?= $errorMessage ?></p>
        <?php endif; ?>

        <form method="post" action="" class="form-inscription" id="form-avion">
            <input type="hidden" name="form_action" value="buy">

            <div style="margin-bottom:10px;">
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="achat_mode" value="comptant" id="achat_comptant" <?= (!isset($_POST['achat_mode']) || $_POST['achat_mode'] === 'comptant') ? 'checked' : '' ?>>
                        <span>Achat comptant</span>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="achat_mode" value="credit" id="achat_credit" <?= (isset($_POST['achat_mode']) && $_POST['achat_mode'] === 'credit') ? 'checked' : '' ?>>
                        <span>Achat à crédit</span>
                    </label>
                </div>
            </div>

            <label>Fleet type * :
                <select name="fleet_type" id="fleetTypeSelect" required class="form-input">
                    <option value="">-- Choisissez un fleet type --</option>
                    <?php foreach ($fleetTypes as $ft): ?>
                        <option value="<?= htmlspecialchars($ft['id']) ?>" <?= (isset($_POST['fleet_type']) && $_POST['fleet_type'] == $ft['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ft['fleet_type']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div id="prixAchatFleetType" style="margin:8px 0 0 0; font-weight:bold; color:#0066cc;"></div>
            <div id="typeFleetType" style="margin:8px 0 0 0; font-weight:bold; color:#444;"></div>

            <label>Immatriculation * :
                <input type="text" name="immat" maxlength="10" value="<?= htmlspecialchars($_POST['immat'] ?? '') ?>" class="form-input" required>
            </label>

            <label>Localisation :
                <input type="text" name="localisation" maxlength="4" pattern="[A-Z0-9]{0,4}" title="Max 4 caractères alphanumériques en majuscule" value="<?= htmlspecialchars($_POST['localisation'] ?? '') ?>" class="form-input">
            </label>

            <label>Hub :
                <input type="text" name="hub" maxlength="4" pattern="[A-Z0-9]{0,4}" title="Max 4 caractères alphanumériques en majuscule" value="<?= htmlspecialchars($_POST['hub'] ?? '') ?>" class="form-input">
            </label>

            <div id="credit-fields" style="display: none; margin-top:10px;">
                <label>Nombre d'années de crédit * :
                    <input type="number" name="nb_annees_credit" min="1" max="50" value="<?= htmlspecialchars($_POST['nb_annees_credit'] ?? '') ?>" class="form-input">
                </label>
                <label>Taux (%) * :
                    <input type="number" name="taux_percent" min="1" step="1" max="100" value="<?= htmlspecialchars($_POST['taux_percent'] ?? '') ?>" class="form-input">
                </label>
            </div>

            <div class="form-actions" style="margin-top:12px;">
                <button type="submit" form="form-avion" class="btn-bleu">Signer le bon de commande</button>
                <button type="reset" form="form-avion" class="btn btn-reset">Réinitialiser</button>
            </div>
        </form>
    </section>

    <section style="flex:1; min-width:360px; max-width:520px;">
        <h2>Vendre un appareil</h2>

        <?php if (isset($_GET['vente']) && $_GET['vente'] === 'ok' && isset($_GET['immat'])): ?>
            <p style="color: green; font-weight:bold;">L'appareil <?= htmlspecialchars($_GET['immat']) ?> a été vendu avec succès. Le banquier va être content !</p>
        <?php endif; ?>

        <?php if (empty($flotte)): ?>
            <p>Aucun appareil actif à vendre.</p>
        <?php else: ?>
            <form id="venteForm" method="post" action="" onsubmit="return confirm('Confirmez-vous la vente de cet appareil ?');">
                <input type="hidden" name="form_action" value="sell">
                <label for="avionSelect" style="font-weight:bold;display:block;margin-bottom:7px;">
                    <span style="color:#0066cc;font-size:1.15em;vertical-align:middle;">✈️</span> Choisir un appareil à vendre :
                </label>
                <select id="avionSelect" name="avion_id" class="form-input" style="width:250px;">
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($flotte as $avion): 
                        // prepare formatted values for display
                        $reste_fmt = is_null($avion['reste_a_payer']) || $avion['reste_a_payer']==='' ? '' : number_format((float)$avion['reste_a_payer'], 2, ',', ' ');
                        $prixvente_fmt = ($avion['prix_vente_prevu'] !== '' && $avion['prix_vente_prevu'] !== null) ? number_format((float)$avion['prix_vente_prevu'], 2, ',', ' ') : '';
                        $recettevente_fmt = is_null($avion['recette_vente']) || $avion['recette_vente']==='' ? '' : number_format((float)$avion['recette_vente'], 2, ',', ' ');
                        $recettes_fmt = is_null($avion['recettes']) || $avion['recettes']==='' ? '' : number_format((float)$avion['recettes'], 2, ',', ' ');
                        $dateachat_fmt = !is_null($avion['date_achat']) && $avion['date_achat'] !== '' ? date('d-m-Y', strtotime($avion['date_achat'])) : '';
                        $datevente_fmt = !is_null($avion['date_vente']) && $avion['date_vente'] !== '' ? date('d-m-Y', strtotime($avion['date_vente'])) : '';
                    ?>
                        <option value="<?= $avion['id'] ?>"
                            data-type="<?= htmlspecialchars($avion['categorie']) ?>"
                            data-localisation="<?= htmlspecialchars($avion['localisation']) ?>"
                            data-hub="<?= htmlspecialchars($avion['hub']) ?>"
                            data-reste_raw="<?= is_null($avion['reste_a_payer']) ? '' : htmlspecialchars($avion['reste_a_payer']) ?>"
                            data-reste="<?= $reste_fmt ?>"
                            data-dateachat="<?= $dateachat_fmt ?>"
                            data-datevente="<?= $datevente_fmt ?>"
                            data-recettevente="<?= $recettevente_fmt ?>"
                            data-recettes="<?= $recettes_fmt ?>"
                            data-prixvente="<?= $prixvente_fmt ?>"
                            data-modeachat="<?= htmlspecialchars($avion['mode_achat']) ?>"
                        >
                            <?= htmlspecialchars($avion['immat']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div id="detailsAvion" style="display:none; margin-bottom:15px;">
                    <p><strong>Type :</strong> <span id="detailType"></span></p>
                    <p><strong>Localisation :</strong> <span id="detailLocalisation"></span></p>
                    <p><strong>Hub :</strong> <span id="detailHub"></span></p>
                    <p><strong>Reste à payer :</strong> <span id="detailReste"></span></p>
                    <p><strong>Date d'achat :</strong> <span id="detailDateAchat"></span></p>
                    <p><strong>Recette de vente :</strong> <span id="detailPrixVentePrevu"></span></p>
                    <p id="achatModeText" style="font-style:italic;color:#555;"></p>
                    <p><strong>Revenus :</strong> <span id="detailRecettes"></span></p>
                </div>
                <button type="submit" id="btnVendre" class="btn-bleu" style="display:none;">Vendre</button>
            </form>
        <?php endif; ?>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Achat: prix et type (formatted strings)
    var fleetTypePrices = <?= json_encode($fleetTypePricesFormatted) ?>;
    var fleetTypeCategories = <?= json_encode($fleetTypeCategories) ?>;
    var selectFleetType = document.getElementById('fleetTypeSelect');
    var prixAchatDiv = document.getElementById('prixAchatFleetType');
    var typeDiv = document.getElementById('typeFleetType');

    function updatePrixAchatAndType() {
        var val = selectFleetType.value;
        if (val && fleetTypePrices[val]) {
            prixAchatDiv.textContent = "Prix d'achat : " + fleetTypePrices[val] + ' €';
        } else {
            prixAchatDiv.textContent = '';
        }
        if (val && fleetTypeCategories[val]) {
            typeDiv.textContent = 'Catégorie : ' + fleetTypeCategories[val];
        } else {
            typeDiv.textContent = '';
        }
    }
    if (selectFleetType) {
        selectFleetType.addEventListener('change', updatePrixAchatAndType);
        updatePrixAchatAndType();
    }

    // Achat: affichage champs crédit
    function toggleCreditFields() {
        var creditFields = document.getElementById('credit-fields');
        var achatCredit = document.getElementById('achat_credit');
        if (!creditFields || !achatCredit) return;
        creditFields.style.display = achatCredit.checked ? 'block' : 'none';
    }
    var achatComptant = document.getElementById('achat_comptant');
    var achatCredit = document.getElementById('achat_credit');
    if (achatComptant && achatCredit) {
        achatComptant.addEventListener('change', toggleCreditFields);
        achatCredit.addEventListener('change', toggleCreditFields);
        toggleCreditFields();
    }

    // Vente: détails
    var select = document.getElementById('avionSelect');
    var detailsDiv = document.getElementById('detailsAvion');
    var btnVendre = document.getElementById('btnVendre');
    if (select) {
        select.addEventListener('change', function() {
            var selected = select.options[select.selectedIndex];
            if (select.value) {
                document.getElementById('detailType').textContent = selected.getAttribute('data-type');
                document.getElementById('detailLocalisation').textContent = selected.getAttribute('data-localisation');
                document.getElementById('detailHub').textContent = selected.getAttribute('data-hub');
                document.getElementById('detailReste').textContent = selected.getAttribute('data-reste') ? selected.getAttribute('data-reste') + ' €' : '-';
                document.getElementById('detailDateAchat').textContent = selected.getAttribute('data-dateachat') || '-';
                document.getElementById('detailPrixVentePrevu').textContent = selected.getAttribute('data-prixvente') ? selected.getAttribute('data-prixvente') + ' €' : '-';
                var modeAchat = selected.getAttribute('data-modeachat');
                var achatModeText = document.getElementById('achatModeText');
                if (modeAchat === 'crédit') {
                    achatModeText.textContent = "Cet avion a été acheté à crédit.";
                } else if (modeAchat === 'comptant') {
                    achatModeText.textContent = "Cet avion a été acheté comptant.";
                } else {
                    achatModeText.textContent = "";
                }
                document.getElementById('detailRecettes').textContent = selected.getAttribute('data-recettes') ? selected.getAttribute('data-recettes') + ' €' : '-';
                detailsDiv.style.display = 'block';
                btnVendre.style.display = 'inline-block';
            } else {
                detailsDiv.style.display = 'none';
                btnVendre.style.display = 'none';
            }
        });
    }

    // Reset vente form behavior (if any reset buttons added)
    var resetButtons = document.querySelectorAll('button[type="reset"][form="form-avion"]');
    resetButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (prixAchatDiv) prixAchatDiv.textContent = '';
            if (typeDiv) typeDiv.textContent = '';
            if (achatComptant) achatComptant.checked = true;
            toggleCreditFields();
        });
    });
});
</script>

<style>
/* lightweight styles reused from original pages; prefer centralizing in css/styles.css later */
.radio-group { display:flex; gap:20px; }
.radio-label { display:flex; align-items:center; gap:8px; font-weight:bold; color:#0066cc; }
.form-inscription { max-width: 460px; display:flex; flex-direction:column; }
.form-inscription label { margin-top:10px; font-weight:bold; }
.form-input { padding:8px; margin-top:5px; border:1px solid #ccc; border-radius:4px; font-size:1rem; width:100%; box-sizing:border-box; }
.btn { padding:8px 16px; border-radius:6px; border:none; cursor:pointer; }
.btn-bleu { background:#0066cc; color:#fff; font-weight:700; }
.btn-reset { background:#999; color:#fff; }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
