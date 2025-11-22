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
        // Trouver le niveau max et ajouter +1
        $maxNiveau = $pdo->query('SELECT COALESCE(MAX(niveau), 0) FROM GRADES')->fetchColumn();
        $stmt = $pdo->prepare('INSERT INTO GRADES (nom, description, taux_horaire, niveau) VALUES (?, ?, ?, ?)');
        $stmt->execute([$nom, $description, $taux_horaire, $maxNiveau + 1]);
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
} elseif ($action === 'move_up') {
    $id = intval($_POST['id'] ?? 0);
    if ($id) {
        // Récupérer le niveau actuel
        $stmt = $pdo->prepare('SELECT niveau FROM GRADES WHERE id=?');
        $stmt->execute([$id]);
        $currentNiveau = $stmt->fetchColumn();
        
        // Trouver le grade juste au-dessus
        $stmt = $pdo->prepare('SELECT id, niveau FROM GRADES WHERE niveau < ? ORDER BY niveau DESC LIMIT 1');
        $stmt->execute([$currentNiveau]);
        $previousGrade = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($previousGrade) {
            // Échanger les niveaux
            $pdo->prepare('UPDATE GRADES SET niveau=? WHERE id=?')->execute([$previousGrade['niveau'], $id]);
            $pdo->prepare('UPDATE GRADES SET niveau=? WHERE id=?')->execute([$currentNiveau, $previousGrade['id']]);
            $message = 'Grade déplacé vers le haut';
        }
    }
} elseif ($action === 'move_down') {
    $id = intval($_POST['id'] ?? 0);
    if ($id) {
        // Récupérer le niveau actuel
        $stmt = $pdo->prepare('SELECT niveau FROM GRADES WHERE id=?');
        $stmt->execute([$id]);
        $currentNiveau = $stmt->fetchColumn();
        
        // Trouver le grade juste en dessous
        $stmt = $pdo->prepare('SELECT id, niveau FROM GRADES WHERE niveau > ? ORDER BY niveau ASC LIMIT 1');
        $stmt->execute([$currentNiveau]);
        $nextGrade = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($nextGrade) {
            // Échanger les niveaux
            $pdo->prepare('UPDATE GRADES SET niveau=? WHERE id=?')->execute([$nextGrade['niveau'], $id]);
            $pdo->prepare('UPDATE GRADES SET niveau=? WHERE id=?')->execute([$currentNiveau, $nextGrade['id']]);
            $message = 'Grade déplacé vers le bas';
        }
    }
}

// Récupération des grades
$stmt = $pdo->query('SELECT id, nom, description, taux_horaire, niveau FROM GRADES ORDER BY niveau ASC');
$grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des pilotes avec leurs heures de vol
$stmt = $pdo->query('
    SELECT 
        p.id, 
        p.nom, 
        p.prenom,
        COALESCE(SUM(TIME_TO_SEC(c.temps_vol)), 0) as total_secondes
    FROM PILOTES p
    LEFT JOIN CARNET_DE_VOL_GENERAL c ON p.id = c.pilote_id
    GROUP BY p.id, p.nom, p.prenom
    ORDER BY total_secondes DESC
');
$pilotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        
        <div class="grades-tables-row">
            <div class="grades-table-section">
                <table class="grades-table-gauche">
            <thead>
                <tr>
                    <th>Ordre</th>
                    <th><?= t('admin_grades_col_grade') ?></th>
                    <th><?= t('admin_grades_col_taux') ?></th>
                    <th><?= t('admin_grades_col_condition') ?></th>
                    <th><?= t('admin_grades_col_actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grades as $index => $grade): ?>
                    <tr>
                        <td class="grades-order-cell">
                            <form method="post" class="grades-order-form">
                                <input type="hidden" name="id" value="<?= $grade['id'] ?>">
                                <button type="submit" name="action" value="move_up" class="btn-move-up" <?= $index === 0 ? 'disabled' : '' ?> title="Monter">▲</button>
                            </form>
                            <form method="post" class="grades-order-form">
                                <input type="hidden" name="id" value="<?= $grade['id'] ?>">
                                <button type="submit" name="action" value="move_down" class="btn-move-down" <?= $index === count($grades) - 1 ? 'disabled' : '' ?> title="Descendre">▼</button>
                            </form>
                        </td>
                        <form method="post" style="display:contents;" class="grade-edit-form" data-grade-id="<?= $grade['id'] ?>">
                            <input type="hidden" name="id" value="<?= $grade['id'] ?>">
                            <td><input type="text" name="nom" class="grade-input" value="<?= htmlspecialchars($grade['nom']) ?>" data-initial="<?= htmlspecialchars($grade['nom']) ?>"></td>
                            <td><input type="number" step="0.01" name="taux_horaire" class="grade-input" value="<?= htmlspecialchars($grade['taux_horaire']) ?>" data-initial="<?= htmlspecialchars($grade['taux_horaire']) ?>"></td>
                            <td><input type="text" name="description" class="grade-input" value="<?= htmlspecialchars($grade['description']) ?>" data-initial="<?= htmlspecialchars($grade['description']) ?>"></td>
                            <td class="grades-table-actions">
                                <button type="submit" name="action" value="edit" class="btn-save" disabled>Modifier</button>
                                <button type="submit" name="action" value="delete" class="btn-delete" onclick="return confirm('<?= t('admin_grades_confirm_delete') ?>')"><?= t('admin_grades_btn_delete') ?></button>
                            </td>
                        </form>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
            </div>

            <div class="pilots-hours-section">
                <h3><?= t('admin_grades_pilots_hours_title') ?></h3>
                <?php if (empty($pilotes)): ?>
                    <p class="no-data-message"><?= t('admin_grades_pilots_hours_no_data') ?></p>
                <?php else: ?>
                    <table class="pilots-hours-table">
                        <thead>
                            <tr>
                                <th><?= t('admin_grades_pilots_hours_col_pilot') ?></th>
                                <th><?= t('admin_grades_pilots_hours_col_hours') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pilotes as $pilote): ?>
                                <?php 
                                    $heures = $pilote['total_secondes'] / 3600;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($pilote['prenom'] . ' ' . $pilote['nom']) ?></td>
                                    <td><?= number_format($heures, 2, ',', ' ') ?> h</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<?php
include __DIR__ . '/../includes/footer.php';
?>
<script>
// Activer/désactiver le bouton Modifier selon les changements
document.addEventListener('DOMContentLoaded', function() {
    console.log('Script grades chargé');
    document.querySelectorAll('.grade-edit-form').forEach(function(form) {
        // Avec display:contents, les éléments sont dans le parent (tr)
        const row = form.closest('tr');
        const inputs = row.querySelectorAll('.grade-input');
        const btnSave = row.querySelector('button[name="action"][value="edit"]');
        
        console.log('Row:', row, 'Inputs:', inputs.length, 'Btn:', btnSave);
        
        if (!btnSave) {
            console.error('Bouton Modifier non trouvé');
            return;
        }
        
        inputs.forEach(function(input) {
            input.addEventListener('input', function() {
                let hasChanges = false;
                inputs.forEach(function(inp) {
                    const initialValue = inp.getAttribute('data-initial');
                    const currentValue = inp.value;
                    if (currentValue !== initialValue) {
                        hasChanges = true;
                    }
                });
                console.log('Changements:', hasChanges);
                btnSave.disabled = !hasChanges;
            });
        });
    });
});
</script>
