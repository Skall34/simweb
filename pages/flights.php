<?php

session_start();

require __DIR__ . '/../includes/db_connect.php';

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user']['id'];

// Requête pour récupérer les vols du pilote connecté
$sql = "SELECT c.date_vol, f.immat, c.depart, c.destination, 
               TIMEDIFF(c.heure_arrivee, c.heure_depart) AS duree
        FROM CARNET_DE_VOL_GENERAL c
        JOIN FLOTTE f ON c.appareil_id = f.id
        WHERE c.pilote_id = :id_pilote
        ORDER BY c.date_vol DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id_pilote' => $userId]);
$flights = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>

<main>
    <h2>Mes vols</h2>
    <?php if (empty($flights)): ?>
        <p>Aucun vol trouvé pour ce pilote.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0" class="table-skywings">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Immatriculation</th>
                    <th>Départ</th>
                    <th>Destination</th>
                    <th>Durée</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($flights as $flight): ?>
                <tr>
                    <td><?= htmlspecialchars($flight['date_vol']) ?></td>
                    <td><?= htmlspecialchars($flight['immat']) ?></td>
                    <td><?= htmlspecialchars($flight['depart']) ?></td>
                    <td><?= htmlspecialchars($flight['destination']) ?></td>
                    <td><?= htmlspecialchars($flight['duree']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
