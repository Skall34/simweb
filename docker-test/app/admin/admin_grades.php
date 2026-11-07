<?php
require_once __DIR__ . '/../includes/require_admin.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/menu_logged.php';

/**
 * Recalcule le grade_id de tous les pilotes actifs en fonction de leurs heures de vol
 * et des seuils définis dans la table GRADES.
 * 
 * @param PDO $pdo Connexion à la base de données
 * @return int Nombre de pilotes mis à jour
 */
function recalculerGradesPilotes($pdo) {
    // Récupérer tous les grades triés par niveau
    $stmtGrades = $pdo->query("SELECT id, niveau, seuil_heures FROM GRADES ORDER BY niveau DESC");
    $grades = $stmtGrades->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($grades)) {
        return 0;
    }
    
    // Récupérer tous les pilotes actifs avec leurs heures de vol
    $stmtPilotes = $pdo->query("
        SELECT 
            p.id,
            p.grade_id,
            COALESCE(SUM(TIME_TO_SEC(cdvg.temps_vol)), 0) AS total_secondes
        FROM PILOTES p
        LEFT JOIN CARNET_DE_VOL_GENERAL cdvg ON p.id = cdvg.pilote_id
        WHERE p.actif = 1
        GROUP BY p.id, p.grade_id
    ");
    $pilotes = $stmtPilotes->fetchAll(PDO::FETCH_ASSOC);
    
    $nbMisAJour = 0;
    $stmtUpdate = $pdo->prepare("UPDATE PILOTES SET grade_id = ? WHERE id = ?");
    
    foreach ($pilotes as $pilote) {
        $totalHeures = $pilote['total_secondes'] / 3600;
        
        // Trouver le grade approprié (le plus haut niveau dont le seuil est atteint)
        $nouveauGradeId = null;
        foreach ($grades as $grade) {
            if ($totalHeures >= $grade['seuil_heures']) {
                $nouveauGradeId = $grade['id'];
                break; // On prend le premier trouvé (plus haut niveau car trié DESC)
            }
        }
        
        // Si aucun grade trouvé, prendre le grade de niveau le plus bas
        if ($nouveauGradeId === null) {
            $nouveauGradeId = end($grades)['id'];
        }
        
        // Mettre à jour si le grade a changé
        if ($nouveauGradeId != $pilote['grade_id']) {
            $stmtUpdate->execute([$nouveauGradeId, $pilote['id']]);
            $nbMisAJour++;
        }
    }
    
    return $nbMisAJour;
}

// Gestion des ajouts/edits/suppressions
$action = $_POST['action'] ?? null;
$message = '';
$nbPilotesMisAJour = 0;

if ($action === 'add') {
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $taux_horaire = floatval(str_replace(',', '.', $_POST['taux_horaire'] ?? '0'));
    $seuil_heures = intval($_POST['seuil_heures'] ?? 0);
    if ($nom && $description && $taux_horaire > 0 && $seuil_heures >= 0) {
        // Trouver le niveau max et ajouter +1
        $maxNiveau = $pdo->query('SELECT COALESCE(MAX(niveau), 0) FROM GRADES')->fetchColumn();
        $stmt = $pdo->prepare('INSERT INTO GRADES (nom, description, taux_horaire, niveau, seuil_heures) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$nom, $description, $taux_horaire, $maxNiveau + 1, $seuil_heures]);
        // Recalculer les grades de tous les pilotes
        $nbPilotesMisAJour = recalculerGradesPilotes($pdo);
        $message = t('admin_grades_success_add');
        if ($nbPilotesMisAJour > 0) {
            $message .= " ($nbPilotesMisAJour pilote(s) mis à jour)";
        }
    } else {
        $message = t('admin_grades_error_required');
    }
} elseif ($action === 'edit') {
    $id = intval($_POST['id'] ?? 0);
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $taux_horaire = floatval(str_replace(',', '.', $_POST['taux_horaire'] ?? '0'));
    $seuil_heures = intval($_POST['seuil_heures'] ?? 0);
    if ($id && $nom && $description && $taux_horaire > 0 && $seuil_heures >= 0) {
        $stmt = $pdo->prepare('UPDATE GRADES SET nom=?, description=?, taux_horaire=?, seuil_heures=? WHERE id=?');
        $stmt->execute([$nom, $description, $taux_horaire, $seuil_heures, $id]);
        // Recalculer les grades de tous les pilotes
        $nbPilotesMisAJour = recalculerGradesPilotes($pdo);
        $message = t('admin_grades_success_edit');
        if ($nbPilotesMisAJour > 0) {
            $message .= " ($nbPilotesMisAJour pilote(s) mis à jour)";
        }
    } else {
        $message = t('admin_grades_error_required');
    }
} elseif ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id) {
        $stmt = $pdo->prepare('DELETE FROM GRADES WHERE id=?');
        $stmt->execute([$id]);
        // Recalculer les grades de tous les pilotes
        $nbPilotesMisAJour = recalculerGradesPilotes($pdo);
        $message = t('admin_grades_success_delete');
        if ($nbPilotesMisAJour > 0) {
            $message .= " ($nbPilotesMisAJour pilote(s) mis à jour)";
        }
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
            // Recalculer les grades de tous les pilotes
            $nbPilotesMisAJour = recalculerGradesPilotes($pdo);
            $message = 'Grade déplacé vers le haut';
            if ($nbPilotesMisAJour > 0) {
                $message .= " ($nbPilotesMisAJour pilote(s) mis à jour)";
            }
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
            // Recalculer les grades de tous les pilotes
            $nbPilotesMisAJour = recalculerGradesPilotes($pdo);
            $message = 'Grade déplacé vers le bas';
            if ($nbPilotesMisAJour > 0) {
                $message .= " ($nbPilotesMisAJour pilote(s) mis à jour)";
            }
        }
    }
}

// Récupération des grades
$stmt = $pdo->query('SELECT id, nom, description, taux_horaire, niveau, seuil_heures FROM GRADES ORDER BY niveau ASC');
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
    WHERE p.actif = 1
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
            <input type="number" step="1" name="seuil_heures" placeholder="Seuil heures requis" required>
            <input type="number" step="10" name="taux_horaire" placeholder="<?= t('admin_grades_placeholder_taux') ?>" required>
            <button type="submit"><?= t('admin_grades_btn_add') ?></button>
        </form>
        
        <div class="grades-tables-row">
            <div class="grades-table-section">
                <table class="grades-table-gauche">
            <thead>
                <tr>
                    <th>Ordre</th>
                    <th><?= t('admin_grades_col_grade') ?></th>
                    <th>Seuil (h)</th>
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
                            <td><input type="number" step="1" name="seuil_heures" class="grade-input" value="<?= htmlspecialchars($grade['seuil_heures']) ?>" data-initial="<?= htmlspecialchars($grade['seuil_heures']) ?>" style="width: 80px;"></td>
                            <td><input type="number" step="10" name="taux_horaire" class="grade-input" value="<?= htmlspecialchars($grade['taux_horaire']) ?>" data-initial="<?= htmlspecialchars($grade['taux_horaire']) ?>"></td>
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
