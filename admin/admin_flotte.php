<?php
require_once __DIR__ . '/../includes/require_admin.php';
require_once __DIR__ . '/../lang.php';
require_once __DIR__ . '/../includes/log_func.php';
require_once __DIR__ . '/../includes/fonctions_financieres.php';
require_once __DIR__ . '/../includes/mail_utils.php';

$successMessage = '';
$errorMessage = '';

// ROUTING DES ACTIONS
$action = $_POST['action'] ?? ($_GET['action'] ?? '');

// --- LIBÉRER UN AVION (en_vol = 0, reservee = 0) ---
if ($action === 'liberer' && isset($_GET['id'])) {
    $avion_id = intval($_GET['id']);
    try {
        $stmt = $pdo->prepare("UPDATE FLOTTE SET en_vol = 0, reservee = 0 WHERE id = :id");
        $stmt->execute(['id' => $avion_id]);
        $_SESSION['flash_message'] = "Avion libéré avec succès";
        header('Location: admin_flotte.php');
        exit;
    } catch (PDOException $e) {
        $errorMessage = "Erreur lors de la libération : " . htmlspecialchars($e->getMessage());
    }
}

// --- VENDRE UN AVION ---
if ($action === 'vendre' && isset($_POST['avion_id'])) {
    $logFile = dirname(__DIR__) . '/scripts/logs/admin_flotte.log';
    $avion_id = intval($_POST['avion_id']);
    logMsg('[VENTE] Début traitement vente appareil, avion_id=' . $avion_id, $logFile);
    try {
        // Récupérer le reste à payer et infos financières
        $stmtFinance = $pdo->prepare("SELECT reste_a_payer, nb_annees_credit, immat FROM FLOTTE WHERE id = :avion_id");
        $stmtFinance->execute(['avion_id' => $avion_id]);
        $rowFinance = $stmtFinance->fetch(PDO::FETCH_ASSOC);
        $reste_a_payer = $rowFinance['reste_a_payer'];
        $immat_vendue = $rowFinance['immat'];
        
        logMsg("Reste à payer récupéré pour avion_id=$avion_id : $reste_a_payer", $logFile);

        // Calculer la recette de vente
        if ($reste_a_payer > 0) {
            $recette_vente = round($reste_a_payer * 0.9, 2);
            logMsg("Mode crédit : recette_vente = 90% reste à payer = $recette_vente", $logFile);
        } else {
            $stmtPrix = $pdo->prepare("SELECT ft.cout_appareil FROM FLOTTE f JOIN FLEET_TYPE ft ON f.fleet_type = ft.id WHERE f.id = :avion_id");
            $stmtPrix->execute(['avion_id' => $avion_id]);
            $prix_neuf = $stmtPrix->fetchColumn();
            $recette_vente = round($prix_neuf * 0.7, 2);
            logMsg("Mode comptant : prix neuf = $prix_neuf, recette_vente (70%) = $recette_vente", $logFile);
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
        $callsign_vendeur = $_SESSION['callsign'] ?? '';
        $commentaire_finance = "Vente appareil $immat_vendue par $callsign_vendeur";
        mettreAJourRecettes($recette_vente, null, $immat_vendue, $callsign_vendeur, 'vente', $commentaire_finance);
        logMsg("Vente enregistrée dans finances_recettes pour immat=$immat_vendue, montant=$recette_vente", $logFile);

        $_SESSION['flash_message'] = "Avion $immat_vendue vendu avec succès pour " . number_format($recette_vente, 0, ',', ' ') . " €";
        logMsg("[VENTE] Vente terminée pour immat=$immat_vendue", $logFile);
        header('Location: admin_flotte.php');
        exit;
    } catch (PDOException $e) {
        $errorMessage = "Erreur lors de la vente : " . htmlspecialchars($e->getMessage());
        logMsg("[ERREUR] Vente échouée pour avion_id=$avion_id : " . $e->getMessage(), $logFile);
    }
}

// --- ACHAT D'UN AVION ---
if ($action === 'acheter' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $logFile = dirname(__DIR__) . '/scripts/logs/admin_flotte.log';
    logMsg('[FLEET] Début traitement achat appareil', $logFile);

    $fleet_type_id = intval($_POST['fleet_type'] ?? 0);
    $immat = strtoupper(trim($_POST['immat'] ?? ''));
    $localisation = strtoupper(trim($_POST['localisation'] ?? ''));
    $hub = strtoupper(trim($_POST['hub'] ?? ''));
    $achat_mode = $_POST['achat_mode'] ?? 'comptant';
    $nb_annees_credit = ($achat_mode === 'credit') ? intval($_POST['nb_annees_credit'] ?? 0) : 0;
    $taux_percent = ($achat_mode === 'credit') ? floatval($_POST['taux_percent'] ?? 0) : 0;

    // Validation
    if (
        $fleet_type_id === 0 || $immat === '' ||
        strlen($immat) > 10 ||
        strlen($localisation) > 4 || !preg_match('/^[A-Z0-9]{0,4}$/', $localisation) ||
        strlen($hub) > 4 || !preg_match('/^[A-Z0-9]{0,4}$/', $hub) ||
        ($achat_mode === 'credit' && ($nb_annees_credit <= 0 || $taux_percent <= 0))
    ) {
        $errorMessage = t('admin_flotte_error_champs_obligatoires');
    } else {
        try {
            // Vérifier si l'immatriculation existe déjà
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM FLOTTE WHERE immat = :immat");
            $stmt->execute(['immat' => $immat]);
            if ($stmt->fetchColumn() > 0) {
                $errorMessage = t('admin_flotte_error_immat_exist');
            } else {
                // Récupérer le prix d'achat
                $stmtPrix = $pdo->prepare("SELECT cout_appareil, type FROM FLEET_TYPE WHERE id = :fleet_type_id");
                $stmtPrix->execute(['fleet_type_id' => $fleet_type_id]);
                $ftData = $stmtPrix->fetch(PDO::FETCH_ASSOC);
                $prix_achat = $ftData['cout_appareil'];
                $categorie = $ftData['type'];

                if ($achat_mode === 'comptant') {
                    $nb_annees_credit = 0;
                    $taux_percent = 0;
                    $remboursement = 0;
                    $traite_payee_cumulee = 0;
                    $reste_a_payer = 0;
                } else {
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
                        date_achat, recettes, nb_annees_credit, taux_percent, remboursement, 
                        traite_payee_cumulee, reste_a_payer, mode_achat
                    ) VALUES (
                        :fleet_type, :immat, :localisation, :hub,
                        0, 100, NULL, NULL,
                        0, 0, 0, 1,
                        :date_achat, 0, :nb_annees_credit, :taux_percent, :remboursement, 
                        :traite_payee_cumulee, :reste_a_payer, :mode_achat
                    )
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'fleet_type' => $fleet_type_id,
                    'immat' => $immat,
                    'localisation' => $localisation ?: null,
                    'hub' => $hub ?: null,
                    'date_achat' => date('Y-m-d'),
                    'nb_annees_credit' => $nb_annees_credit,
                    'taux_percent' => $taux_percent,
                    'remboursement' => $remboursement,
                    'traite_payee_cumulee' => $traite_payee_cumulee,
                    'reste_a_payer' => $reste_a_payer,
                    'mode_achat' => $mode_achat_db
                ]);
                $avion_id = $pdo->lastInsertId();

                // Enregistrer l'achat dans finances_depenses
                $callsign_acheteur = $_SESSION['callsign'] ?? '';
                $commentaire_finance = "Achat appareil $immat par $callsign_acheteur";
                mettreAJourDepenses($prix_achat, $avion_id, $immat, $callsign_acheteur, 'achat', $commentaire_finance);
                
                $_SESSION['flash_message'] = str_replace('{immat}', htmlspecialchars($immat), t('admin_flotte_success_achat'));
                
                // Envoi mail
                $mailSubject = t('admin_flotte_mail_subject');
                $mailBody = '<h3>' . t('admin_flotte_mail_title') . '</h3><ul>' .
                    '<li><strong>' . t('admin_flotte_mail_immat') . ' :</strong> ' . htmlspecialchars($immat) . '</li>' .
                    '<li><strong>' . t('admin_flotte_mail_categorie') . ' :</strong> ' . htmlspecialchars($categorie) . '</li>' .
                    '<li><strong>' . t('admin_flotte_mail_prix_achat') . ' :</strong> ' . number_format($prix_achat, 2, ',', ' ') . ' €</li>' .
                    '<li><strong>' . t('admin_flotte_mail_mode_achat') . ' :</strong> ' . ($achat_mode === 'credit' ? t('admin_flotte_mail_mode_credit') : t('admin_flotte_mail_mode_comptant')) . '</li>' .
                    '</ul>';
                if (defined('VA_ADMIN_EMAIL') && VA_ADMIN_EMAIL) {
                    sendSummaryMail($mailSubject, $mailBody, VA_ADMIN_EMAIL);
                }

                header('Location: admin_flotte.php');
                exit;
            }
        } catch (PDOException $e) {
            $errorMessage = t('admin_flotte_error_sql') . htmlspecialchars($e->getMessage());
        }
    }
}

// Récupérer flash message
if (!empty($_SESSION['flash_message'])) {
    $successMessage = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

// Récupérer fleet types pour le formulaire
$fleetTypes = [];
try {
    $stmt = $pdo->query("SELECT id, fleet_type, type, cout_appareil FROM FLEET_TYPE ORDER BY fleet_type");
    $fleetTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Erreur chargement types : " . htmlspecialchars($e->getMessage());
}

// Récupérer la flotte active
$flotte = [];
try {
    $stmt = $pdo->query("
        SELECT f.id, f.immat, f.localisation, f.hub, f.fleet_type, f.reste_a_payer, 
               f.date_achat, f.recettes, f.nb_annees_credit, f.mode_achat, f.en_vol, f.reservee,
               f.status, f.etat, ft.type as categorie, ft.fleet_type as type_nom, ft.cout_appareil
        FROM FLOTTE f
        LEFT JOIN FLEET_TYPE ft ON f.fleet_type = ft.id
        WHERE f.actif = 1 
        ORDER BY f.immat
    ");
    $flotte = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Erreur chargement flotte : " . htmlspecialchars($e->getMessage());
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>

<main style="display:flex; flex-direction:row; align-items:flex-start; gap:40px;">
    <div style="flex:1; min-width:280px; max-width:370px;">
        <h2><?= t('admin_flotte_title_buy_section') ?></h2>

        <?php if ($successMessage): ?>
            <div style="background:#e6f9e6;color:#0b6623;padding:10px 12px;border-radius:8px;font-weight:600;font-size:0.95em;margin-bottom:10px;">
                <?= $successMessage ?>
            </div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <p style="color: red; font-weight:bold;"><?= $errorMessage ?></p>
        <?php endif; ?>

        <form method="post" action="" class="form-inscription">
            <input type="hidden" name="action" value="acheter">

            <div style="margin-bottom: 15px;">
                <label style="display: inline-flex; align-items: center; gap: 6px; margin-right: 20px; cursor: pointer;">
                    <input type="radio" name="achat_mode" value="comptant" checked style="margin: 0;">
                    <span><?= t('admin_flotte_radio_comptant') ?></span>
                </label>
                <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                    <input type="radio" name="achat_mode" value="credit" style="margin: 0;">
                    <span><?= t('admin_flotte_radio_credit') ?></span>
                </label>
            </div>

            <label>Fleet type * :</label>
            <select name="fleet_type" id="fleetTypeSelect" required class="fleet-filter-select input-250">
                <option value="">-- Sélectionner --</option>
                <?php foreach ($fleetTypes as $ft): ?>
                    <option value="<?= $ft['id'] ?>" data-prix="<?= number_format($ft['cout_appareil'], 2, ',', ' ') ?>" data-categorie="<?= htmlspecialchars($ft['type']) ?>">
                        <?= htmlspecialchars($ft['fleet_type']) ?> (<?= htmlspecialchars($ft['type']) ?>) - <?= number_format($ft['cout_appareil'], 2, ',', ' ') ?> €
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Immatriculation * :</label>
            <input type="text" name="immat" maxlength="10" required class="form-input input-250">

            <label>Localisation (ICAO) :</label>
            <input type="text" name="localisation" maxlength="4" pattern="[A-Z0-9]{0,4}" class="form-input input-250">

            <label>Hub (ICAO) :</label>
            <input type="text" name="hub" maxlength="4" pattern="[A-Z0-9]{0,4}" class="form-input input-250">

            <div id="credit-fields" class="credit-fields">
                <label><?= t('admin_flotte_label_nb_annees_credit') ?></label>
                <input type="number" name="nb_annees_credit" min="1" max="50" class="form-input input-250">
                
                <label><?= t('admin_flotte_label_taux') ?></label>
                <input type="number" name="taux_percent" min="1" step="1" max="100" class="form-input input-250">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-small"><?= t('admin_flotte_btn_signer') ?></button>
                <button type="button" class="btn btn-reset btn-small" onclick="window.location.href='admin_flotte.php';">Réinitialiser</button>
            </div>
        </form>
    </div>

    <!-- SECTION LISTE FLOTTE -->
    <aside style="min-width:900px;max-width:1800px;margin-left:40px;margin-right:auto;background:#f7fbff;border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,0.04);padding:18px 16px 12px 16px;align-self:flex-start;">
        <h3 style="margin-top:0;margin-bottom:12px;font-size:1.1em;color:#0066cc;">Flotte active (<?= count($flotte) ?> avion<?= count($flotte) > 1 ? 's' : '' ?>)</h3>
        <?php if (empty($flotte)): ?>
            <p><?= t('admin_flotte_no_aircraft') ?></p>
        <?php else: ?>
            <table class="table-skywings" style="width:100%;">
                <thead>
                    <tr>
                        <th><?= t('admin_flotte_col_immat') ?></th>
                        <th><?= t('admin_flotte_col_type') ?></th>
                        <th><?= t('admin_flotte_col_categorie') ?></th>
                        <th><?= t('admin_flotte_col_localisation') ?></th>
                        <th><?= t('admin_flotte_col_hub') ?></th>
                        <th><?= t('admin_flotte_col_reste_a_payer') ?></th>
                        <th><?= t('admin_flotte_col_recettes') ?></th>
                        <th><?= t('admin_flotte_col_mode_achat') ?></th>
                        <th><?= t('admin_flotte_col_actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($flotte as $avion): 
                            // Calculer prix de vente
                            if ($avion['reste_a_payer'] > 0) {
                                $prix_vente = round($avion['reste_a_payer'] * 0.9, 2);
                            } else {
                                $prix_vente = round($avion['cout_appareil'] * 0.7, 2);
                            }
                        ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($avion['immat']) ?></strong></td>
                                <td><?= htmlspecialchars($avion['type_nom']) ?></td>
                                <td><?= htmlspecialchars($avion['categorie']) ?></td>
                                <td><?= htmlspecialchars($avion['localisation'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($avion['hub'] ?? '-') ?></td>
                                <td><?= $avion['reste_a_payer'] > 0 ? number_format($avion['reste_a_payer'], 2, ',', ' ') . ' €' : '-' ?></td>
                                <td><?= number_format($avion['recettes'] ?? 0, 2, ',', ' ') ?> €</td>
                                          <td><?= $avion['mode_achat'] === 'credit' ? t('admin_flotte_mode_credit') : t('admin_flotte_mode_comptant') ?></td>
                                <td style="white-space: nowrap;">
                                                <a href="?action=liberer&id=<?= $avion['id'] ?>" 
                                                    title="<?= t('admin_flotte_action_liberer_title') ?>"
                                                    onclick="return confirm('Libérer l\'avion <?= htmlspecialchars($avion['immat']) ?> ?\n\nCela va :\n- Mettre en_vol à 0\n- Retirer la réservation')">
                                                    <?= t('admin_flotte_action_liberer') ?></a>
                                    &nbsp;|&nbsp;
                                                <a href="#" 
                                       onclick="return confirmVente(<?= $avion['id'] ?>, '<?= htmlspecialchars($avion['immat']) ?>', '<?= htmlspecialchars($avion['type_nom']) ?>', '<?= htmlspecialchars($avion['categorie']) ?>', <?= $avion['reste_a_payer'] ?? 0 ?>, <?= $avion['recettes'] ?? 0 ?>, <?= $prix_vente ?>, '<?= $avion['mode_achat'] ?>')"
                                                    style="color: #dc3545;"><?= t('admin_flotte_action_vendre') ?></a>
                                </td>
                            </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </aside>
</main>

<!-- Formulaire caché pour la vente -->
<form id="venteForm" method="post" action="" style="display: none;">
    <input type="hidden" name="action" value="vendre">
    <input type="hidden" name="avion_id" id="venteAvionId">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion des champs crédit
    const creditRadio = document.querySelector('input[name="achat_mode"][value="credit"]');
    const comptantRadio = document.querySelector('input[name="achat_mode"][value="comptant"]');
    const creditFields = document.getElementById('credit-fields');

    function toggleCredit() {
        if (creditRadio.checked) {
            creditFields.style.display = 'block';
        } else {
            creditFields.style.display = 'none';
        }
    }

    if (creditRadio && comptantRadio) {
        creditRadio.addEventListener('change', toggleCredit);
        comptantRadio.addEventListener('change', toggleCredit);
        toggleCredit();
    }
});

function confirmVente(id, immat, type, categorie, reste, recettes, prixVente, modeAchat) {
    const resteFormate = reste > 0 ? new Intl.NumberFormat('fr-FR', {style: 'currency', currency: 'EUR'}).format(reste) : 'Aucun';
    const recettesFormate = new Intl.NumberFormat('fr-FR', {style: 'currency', currency: 'EUR'}).format(recettes);
    const prixVenteFormate = new Intl.NumberFormat('fr-FR', {style: 'currency', currency: 'EUR'}).format(prixVente);
    const modeTexte = modeAchat === 'credit' ? 'Crédit' : 'Comptant';
    
    let explication = '';
    if (modeAchat === 'credit') {
        explication = '\n(90% du reste à payer)';
    } else {
        explication = '\n(70% du prix neuf - décote d\'occasion)';
    }
    
    const message = `Confirmer la vente de l'avion ${immat} ?\n\n` +
        `Type : ${type} (${categorie})\n` +
        `Mode d'achat : ${modeTexte}\n` +
        `Reste à payer : ${resteFormate}\n` +
        `Recettes générées : ${recettesFormate}\n` +
        `\n═══════════════════════════\n` +
        `Prix de vente : ${prixVenteFormate}${explication}`;
    
    if (confirm(message)) {
        document.getElementById('venteAvionId').value = id;
        document.getElementById('venteForm').submit();
    }
    return false;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
