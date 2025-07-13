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
        <table class="table-skywings" border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Fleet $type</th>
                    <th>Coût horaire (€)</th>
                    <th>Prix (€)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($types as $type): ?>
                <tr>
                    <td><?= htmlspecialchars($type['fleet_type']) ?></td>
                    <td><?= number_format($type['cout_horaire'], 2, ',', ' ') ?></td>
                    <td><?= number_format($type['cout_appareil'], 0, '', ' ') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
