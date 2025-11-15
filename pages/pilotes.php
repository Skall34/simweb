<?php
session_start();

require_once __DIR__ . '/../includes/db_connect.php';

require_once __DIR__ . '/../includes/require_login.php';

// Récupère tous les pilotes
$sql = "SELECT callsign, prenom, nom FROM PILOTES WHERE actif = 1 ORDER BY callsign";
$stmt = $pdo->query($sql);
$pilotes = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>

<main>
    <div class="container grades-container">
        <h2><?= t('pilotes_title') ?></h2>
        <?php if (empty($pilotes)): ?>
            <p><?= t('pilotes_no_results') ?></p>
        <?php else: ?>
            <table class="grades-table-gauche">
                <thead>
                    <tr>
                        <th><?= t('pilotes_table_callsign') ?></th>
                        <th><?= t('pilotes_table_prenom') ?></th>
                        <th><?= t('pilotes_table_nom') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pilotes as $pilote): ?>
                        <tr>
                            <td><?= htmlspecialchars($pilote['callsign']) ?></td>
                            <td><?= htmlspecialchars($pilote['prenom']) ?></td>
                            <td><?= htmlspecialchars($pilote['nom']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
