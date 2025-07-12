<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/db_connect.php';
include '../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';

// Récupérer les filtres
$callsignFilter = isset($_GET['callsign']) ? trim($_GET['callsign']) : '';
$immatFilter = isset($_GET['immat']) ? trim($_GET['immat']) : '';

try {
    $sql = "
    SELECT 
      cdvg.date_vol,
      p.callsign,
      f.immat,
      cdvg.depart,
      cdvg.destination,
      cdvg.fuel_depart,
      cdvg.fuel_arrivee,
      cdvg.payload,
      cdvg.heure_depart,
      cdvg.heure_arrivee,
      cdvg.note_du_vol,
      m.libelle AS mission_libelle,
      cdvg.pirep_maintenance,
      TIMEDIFF(cdvg.heure_arrivee, cdvg.heure_depart) AS block_time,
      (cdvg.fuel_depart - cdvg.fuel_arrivee) AS conso
    FROM CARNET_DE_VOL_GENERAL cdvg
    LEFT JOIN PILOTES p ON cdvg.pilote_id = p.id
    LEFT JOIN FLOTTE f ON cdvg.appareil_id = f.id
    LEFT JOIN MISSIONS m ON cdvg.mission_id = m.id
    ";

    $conditions = [];
    $params = [];

    if ($callsignFilter !== '') {
        $conditions[] = "p.callsign LIKE :callsign";
        $params['callsign'] = "%$callsignFilter%";
    }

    if ($immatFilter !== '') {
        $conditions[] = "f.immat LIKE :immat";
        $params['immat'] = "%$immatFilter%";
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }

    $sql .= " ORDER BY cdvg.date_vol DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $vols = $stmt->fetchAll();

} catch (PDOException $e) {
    echo "<p>Erreur SQL : " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}
?>

<main>
    <h2>Liste des vols</h2>

    <!-- Formulaire de filtre -->
    <form method="get" action="">
        <label for="callsign">&nbsp;&nbsp;Filtrer par Callsign :</label>
        <input type="text" id="callsign" name="callsign" value="<?php echo htmlspecialchars($callsignFilter); ?>">

        <label for="immat">&nbsp;&nbsp;Filtrer par Immat :</label>
        <input type="text" id="immat" name="immat" value="<?php echo htmlspecialchars($immatFilter); ?>">

        <button type="submit" class="btn">Filtrer</button>
        <button type="button" class="btn" onclick="window.location.href='<?= basename($_SERVER['PHP_SELF']) ?>';">Réinitialiser</button>
    </form>

    <div class="table-scroll-wrapper">
        <table class="table-skywings">
            <thead>
                <tr>
                    <th>Date vol</th>
                    <th>Callsign</th>
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
                    <th>Note du vol</th>
                    <th>Mission</th>
                    <th>Pirep maintenance</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($vols as $vol):
                $pirep_complet = $vol['pirep_maintenance'];
                $pirep_court = mb_strimwidth($pirep_complet, 0, 13, '...');
                $date_formatee = date("d-m-Y", strtotime($vol['date_vol']));
            ?>
                <tr>
                    <td><?= $date_formatee ?></td>
                    <td><?php echo htmlspecialchars($vol['callsign']); ?></td>
                    <td><?php echo htmlspecialchars($vol['immat']); ?></td>
                    <td><?php echo htmlspecialchars($vol['depart']); ?></td>
                    <td><?php echo htmlspecialchars($vol['destination']); ?></td>
                    <td><?php echo htmlspecialchars($vol['fuel_depart']); ?></td>
                    <td><?php echo htmlspecialchars($vol['fuel_arrivee']); ?></td>
                    <td><?php echo htmlspecialchars($vol['conso']); ?></td>
                    <td><?php echo htmlspecialchars($vol['payload']); ?></td>
                    <td><?php echo htmlspecialchars($vol['heure_depart']); ?></td>
                    <td><?php echo htmlspecialchars($vol['heure_arrivee']); ?></td>
                    <td><?php echo htmlspecialchars($vol['block_time']); ?></td>
                    <td><?php echo htmlspecialchars($vol['note_du_vol']); ?></td>
                    <td><?php echo htmlspecialchars($vol['mission_libelle']); ?></td>
                    <td title="<?= htmlspecialchars($pirep_complet) ?>"><?= htmlspecialchars($pirep_court) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
