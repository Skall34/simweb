<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/menu_logged.php';

if (!isset($_SESSION['user']['id'])) {
    header('Location: login.php');
    exit;
}


// Récupération des grades
$stmt = $pdo->query('SELECT nom, description, taux_horaire FROM GRADES ORDER BY taux_horaire ASC');
$grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<main>
    <div class="container" style="max-width:700px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
        <h2 style="text-align:center;color:#1a3552;margin-bottom:28px;">Détails des grades pilotes</h2>
        <table style="width:100%;border-collapse:collapse;font-size:1.08em;">
            <thead>
                <tr style="background:#eaf2fb;">
                    <th style="padding:10px 8px;text-align:left;">Grade</th>
                    <th style="padding:10px 8px;text-align:left;">Taux horaire (€)</th>
                    <th style="padding:10px 8px;text-align:left;">Condition d'accès</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grades as $grade): ?>
                    <tr style="background:#fff;">
                        <td style="padding:8px 8px;"><strong><?= htmlspecialchars($grade['nom']) ?></strong></td>
                        <td style="padding:8px 8px;"><?= number_format($grade['taux_horaire'], 2, ',', ' ') ?></td>
                        <td style="padding:8px 8px;"><?= htmlspecialchars($grade['description']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
<?php
include __DIR__ . '/../includes/footer.php';
?>