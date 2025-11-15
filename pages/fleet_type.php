<?php
session_start();

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/require_login.php';

// Récupère tous les types de flotte avec coût horaire et prix
$sql = "SELECT fleet_type, cout_horaire, cout_appareil FROM FLEET_TYPE ORDER BY fleet_type";
$stmt = $pdo->query($sql);
$types = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';

?>

<main>
    <h2><?= t('fleet_type_title') ?></h2>

    <?php if (empty($types)): ?>
        <p><?= t('fleet_type_no_results') ?></p>
    <?php else: ?>
        <?php
        // Découpe le tableau en 2 colonnes égales
        $total = count($types);
        $mid = (int)ceil($total / 2);
        $col1 = array_slice($types, 0, $mid);
        $col2 = array_slice($types, $mid);
        ?>
        <div class="fleet-type-tables-row">
            <div class="fleet-type-table-section">
                <table class="table-skywings">
                    <thead>
                        <tr>
                            <th class="fleet_type"><?= t('fleet_type_table_type') ?></th>
                            <th class="cout_horaire"><?= t('fleet_type_table_cout_horaire') ?></th>
                            <th class="prix"><?= t('fleet_type_table_prix') ?></th>
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
            <div class="fleet-type-table-section">
                <table class="table-skywings">
                    <thead>
                        <tr>
                            <th class="fleet_type"><?= t('fleet_type_table_type') ?></th>
                            <th class="cout_horaire"><?= t('fleet_type_table_cout_horaire') ?></th>
                            <th class="prix"><?= t('fleet_type_table_prix') ?></th>
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
