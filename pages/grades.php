<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/require_login.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/menu_logged.php';


// Récupération des grades
$stmt = $pdo->query('SELECT nom, description, taux_horaire FROM GRADES ORDER BY taux_horaire ASC');
$grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<main>
    <div class="container grades-container">
        <h2><?= t('grades_title') ?></h2>
        <table class="grades-table-gauche">
            <thead>
                <tr>
                    <th><?= t('grades_table_grade') ?></th>
                    <th><?= t('grades_table_taux_horaire') ?></th>
                    <th><?= t('grades_table_condition') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grades as $grade): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($grade['nom']) ?></strong></td>
                        <td><?= number_format($grade['taux_horaire'], 2, ',', ' ') ?></td>
                        <td><?= htmlspecialchars($grade['description']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
<?php
include __DIR__ . '/../includes/footer.php';
?>