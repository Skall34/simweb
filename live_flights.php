<?php
require_once("lang.php");
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
                    <th><?= t('liveflights_header_callsign') ?></th>
                    <th><?= t('liveflights_header_departure') ?></th>
                    <th><?= t('liveflights_header_destination') ?></th>
                    <th><?= t('liveflights_header_aircraft') ?></th>
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
        <p><?= t('liveflights_none') ?></p>
    <?php endif;
} catch (PDOException $e) {
    echo "<p>" . t('liveflights_error_fetch') . htmlspecialchars($e->getMessage()) . "</p>";
}
