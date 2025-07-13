<?php
require_once("includes/db_connect.php");

try {
    $sql = "
        SELECT * FROM `Live_FLIGHTS`
    ";
    $stmt = $pdo->query($sql);
    $liveFlights = $stmt->fetchAll();

    if (count($liveFlights) > 0): ?>
        <table class="table-skywings">
            <thead>
                <tr>
                    <th>Callsign</th>
                    <th>Départ</th>
                    <th>Destination</th>
                    <th>Appareil</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($liveFlights as $flight): ?>
                    <tr>
                        <td><?= htmlspecialchars($flight['Callsign']) ?></td>
                        <td><?= htmlspecialchars($flight['ICAO_Dep']) ?></td>
                        <td><?= htmlspecialchars($flight['ICAO_Arr']) ?></td>
                        <td><?= htmlspecialchars($flight['Avion']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Aucun vol en cours.</p>
    <?php endif;
} catch (PDOException $e) {
    echo "<p>Erreur lors de la récupération des vols en cours : " . htmlspecialchars($e->getMessage()) . "</p>";
}
