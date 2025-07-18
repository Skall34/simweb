

<?php
session_start();
require __DIR__ . '/../includes/db_connect.php';
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$message = '';
$errors = [];
$selectedMission = null;

// Récupérer toutes les missions pour la liste déroulante
$missionsList = [];
try {
    $stmtAll = $pdo->query("SELECT id, libelle, majoration_mission, Active FROM MISSIONS ORDER BY libelle ASC");
    $missionsList = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Traitement sélection/modification/ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'select') {
        $id = (int)($_POST['mission_id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT id, libelle, majoration_mission, Active FROM MISSIONS WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $selectedMission = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$selectedMission) {
                $errors[] = "Mission introuvable.";
            }
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['mission_id'] ?? 0);
        $libelle = trim($_POST['libelle'] ?? '');
        $majoration = trim($_POST['majoration_mission'] ?? '');
        $active = isset($_POST['Active']) ? 1 : 0;
        if ($libelle === '') $errors[] = 'Le libellé est obligatoire.';
        if (!is_numeric($majoration) || $majoration < 0) $errors[] = 'La majoration doit être un nombre positif.';
        if (empty($errors) && $id > 0) {
            $stmt = $pdo->prepare("UPDATE MISSIONS SET libelle = :libelle, majoration_mission = :maj, Active = :active WHERE id = :id");
            $stmt->execute([
                'libelle' => $libelle,
                'maj' => $majoration,
                'active' => $active,
                'id' => $id
            ]);
            $message = "Mission modifiée avec succès.";
            // Rafraîchir la sélection
            $stmt = $pdo->prepare("SELECT id, libelle, majoration_mission, Active FROM MISSIONS WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $selectedMission = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } elseif ($action === 'create') {
        $libelle = trim($_POST['libelle_new'] ?? '');
        $majoration = trim($_POST['majoration_mission_new'] ?? '');
        $active = isset($_POST['Active_new']) ? 1 : 0;
        if ($libelle === '') $errors[] = 'Le libellé est obligatoire.';
        if (!is_numeric($majoration) || $majoration < 0) $errors[] = 'La majoration doit être un nombre positif.';
        // Vérifier unicité
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM MISSIONS WHERE libelle = :libelle");
        $stmt->execute(['libelle' => $libelle]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'Ce libellé existe déjà.';
        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO MISSIONS (libelle, majoration_mission, Active) VALUES (:libelle, :maj, :active)");
            $stmt->execute([
                'libelle' => $libelle,
                'maj' => $majoration,
                'active' => $active
            ]);
            $message = "Mission créée avec succès.";
            // Rafraîchir la liste
            $stmtAll = $pdo->query("SELECT id, libelle, majoration_mission, Active FROM MISSIONS ORDER BY libelle ASC");
            $missionsList = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>


<main>
    <h2>Administration des missions</h2>

    <?php if ($message): ?>
        <p style="color: green; font-weight:bold;"> <?= htmlspecialchars($message) ?> </p>
    <?php endif; ?>
    <?php if ($errors): ?>
        <ul style="color: red;">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div style="display:flex; align-items:flex-start; gap:1.2rem;">
        <div style="flex:1 1 380px; min-width:320px; max-width:420px;">
            <form method="post" style="margin-bottom:2rem;">
                <label for="mission_id"><strong>Sélectionner une mission :</strong></label>
                <select name="mission_id" id="mission_id" onchange="this.form.submit()">
                    <option value="">-- Choisir --</option>
                    <?php foreach ($missionsList as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= (isset($selectedMission['id']) && $selectedMission['id'] == $m['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['libelle']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="action" value="select">
            </form>

            <?php if ($selectedMission): ?>
                <form method="post" style="max-width:400px; margin-bottom:2.5rem; background:#f7fbff; padding:18px 20px; border-radius:7px; box-shadow:0 2px 8px #0001;">
                    <input type="hidden" name="mission_id" value="<?= $selectedMission['id'] ?>">
                    <label for="libelle">Libellé :</label>
                    <input type="text" name="libelle" id="libelle" value="<?= htmlspecialchars($selectedMission['libelle']) ?>" required>

                    <label for="majoration_mission">Majoration (ex : 1.00) :</label>
                    <input type="number" step="0.10" min="0" name="majoration_mission" id="majoration_mission" value="<?= htmlspecialchars($selectedMission['majoration_mission']) ?>" required>

                    <label for="Active" style="display: inline-flex; align-items: center; gap: 0.25em; cursor: pointer;">
                        <input type="checkbox" name="Active" id="Active" value="1" <?php if ((int)$selectedMission['Active'] === 1) echo 'checked'; ?> style="margin: 0;">
                        Active
                    </label>

                    <button type="submit" name="action" value="update">Modifier</button>
                </form>
            <?php endif; ?>

            <form method="post" style="max-width:400px; background:#f7fbff; padding:18px 20px; border-radius:7px; box-shadow:0 2px 8px #0001;">
                <h3>Créer une nouvelle mission</h3>
                <label for="libelle_new">Libellé :</label>
                <input type="text" name="libelle_new" id="libelle_new" required>

                <label for="majoration_mission_new">Majoration (ex : 1.00) :</label>
                <input type="number" step="0.10" min="0" name="majoration_mission_new" id="majoration_mission_new" value="1.00" required>

                <label for="Active_new" style="display: inline-flex; align-items: center; gap: 0.25em; cursor: pointer;">
                    <input type="checkbox" name="Active_new" id="Active_new" value="1" checked style="margin: 0;">
                    Active
                </label>

                <button type="submit" name="action" value="create">Créer</button>
            </form>
        </div>
        <div style="flex:1 1 320px; min-width:260px; max-width:380px;">
            <h3 style="margin-top:0;">Missions existantes</h3>
            <table style="width:100%;border-collapse:collapse;background:#f7fbff;box-shadow:0 2px 8px #0001;font-size:0.97em;">
                <thead>
                    <tr>
                        <th style="background:#e6f0fa;color:#0066cc;font-weight:bold;border:1px solid #b3c6e0;padding:7px 10px;">Libellé</th>
                        <th style="background:#e6f0fa;color:#0066cc;font-weight:bold;border:1px solid #b3c6e0;padding:7px 10px;">Majoration</th>
                        <th style="background:#e6f0fa;color:#0066cc;font-weight:bold;border:1px solid #b3c6e0;padding:7px 10px;">Active</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($missionsList as $m): ?>
                        <tr>
                            <td style="border:1px solid #b3c6e0;padding:7px 10px;"> <?= htmlspecialchars($m['libelle']) ?> </td>
                            <td style="border:1px solid #b3c6e0;padding:7px 10px;"> <?= htmlspecialchars($m['majoration_mission']) ?> </td>
                            <td style="border:1px solid #b3c6e0;padding:7px 10px; color:<?= ((int)$m['Active'] === 1) ? '#008800' : '#c00' ?>;font-weight:bold;">
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
form label { display:block; margin-top:10px; margin-bottom:3px; }
form input[type=text], form input[type=number] { width:100%; padding:5px 7px; margin-bottom:8px; border:1px solid #b3c6e0; border-radius:3px; }
form select { padding:5px 7px; border-radius:3px; border:1px solid #b3c6e0; margin-bottom:10px; }
form button { margin-top:10px; background:#0066cc; color:#fff; border:none; padding:7px 18px; border-radius:4px; cursor:pointer; font-weight:bold; }
form button:hover { background:#0055a3; }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
