<?php
require_once __DIR__ . '/../includes/require_admin.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/menu_logged.php';

// Gestion des ajouts/edits/suppressions
$action = $_POST['action'] ?? null;
$message = '';

if ($action === 'add') {
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $taux_horaire = floatval(str_replace(',', '.', $_POST['taux_horaire'] ?? '0'));
    if ($nom && $description && $taux_horaire > 0) {
        $stmt = $pdo->prepare('INSERT INTO GRADES (nom, description, taux_horaire) VALUES (?, ?, ?)');
        $stmt->execute([$nom, $description, $taux_horaire]);
        $message = t('admin_grades_success_add');
    } else {
        $message = t('admin_grades_error_required');
    }
} elseif ($action === 'edit') {
    $id = intval($_POST['id'] ?? 0);
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $taux_horaire = floatval(str_replace(',', '.', $_POST['taux_horaire'] ?? '0'));
    if ($id && $nom && $description && $taux_horaire > 0) {
        $stmt = $pdo->prepare('UPDATE GRADES SET nom=?, description=?, taux_horaire=? WHERE id=?');
        $stmt->execute([$nom, $description, $taux_horaire, $id]);
        $message = t('admin_grades_success_edit');
    } else {
        $message = t('admin_grades_error_required');
    }
} elseif ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id) {
        $stmt = $pdo->prepare('DELETE FROM GRADES WHERE id=?');
        $stmt->execute([$id]);
        $message = t('admin_grades_success_delete');
    }
}

// Récupération des grades
$stmt = $pdo->query('SELECT id, nom, description, taux_horaire FROM GRADES ORDER BY taux_horaire ASC');
$grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<main>
    <div class="container admin-grades-container">
        <h2 class="admin-grades-title"><?= t('admin_grades_admin_title') ?></h2>
        <?php if ($message): ?>
            <div class="admin-grades-message">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <form method="post" class="admin-grades-form">
            <input type="hidden" name="action" value="add">
            <input type="text" name="nom" placeholder="<?= t('admin_grades_placeholder_nom') ?>" required>
            <input type="text" name="description" placeholder="<?= t('admin_grades_placeholder_description') ?>" required>
            <input type="number" step="0.01" name="taux_horaire" placeholder="<?= t('admin_grades_placeholder_taux') ?>" required>
            <button type="submit"><?= t('admin_grades_btn_add') ?></button>
        </form>
        <table class="grades-table-gauche">
            <thead>
                <tr>
                    <th><?= t('admin_grades_col_grade') ?></th>
                    <th><?= t('admin_grades_col_taux') ?></th>
                    <th><?= t('admin_grades_col_condition') ?></th>
                    <th><?= t('admin_grades_col_actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grades as $grade): ?>
                    <tr>
                        <form method="post" style="display:contents;">
                            <input type="hidden" name="id" value="<?= $grade['id'] ?>">
                            <td><input type="text" name="nom" value="<?= htmlspecialchars($grade['nom']) ?>"></td>
                            <td><input type="number" step="0.01" name="taux_horaire" value="<?= htmlspecialchars($grade['taux_horaire']) ?>"></td>
                            <td><input type="text" name="description" value="<?= htmlspecialchars($grade['description']) ?>"></td>
                            <td class="grades-table-actions">
                                <button type="submit" name="action" value="edit" class="btn-save"><?= t('admin_grades_btn_save') ?></button>
                                <button type="submit" name="action" value="delete" class="btn-delete" onclick="return confirm('<?= t('admin_grades_confirm_delete') ?>')"><?= t('admin_grades_btn_delete') ?></button>
                            </td>
                        </form>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
<?php
include __DIR__ . '/../includes/footer.php';
?>
