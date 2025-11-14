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
    <div class="container" style="max-width:700px;margin:40px 0 40px 0;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
        <h2 style="text-align:left;color:#1a3552;margin-bottom:28px;"><?= t('pilotes_title') ?></h2>
        <?php if (empty($pilotes)): ?>
            <p><?= t('pilotes_no_results') ?></p>
        <?php else: ?>
            <table class="grades-table-gauche" style="width:100%;border-collapse:collapse;font-size:1.08em;margin-left:0;">
                <thead>
                    <tr style="background:#eaf2fb;">
                        <th style="padding:10px 8px;text-align:left;"><?= t('pilotes_table_callsign') ?></th>
                        <th style="padding:10px 8px;text-align:left;"><?= t('pilotes_table_prenom') ?></th>
                        <th style="padding:10px 8px;text-align:left;"><?= t('pilotes_table_nom') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pilotes as $pilote): ?>
                        <tr style="background:#fff;">
                            <td style="padding:8px 8px;"><?= htmlspecialchars($pilote['callsign']) ?></td>
                            <td style="padding:8px 8px;"><?= htmlspecialchars($pilote['prenom']) ?></td>
                            <td style="padding:8px 8px;"><?= htmlspecialchars($pilote['nom']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
