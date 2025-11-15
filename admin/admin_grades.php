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
    <div class="container" style="max-width:1100px;margin:40px 0 40px 0;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
        <h2 style="text-align:left;color:#1a3552;margin-bottom:28px;"><?= t('admin_grades_admin_title') ?></h2>
        <?php if ($message): ?>
            <div style="margin-bottom:18px;color:#1a3552;background:#eaf2fb;padding:10px 16px;border-radius:6px;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <form method="post" style="margin-bottom:32px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
            <input type="hidden" name="action" value="add">
            <input type="text" name="nom" placeholder="<?= t('admin_grades_placeholder_nom') ?>" required style="padding:8px 10px;border-radius:4px;border:1px solid #bbb;">
            <input type="text" name="description" placeholder="<?= t('admin_grades_placeholder_description') ?>" required style="padding:8px 10px;border-radius:4px;border:1px solid #bbb;min-width:180px;">
            <input type="number" step="0.01" name="taux_horaire" placeholder="<?= t('admin_grades_placeholder_taux') ?>" required style="padding:8px 10px;border-radius:4px;border:1px solid #bbb;width:120px;">
            <button type="submit" style="padding:8px 18px;background:#1a3552;color:#fff;border:none;border-radius:4px;"><?= t('admin_grades_btn_add') ?></button>
        </form>
        <table class="grades-table-gauche" style="min-width:1000px;width:100%;border-collapse:collapse;font-size:1.08em;margin-left:0;table-layout:auto;">
            <thead>
                <tr style="background:#eaf2fb;">
                    <th style="padding:10px 8px;text-align:left;"><?= t('admin_grades_col_grade') ?></th>
                    <th style="padding:10px 8px;text-align:left;"><?= t('admin_grades_col_taux') ?></th>
                    <th style="padding:10px 8px;text-align:left;"><?= t('admin_grades_col_condition') ?></th>
                    <th style="padding:10px 8px;text-align:left;"><?= t('admin_grades_col_actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grades as $grade): ?>
                    <tr style="background:#fff;">
                        <form method="post" style="display:contents;">
                            <input type="hidden" name="id" value="<?= $grade['id'] ?>">
                            <td style="padding:8px 8px;"><input type="text" name="nom" value="<?= htmlspecialchars($grade['nom']) ?>" style="width:220px;padding:4px 6px;border-radius:3px;border:1px solid #bbb;"></td>
                            <td style="padding:8px 8px;"><input type="number" step="0.01" name="taux_horaire" value="<?= htmlspecialchars($grade['taux_horaire']) ?>" style="width:80px;padding:4px 6px;border-radius:3px;border:1px solid #bbb;"></td>
                            <td style="padding:8px 8px;"><input type="text" name="description" value="<?= htmlspecialchars($grade['description']) ?>" style="width:320px;padding:4px 6px;border-radius:3px;border:1px solid #bbb;"></td>
                            <td style="padding:8px 8px;display:flex;gap:6px;">
                                <button type="submit" name="action" value="edit" style="background:#1a3552;color:#fff;border:none;padding:4px 10px;border-radius:3px;"><?= t('admin_grades_btn_save') ?></button>
                                <button type="submit" name="action" value="delete" style="background:#b00;color:#fff;border:none;padding:4px 10px;border-radius:3px;" onclick="return confirm('<?= t('admin_grades_confirm_delete') ?>')"><?= t('admin_grades_btn_delete') ?></button>
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
