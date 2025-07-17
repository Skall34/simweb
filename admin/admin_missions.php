
<?php
session_start();
require __DIR__ . '/../includes/db_connect.php';
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$missionsList = [];
try {
    $stmtAll = $pdo->query("SELECT libelle, majoration_mission, Active FROM MISSIONS ORDER BY libelle ASC");
    $missionsList = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // On ignore l'erreur ici pour ne pas casser la page
}

$message = '';
$errors = [];
$mission = null;
$mode = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recherche
    if (isset($_POST['search'])) {
        $searchLibelle = trim($_POST['libelle_search'] ?? '');
        if ($searchLibelle === '') {
            $errors[] = 'Merci de saisir un libellé à rechercher.';
        } else {
            $stmt = $pdo->prepare("SELECT libelle, majoration_mission, Active FROM MISSIONS WHERE libelle = :libelle LIMIT 1");
            $stmt->execute(['libelle' => $searchLibelle]);
            $missionFound = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($missionFound) {
                $mission = [
                    'libelle' => isset($missionFound['libelle']) ? $missionFound['libelle'] : '',
                    'majoration_mission' => isset($missionFound['majoration_mission']) ? $missionFound['majoration_mission'] : '',
                    'Active' => isset($missionFound['Active']) ? (int)$missionFound['Active'] : 0
                ];
                $mode = 'edit';
            } else {
                $mission = [
                    'libelle' => $searchLibelle,
                    'majoration_mission' => '1.00',
                    'Active' => 1
                ];
                $mode = 'create';
                $message = "Mission '$searchLibelle' non trouvée. Vous pouvez la créer ci-dessous.";
            }
        }
    }
    // Création ou modification
    if (isset($_POST['save'])) {
        $libelle = trim($_POST['libelle'] ?? '');
        $majoration = trim($_POST['majoration_mission'] ?? '');
        $active = isset($_POST['Active']) ? 1 : 0;
        if ($libelle === '') {
            $errors[] = 'Le libellé est obligatoire.';
        }
        if (!is_numeric($majoration) || $majoration < 0) {
            $errors[] = 'La majoration doit être un nombre positif.';
        }
        if (empty($errors)) {
            $stmtCheck = $pdo->prepare("SELECT id FROM MISSIONS WHERE libelle = :libelle LIMIT 1");
            $stmtCheck->execute(['libelle' => $libelle]);
            $exists = $stmtCheck->fetchColumn();
            if ($exists) {
                $stmtUpdate = $pdo->prepare("UPDATE MISSIONS SET majoration_mission = :maj, Active = :active WHERE libelle = :libelle");
                $stmtUpdate->execute([
                    'maj' => $majoration,
                    'active' => $active,
                    'libelle' => $libelle
                ]);
                $message = "Mission '$libelle' mise à jour avec succès.";
            } else {
                $stmtInsert = $pdo->prepare("INSERT INTO MISSIONS (libelle, majoration_mission, Active) VALUES (:libelle, :maj, :active)");
                $stmtInsert->execute([
                    'libelle' => $libelle,
                    'maj' => $majoration,
                    'active' => $active
                ]);
                $message = "Mission '$libelle' créée avec succès.";
            }
            // Après création/modif, retour à l'état initial (formulaire de recherche uniquement)
            $mission = null;
            $mode = null;
        } else {
            $mission = [
                'libelle' => $libelle,
                'majoration_mission' => $majoration,
                'Active' => $active
            ];
            $mode = isset($exists) && $exists ? 'edit' : 'create';
        }
    }
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>


<main>
    <h2>Administration des missions</h2>

    <?php if ($message): ?>
        <p style="color: green;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if ($errors): ?>
        <ul style="color: red;">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div style="display: flex; align-items: flex-start; gap: 2.2rem; margin-bottom: 2rem;">
        <div>
            <!-- Formulaire de recherche toujours visible -->
            <form method="post" style="margin-bottom: 2rem;" class="form-inscription">
                <label for="libelle_search">Rechercher une mission par son nom :</label>
                <input type="text" name="libelle_search" id="libelle_search" value="<?= isset($_POST['libelle_search']) ? htmlspecialchars($_POST['libelle_search']) : '' ?>" required>
                <button type="submit" name="search" class="btn">Rechercher</button>
            </form>

            <?php if ($mode === 'edit' || $mode === 'create'): ?>
                <form method="post" style="max-width: 400px;" class="form-inscription">
                    <label for="libelle">Libellé :</label>
                    <input type="text" name="libelle" id="libelle" value="<?= htmlspecialchars($mission['libelle'] ?? '') ?>" required>

                    <label for="majoration_mission">Majoration (ex : 1.00) :</label>
                    <input type="number" step="0.10" min="0" name="majoration_mission" id="majoration_mission" value="<?= htmlspecialchars($mission['majoration_mission'] ?? '1.00') ?>" required>

                    <label for="Active" style="display: inline-flex; align-items: center; gap: 0.25em; cursor: pointer;">
                        <input type="checkbox" name="Active" id="Active" value="1" <?php if (isset($mission['Active']) && (int)$mission['Active'] === 1) echo 'checked'; ?> style="margin: 0;">
                        Active
                    </label>

                    <?php if ($mode === 'edit'): ?>
                        <button type="submit" name="save">Modifier</button>
                    <?php elseif ($mode === 'create'): ?>
                        <button type="submit" name="save">Créer</button>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
        <div class="missions-table-wrapper">
            <h3 style="margin-top:0;">Missions existantes</h3>
            <table class="missions-table">
                <thead>
                    <tr>
                        <th>Libellé</th>
                        <th>Majoration</th>
                        <th>Active</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($missionsList as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['libelle']) ?></td>
                            <td><?= htmlspecialchars($m['majoration_mission']) ?></td>
                            <td class="<?= ((int)$m['Active'] === 1) ? 'active-yes' : 'active-no' ?>">
                                <?= ((int)$m['Active'] === 1) ? 'Oui' : 'Non' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>




<style>
.missions-table-wrapper {
    float: right;
    width: 370px;
    margin-left: 2rem;
    margin-bottom: 2rem;
}
.missions-table {
    width: 100%;
    border-collapse: collapse;
    background: #f7fbff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    font-size: 0.97em;
}
.missions-table th, .missions-table td {
    border: 1px solid #b3c6e0;
    padding: 7px 10px;
    text-align: center;
}
.missions-table th {
    background: #e6f0fa;
    color: #0066cc;
    font-weight: bold;
}
.missions-table td.active-yes {
    color: #008800;
    font-weight: bold;
}
.missions-table td.active-no {
    color: #c00;
    font-weight: bold;
}
</style>

<!-- Le tableau est déjà affiché dans le flexbox principal, on retire ce doublon en bas -->

<?php include __DIR__ . '/../includes/footer.php'; ?>
