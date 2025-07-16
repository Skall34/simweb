<?php
session_start();

require __DIR__ . '/../includes/db_connect.php';

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

// Récupère tous les types de flotte avec coût horaire et prix
$sql = "SELECT fleet_type, cout_horaire, cout_appareil FROM FLEET_TYPE ORDER BY fleet_type";
$stmt = $pdo->query($sql);
$types = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';

?>

<main>
    <h2>Fleet Types</h2>

    <?php if (empty($types)): ?>
        <p>Aucun type trouvé.</p>
    <?php else: ?>
        <style>
            .table-skywings th, .table-skywings td {
                padding: 4px 6px;
                font-size: 14px;
            }
            .table-skywings th.fleet_type, .table-skywings td.fleet_type { width: 120px; }
            .table-skywings th.cout_horaire, .table-skywings td.cout_horaire { width: 110px; }
            .table-skywings th.prix, .table-skywings td.prix { width: 100px; }
        </style>
        <table class="table-skywings" border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th class="fleet_type">Fleet type</th>
                    <th class="cout_horaire">Coût horaire (€)</th>
                    <th class="prix">Prix (€)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($types as $type): ?>
                <tr>
                    <td class="fleet_type"><?= htmlspecialchars($type['fleet_type']) ?></td>
                    <td class="cout_horaire"><?= number_format($type['cout_horaire'], 2, ',', ' ') ?></td>
                    <td class="prix"><?= number_format($type['cout_appareil'], 0, '', ' ') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
