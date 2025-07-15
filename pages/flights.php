<?php

session_start();

require __DIR__ . '/../includes/db_connect.php';

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user']['id'];

// Requête pour récupérer les vols du pilote connecté avec la structure de tableau_vols.php
$sql = "
SELECT 
  c.date_vol,
  f.immat,
  c.depart,
  c.destination,
  c.fuel_depart,
  c.fuel_arrivee,
  c.payload,
  c.heure_depart,
  c.heure_arrivee,
  c.note_du_vol,
  m.libelle AS mission_libelle,
  c.cout_vol,
  c.pirep_maintenance,
  TIMEDIFF(c.heure_arrivee, c.heure_depart) AS block_time,
  (c.fuel_depart - c.fuel_arrivee) AS conso
FROM CARNET_DE_VOL_GENERAL c
LEFT JOIN FLOTTE f ON c.appareil_id = f.id
LEFT JOIN MISSIONS m ON c.mission_id = m.id
WHERE c.pilote_id = :id_pilote
ORDER BY c.date_vol DESC
";

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
        <div class="table-scroll-wrapper">
        <table class="table-skywings">
            <thead>
                <tr>
                    <th>Date vol</th>
                    <th>Immat</th>
                    <th>Départ</th>
                    <th>Destination</th>
                    <th>Fuel départ</th>
                    <th>Fuel arrivée</th>
                    <th>Conso</th>
                    <th>Payload</th>
                    <th>Heure départ</th>
                    <th>Heure arrivée</th>
                    <th>Block time</th>
                    <th style="width:110px;">Note du vol</th>
                    <th>Mission</th>
                    <th>Coût du vol</th>
                    <th>Pirep maintenance</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($flights as $flight):
                $pirep_complet = $flight['pirep_maintenance'];
                $pirep_court = mb_strimwidth($pirep_complet, 0, 13, '...');
                $date_formatee = date("d-m-Y", strtotime($flight['date_vol']));
            ?>
                <tr>
                    <td><?= $date_formatee ?></td>
                    <td><?php echo htmlspecialchars($flight['immat']); ?></td>
                    <td><?php echo htmlspecialchars($flight['depart']); ?></td>
                    <td><?php echo htmlspecialchars($flight['destination']); ?></td>
                    <td><?php echo htmlspecialchars($flight['fuel_depart']); ?></td>
                    <td><?php echo htmlspecialchars($flight['fuel_arrivee']); ?></td>
                    <td><?php echo htmlspecialchars($flight['conso']); ?></td>
                    <td><?php echo htmlspecialchars($flight['payload']); ?></td>
                    <td><?php echo htmlspecialchars($flight['heure_depart']); ?></td>
                    <td><?php echo htmlspecialchars($flight['heure_arrivee']); ?></td>
                    <td><?php echo htmlspecialchars($flight['block_time']); ?></td>
                    <td style="max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($flight['note_du_vol']); ?></td>
                    <td><?php echo htmlspecialchars($flight['mission_libelle']); ?></td>
                    <td><?php echo number_format($flight['cout_vol'], 2) . ' €'; ?></td>
                    <td title="<?= htmlspecialchars($pirep_complet) ?>"><?= htmlspecialchars($pirep_court) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
