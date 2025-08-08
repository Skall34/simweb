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
            .table-section {
                min-width: 420px;
                max-width: 600px;
                margin: 0;
            }
            .table-skywings {
                width: 100%;
                border-collapse: collapse;
                background: #fff;
                box-shadow: 0 2px 8px rgba(0,0,0,0.04);
                border-radius: 8px;
                overflow: hidden;
            }
            .table-skywings th, .table-skywings td {
                padding: 4px 8px;
                font-size: 14px;
                text-align: left;
            }
            .table-skywings th {
                background: #0d47a1;
                color: #fff;
                font-weight: 600;
                border-bottom: 2px solid #08306b;
            }
            .table-skywings tr:nth-child(even) td {
                background: #f7fbff;
            }
            .table-skywings th.fleet_type, .table-skywings td.fleet_type { width: 120px; }
            .table-skywings th.cout_horaire, .table-skywings td.cout_horaire { width: 110px; }
            .table-skywings th.prix, .table-skywings td.prix { width: 100px; }
        </style>
        <?php
        // Découpe le tableau en 2 colonnes égales
        $total = count($types);
        $mid = (int)ceil($total / 2);
        $col1 = array_slice($types, 0, $mid);
        $col2 = array_slice($types, $mid);
        ?>
        <div style="display: flex; gap: 32px; align-items: flex-start;">
            <div class="table-section">
                <table class="table-skywings">
                    <thead>
                        <tr>
                            <th class="fleet_type">Fleet type</th>
                            <th class="cout_horaire">Coût horaire (€)</th>
                            <th class="prix">Prix (€)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($col1 as $type): ?>
                        <tr>
                            <td class="fleet_type"><?= htmlspecialchars($type['fleet_type']) ?></td>
                            <td class="cout_horaire"><?= number_format($type['cout_horaire'], 2, ',', ' ') ?></td>
                            <td class="prix"><?= number_format($type['cout_appareil'], 0, '', ' ') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-section">
                <table class="table-skywings">
                    <thead>
                        <tr>
                            <th class="fleet_type">Fleet type</th>
                            <th class="cout_horaire">Coût horaire (€)</th>
                            <th class="prix">Prix (€)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($col2 as $type): ?>
                        <tr>
                            <td class="fleet_type"><?= htmlspecialchars($type['fleet_type']) ?></td>
                            <td class="cout_horaire"><?= number_format($type['cout_horaire'], 2, ',', ' ') ?></td>
                            <td class="prix"><?= number_format($type['cout_appareil'], 0, '', ' ') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
