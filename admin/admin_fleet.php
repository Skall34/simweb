<?php
session_start();
require __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';

$successMessage = '';
$errorMessage = '';

// Récupérer les fleet types pour la liste déroulante
$fleetTypes = [];
try {
    $stmt = $pdo->query("SELECT id, fleet_type FROM FLEET_TYPE ORDER BY fleet_type");
    $fleetTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    // Validation des champs
    if (
        $fleet_type_id === 0 || $type === '' || $immat === '' ||
        strlen($immat) > 10 ||
        strlen($localisation) > 4 || !preg_match('/^[A-Z0-9]{0,4}$/', $localisation) ||
        strlen($hub) > 4 || !preg_match('/^[A-Z0-9]{0,4}$/', $hub)
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
                $successMessage = "Avion ajouté avec succès.";
            }
        } catch (PDOException $e) {
            $errorMessage = "Erreur SQL : " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<main>
    <h2>Ajouter un avion</h2>

    <?php if ($successMessage): ?>
        <p style="color: green;"><?= $successMessage ?></p>
    <?php elseif ($errorMessage): ?>
        <p style="color: red;"><?= $errorMessage ?></p>
    <?php endif; ?>

    <form method="post" action="" class="form-inscription" id="form-avion">

        <label>Fleet type * :
            <select name="fleet_type" required>
                <option value="">-- Choisissez un fleet type --</option>
                <?php foreach ($fleetTypes as $ft): ?>
                    <option value="<?= htmlspecialchars($ft['id']) ?>" <?= (isset($_POST['fleet_type']) && $_POST['fleet_type'] == $ft['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ft['fleet_type']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

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

    </form>

    <div class="form-buttons">
        <button type="submit" form="form-avion">Ajouter</button>
        <button type="reset" form="form-avion">Réinitialiser</button>
    </div>
</main>

<style>
main {
    padding-left: 30px;
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
