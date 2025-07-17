<?php

session_start();
require __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
require_once __DIR__ . '/../includes/mail_utils.php';

$successMessage = '';
$errorMessage = '';

// Récupérer les fleet types pour la liste déroulante et leurs prix
$fleetTypes = [];
$fleetTypePrices = [];
try {
    $stmt = $pdo->query("SELECT id, fleet_type, cout_appareil FROM FLEET_TYPE ORDER BY fleet_type");
    $fleetTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($fleetTypes as $ft) {
        $fleetTypePrices[$ft['id']] = $ft['cout_appareil'];
    }
} catch (PDOException $e) {
    $errorMessage = "Erreur lors de la récupération des types de flotte : " . htmlspecialchars($e->getMessage());
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fleet_type_id = intval($_POST['fleet_type'] ?? 0);
    $type = trim($_POST['type'] ?? '');
    $immat = strtoupper(trim($_POST['immat'] ?? ''));
    $localisation = strtoupper(trim($_POST['localisation'] ?? ''));
    $hub = strtoupper(trim($_POST['hub'] ?? ''));
    $achat_mode = $_POST['achat_mode'] ?? 'comptant';
    $nb_annees_credit = ($achat_mode === 'credit') ? intval($_POST['nb_annees_credit'] ?? 0) : 0;
    $taux_percent = ($achat_mode === 'credit') ? floatval($_POST['taux_percent'] ?? 0) : 0;

    // Validation des champs
    if (
        $fleet_type_id === 0 || $type === '' || $immat === '' ||
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
                $errorMessage = "Un avion avec cette immatriculation existe déjà.";
            } else {
                // Construction de la requête
                $sql = "
                    INSERT INTO FLOTTE (
                        fleet_type, type, immat, localisation, hub, 
                        status, etat, dernier_utilisateur, fuel_restant, 
                        compteur_immo, en_vol, nb_maintenance, Actif
                    ) VALUES (
                        :fleet_type, :type, :immat, :localisation, :hub,
                        0, 0, NULL, NULL,
                        0, 0, 0, 1
                    )
                ";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'fleet_type' => $fleet_type_id,
                    'type' => $type,
                    'immat' => $immat,
                    'localisation' => $localisation ?: null,
                    'hub' => $hub ?: null
                ]);
                // Récupérer l'id du nouvel avion
                $avion_id = $pdo->lastInsertId();

                // Récupérer le prix d'achat dans FLEET_TYPE
                $stmtPrix = $pdo->prepare("SELECT cout_appareil FROM FLEET_TYPE WHERE id = :fleet_type_id");
                $stmtPrix->execute(['fleet_type_id' => $fleet_type_id]);
                $prix_achat = $stmtPrix->fetchColumn();

                // Insérer la ligne dans FINANCES selon le mode d'achat
                if ($achat_mode === 'comptant') {
                    $sqlFinances = "INSERT INTO FINANCES (
                        avion_id, date_achat, recettes, nb_annees_credit, taux_percent, remboursement, traite_payee_cumulee, reste_a_payer
                    ) VALUES (
                        :avion_id, :date_achat, 0, 0, 0, 0, 0, 0
                    )";
                    $paramsFinances = [
                        'avion_id' => $avion_id,
                        'date_achat' => date('Y-m-d')
                    ];
                } else {
                    $sqlFinances = "INSERT INTO FINANCES (
                        avion_id, date_achat, recettes, nb_annees_credit, taux_percent, remboursement, traite_payee_cumulee, reste_a_payer
                    ) VALUES (
                        :avion_id, :date_achat, 0, :nb_annees_credit, :taux_percent, 0, 0, :reste_a_payer
                    )";
                    $paramsFinances = [
                        'avion_id' => $avion_id,
                        'date_achat' => date('Y-m-d'),
                        'nb_annees_credit' => $nb_annees_credit,
                        'taux_percent' => $taux_percent,
                        'reste_a_payer' => $prix_achat
                    ];
                }
                $stmtFinances = $pdo->prepare($sqlFinances);
                $stmtFinances->execute($paramsFinances);

                // Mettre à jour le champ cout_avions dans BALANCE_COMMERCIALE
                $stmtGetCout = $pdo->query("SELECT cout_avions FROM BALANCE_COMMERCIALE WHERE id = 1");
                $cout_avions = $stmtGetCout->fetchColumn();
                if ($cout_avions === false || $cout_avions === null) {
                    $cout_avions = 0;
                }
                $nouveau_cout = $cout_avions + $prix_achat;
                $stmtUpdateCout = $pdo->prepare("UPDATE BALANCE_COMMERCIALE SET cout_avions = :nouveau_cout WHERE id = 1");
                $stmtUpdateCout->execute(['nouveau_cout' => $nouveau_cout]);
             // Recalculer et vérifier la balance commerciale après la vente
                $sqlGetBalance = "SELECT balance_actuelle FROM BALANCE_COMMERCIALE";
                $stmtBalance = $pdo->query($sqlGetBalance);
                $balance = $stmtBalance->fetchColumn();
                $balance_actuelle = $balance + $nouveau_cout;
                $sqlUpdateBalance = "UPDATE BALANCE_COMMERCIALE SET balance_actuelle = :balance_actuelle";
                $stmtUpdateBalance = $pdo->prepare($sqlUpdateBalance);
                $stmtUpdateBalance->execute(['balance_actuelle' => $balance_actuelle]);
                $successMessage = "L'appareil $immat a été acheté avec succès. Félicitations !!";

                // Envoi du mail récapitulatif via mail_utils.php
                $mailSubject = "Nouvel achat d'appareil";
                $mailBody = '<h3>Nouvel achat d\'appareil</h3>' .
                    '<ul>' .
                    '<li><strong>Immatriculation :</strong> ' . htmlspecialchars($immat) . '</li>' .
                    '<li><strong>Type :</strong> ' . htmlspecialchars($type) . '</li>' .
                    '<li><strong>Fleet type :</strong> ' . htmlspecialchars($fleetTypes[array_search($fleet_type_id, array_column($fleetTypes, 'id'))]['fleet_type'] ?? $fleet_type_id) . '</li>' .
                    '<li><strong>Localisation :</strong> ' . htmlspecialchars($localisation) . '</li>' .
                    '<li><strong>Hub :</strong> ' . htmlspecialchars($hub) . '</li>' .
                    '<li><strong>Prix d\'achat :</strong> ' . number_format($prix_achat, 2) . ' €</li>' .
                    '<li><strong>Mode d\'achat :</strong> ' . ($achat_mode === 'credit' ? 'Crédit' : 'Comptant') . '</li>' .
                    ($achat_mode === 'credit' ? '<li><strong>Années crédit :</strong> ' . $nb_annees_credit . '</li><li><strong>Taux :</strong> ' . $taux_percent . '%</li>' : '') .
                    '</ul>';
                $to = ADMIN_EMAIL;
                $mailResult = sendSummaryMail($mailSubject, $mailBody, $to);
                if ($mailResult === true) {
                    $successMessage .= '<br><span style="color:green;">Un mail de notification a été envoyé à l\'administrateur.</span>';
                } else {
                    $successMessage .= '<br><span style="color:orange;">Mail non envoyé : ' . htmlspecialchars($mailResult) . '</span>';
                }

                // Réinitialiser les valeurs du formulaire
                $_POST = [];
            }
        } catch (PDOException $e) {
            $errorMessage = "Erreur SQL : " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<main>
    <h2>Acheter un appareil</h2>

    <?php if ($successMessage): ?>
        <p style="color: green; font-weight:bold;"><?= $successMessage ?></p>
    <?php elseif ($errorMessage): ?>
        <p style="color: red; font-weight:bold;"><?= $errorMessage ?></p>
    <?php endif; ?>

    <form method="post" action="" class="form-inscription" id="form-avion">

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
            <select name="fleet_type" id="fleetTypeSelect" required>
                <option value="">-- Choisissez un fleet type --</option>
                <?php foreach ($fleetTypes as $ft): ?>
                    <option value="<?= htmlspecialchars($ft['id']) ?>" <?= (isset($_POST['fleet_type']) && $_POST['fleet_type'] == $ft['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ft['fleet_type']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <div id="prixAchatFleetType" style="margin:8px 0 0 0; font-weight:bold; color:#0066cc;"></div>

        <label>Type * :
            <select name="type" required>
                <option value="">-- Choisissez un type --</option>
                <?php 
                $types = ['Helico', 'Liner', 'Bimoteur', 'Monomoteur'];
                foreach ($types as $t): ?>
                    <option value="<?= $t ?>" <?= (isset($_POST['type']) && $_POST['type'] === $t) ? 'selected' : '' ?>><?= $t ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Immatriculation * :
            <input type="text" name="immat" maxlength="10" value="<?= htmlspecialchars($_POST['immat'] ?? '') ?>" required>
        </label>

        <label>Localisation :
            <input type="text" name="localisation" maxlength="4" pattern="[A-Z0-9]{0,4}" title="Max 4 caractères alphanumériques en majuscule" value="<?= htmlspecialchars($_POST['localisation'] ?? '') ?>">
        </label>

        <label>Hub :
            <input type="text" name="hub" maxlength="4" pattern="[A-Z0-9]{0,4}" title="Max 4 caractères alphanumériques en majuscule" value="<?= htmlspecialchars($_POST['hub'] ?? '') ?>">
        </label>

        <div id="credit-fields" style="display: none; margin-top:10px;">
            <label>Nombre d'années de crédit * :
                <input type="number" name="nb_annees_credit" min="1" max="50" value="<?= htmlspecialchars($_POST['nb_annees_credit'] ?? '') ?>">
            </label>
            <label>Taux (%) * :
                <input type="number" name="taux_percent" min="1" step="1" max="100" value="<?= htmlspecialchars($_POST['taux_percent'] ?? '') ?>">
            </label>
        </div>

    </form>

    <div class="form-buttons">
        <button type="submit" form="form-avion">Signer le bon de commande</button>
        <button type="reset" form="form-avion">Réinitialiser</button>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prix d'achat des fleet types
    var fleetTypePrices = <?php echo json_encode($fleetTypePrices); ?>;
    var selectFleetType = document.getElementById('fleetTypeSelect');
    var prixAchatDiv = document.getElementById('prixAchatFleetType');

    function updatePrixAchat() {
        var val = selectFleetType.value;
        if (val && fleetTypePrices[val]) {
            prixAchatDiv.textContent = 'Prix d\'achat : ' + fleetTypePrices[val] + ' €';
        } else {
            prixAchatDiv.textContent = '';
        }
    }
    selectFleetType.addEventListener('change', updatePrixAchat);
    updatePrixAchat();

    // Affichage des champs crédit
    function toggleCreditFields() {
        var creditFields = document.getElementById('credit-fields');
        var achatCredit = document.getElementById('achat_credit');
        creditFields.style.display = achatCredit.checked ? 'block' : 'none';
    }
    document.getElementById('achat_comptant').addEventListener('change', toggleCreditFields);
    document.getElementById('achat_credit').addEventListener('change', toggleCreditFields);
    toggleCreditFields();
});
</script>

<style>
main {
    padding-left: 30px;
}

/* Boutons radio stylés et alignés */
.radio-group {
    display: flex;
    flex-direction: row;
    gap: 30px;
    align-items: center;
    margin-bottom: 10px;
}
.radio-label {
    display: flex;
    align-items: center;
    gap: 7px;
    font-weight: bold;
    font-size: 1.05em;
    color: #0066cc;
}
.radio-label input[type="radio"] {
    accent-color: #0066cc;
    width: 18px;
    height: 18px;
    margin: 0;
}

.form-inscription {
    max-width: 400px;
    display: flex;
    flex-direction: column;
}

.form-inscription label {
    margin-top: 10px;
    font-weight: bold;
}

.form-inscription input,
.form-inscription select {
    padding: 8px;
    margin-top: 5px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 1rem;
    width: 100%;
    box-sizing: border-box;
    text-transform: uppercase;
}

.form-buttons {
    margin-top: 15px;
    display: flex;
    gap: 10px;
    max-width: 400px;
}

.form-buttons button {
    padding: 8px 16px;
    font-weight: bold;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    font-size: 1rem;
    width: auto;
    text-transform: none;
}

.form-buttons button[type="submit"] {
    background-color: #0066cc;
    color: white;
    transition: background-color 0.3s ease;
}

.form-buttons button[type="submit"]:hover {
    background-color: #005bb5;
}

.form-buttons button[type="reset"] {
    background-color: #999;
    color: white;
    transition: background-color 0.3s ease;
}

.form-buttons button[type="reset"]:hover {
    background-color: #777;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
