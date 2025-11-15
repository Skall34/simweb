

<?php
require_once __DIR__ . '/../includes/require_admin.php';

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
                $errors[] = t('admin_missions_error_not_found');
            }
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['mission_id'] ?? 0);
        $libelle = trim($_POST['libelle'] ?? '');
        $majoration = trim($_POST['majoration_mission'] ?? '');
        $active = isset($_POST['Active']) ? 1 : 0;
        if ($libelle === '') $errors[] = t('admin_missions_error_libelle');
        if (!is_numeric($majoration) || $majoration < 0) $errors[] = t('admin_missions_error_majoration');
        if (empty($errors) && $id > 0) {
            $stmt = $pdo->prepare("UPDATE MISSIONS SET libelle = :libelle, majoration_mission = :maj, Active = :active WHERE id = :id");
            $stmt->execute([
                'libelle' => $libelle,
                'maj' => $majoration,
                'active' => $active,
                'id' => $id
            ]);
            $message = t('admin_missions_success_update');
            // Rafraîchir la sélection
            $stmt = $pdo->prepare("SELECT id, libelle, majoration_mission, Active FROM MISSIONS WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $selectedMission = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } elseif ($action === 'create') {
        $libelle = trim($_POST['libelle_new'] ?? '');
        $majoration = trim($_POST['majoration_mission_new'] ?? '');
        $active = isset($_POST['Active_new']) ? 1 : 0;
        if ($libelle === '') $errors[] = t('admin_missions_error_libelle');
        if (!is_numeric($majoration) || $majoration < 0) $errors[] = t('admin_missions_error_majoration');
        // Vérifier unicité
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM MISSIONS WHERE libelle = :libelle");
        $stmt->execute(['libelle' => $libelle]);
        if ($stmt->fetchColumn() > 0) $errors[] = t('admin_missions_error_exists');
        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO MISSIONS (libelle, majoration_mission, Active) VALUES (:libelle, :maj, :active)");
            $stmt->execute([
                'libelle' => $libelle,
                'maj' => $majoration,
                'active' => $active
            ]);
            $message = t('admin_missions_success_create');
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
    <h2><?= t('admin_missions_title') ?></h2>

    <?php if ($message): ?>
        <p class="message-success"> <?= htmlspecialchars($message) ?> </p>
    <?php endif; ?>
    <?php if ($errors): ?>
        <ul class="message-error">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div class="admin-missions-layout">
        <div class="admin-missions-left">
            <form method="post" class="admin-missions-form-select">
                <label for="mission_id"><strong><?= t('admin_missions_select_label') ?></strong></label>
                <select name="mission_id" id="mission_id" onchange="this.form.submit()">
                    <option value=""><?= t('admin_missions_select_default') ?></option>
                    <?php foreach ($missionsList as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= (isset($selectedMission['id']) && $selectedMission['id'] == $m['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['libelle']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="action" value="select">
            </form>

            <?php if ($selectedMission): ?>
                <form method="post" class="admin-missions-form-edit">
                    <input type="hidden" name="mission_id" value="<?= $selectedMission['id'] ?>">
                    <label for="libelle"><?= t('admin_missions_label_libelle') ?></label>
                    <input type="text" name="libelle" id="libelle" value="<?= htmlspecialchars($selectedMission['libelle']) ?>" required>

                    <label for="majoration_mission"><?= t('admin_missions_label_majoration') ?></label>
                    <input type="number" step="0.10" min="0" name="majoration_mission" id="majoration_mission" value="<?= htmlspecialchars($selectedMission['majoration_mission']) ?>" required>

                    <label for="Active" class="admin-missions-checkbox-label">
                        <input type="checkbox" name="Active" id="Active" value="1" <?php if ((int)$selectedMission['Active'] === 1) echo 'checked'; ?>>
                        <?= t('admin_missions_label_active') ?>
                    </label>

                    <button type="submit" name="action" value="update"><?= t('admin_missions_btn_update') ?></button>
                </form>
            <?php endif; ?>

            <form method="post" class="admin-missions-form-create">
                <h3><?= t('admin_missions_create_title') ?></h3>
                <label for="libelle_new"><?= t('admin_missions_label_libelle') ?></label>
                <input type="text" name="libelle_new" id="libelle_new" required>

                <label for="majoration_mission_new"><?= t('admin_missions_label_majoration') ?></label>
                <input type="number" step="0.10" min="0" name="majoration_mission_new" id="majoration_mission_new" value="1.00" required>

                <label for="Active_new" class="admin-missions-checkbox-label">
                    <input type="checkbox" name="Active_new" id="Active_new" value="1" checked>
                    <?= t('admin_missions_label_active') ?>
                </label>

                <button type="submit" name="action" value="create"><?= t('admin_missions_btn_create') ?></button>
            </form>
        </div>
        <div class="admin-missions-right">
            <h3 class="admin-missions-table-title"><?= t('admin_missions_table_title') ?></h3>
            <table class="admin-missions-table">
                <thead>
                    <tr>
                        <th><?= t('admin_missions_col_libelle') ?></th>
                        <th><?= t('admin_missions_col_majoration') ?></th>
                        <th><?= t('admin_missions_col_active') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($missionsList as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['libelle']) ?></td>
                            <td><?= htmlspecialchars($m['majoration_mission']) ?></td>
                            <td class="<?= ((int)$m['Active'] !== 0) ? 'admin-missions-active-yes' : 'admin-missions-active-no' ?>">
                                <?= ((int)$m['Active'] !== 0) ? t('admin_missions_active_yes') : t('admin_missions_active_no') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
