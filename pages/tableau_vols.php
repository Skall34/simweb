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
      cdvg.cout_vol,
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

    <div class="table-main-padding">
        <!-- Tableau d'en-tête fixe -->
        <table class="table-skywings table-header-fixed">
            <thead>
                <tr>
                    <th style="min-width:90px;">Date vol</th>
                    <th style="min-width:90px;">Callsign</th>
                    <th style="min-width:90px;">Immat</th>
                    <th style="min-width:90px;">Départ</th>
                    <th style="min-width:90px;">Destination</th>
                    <th style="min-width:90px;">Fuel départ</th>
                    <th style="min-width:90px;">Fuel arrivée</th>
                    <th style="min-width:90px;">Conso</th>
                    <th style="min-width:90px;">Payload</th>
                    <th style="min-width:90px;">Heure départ</th>
                    <th style="min-width:90px;">Heure arrivée</th>
                    <th style="min-width:90px;">Block time</th>
                    <th style="min-width:110px;">Note du vol</th>
                    <th style="min-width:90px;">Mission</th>
                    <th style="min-width:110px;">Coût du vol</th>
                    <th style="min-width:120px;">Pirep</th>
                </tr>
            </thead>
        </table>
        <!-- Tableau scrollable des données -->
        <div class="table-scroll-wrapper">
            <table class="table-skywings">
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
                        <td style="max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($vol['note_du_vol']); ?></td>
                    <td style="max-width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($vol['mission_libelle']); ?>">
                        <?php echo mb_strimwidth($vol['mission_libelle'], 0, 11, '...'); ?>
                    </td>
                        <td><?php echo number_format($vol['cout_vol'] !== null ? (float)$vol['cout_vol'] : 0, 2) . ' €'; ?></td>
                        <td title="<?= htmlspecialchars($pirep_complet) ?>"><?= htmlspecialchars($pirep_court) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<style>
.table-header-fixed {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    table-layout: auto;
    margin-bottom: 0;
}
.table-header-fixed th {
    background: #0d47a1;
    color: #fff;
    border-bottom: 2px solid #08306b;
    z-index: 10;
    box-shadow: 0 2px 4px rgba(0,0,0,0.03);
    padding: 8px 10px;
    text-align: center;
    font-weight: bold;
    letter-spacing: 0.5px;
    white-space: nowrap;
}
.table-scroll-wrapper {
    width: 100%;
    max-height: 60vh;
    overflow-y: auto;
    overflow-x: auto;
    border-top: none;
}
.table-skywings {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    table-layout: auto;
}
.table-skywings td, .table-skywings th {
    padding: 8px 10px;
    text-align: center;
    min-width: 90px;
    box-sizing: border-box;
    white-space: nowrap;
}

.table-main-padding {
    padding-left: 32px;
}

</style>


</style>

<script>
// Synchronise dynamiquement la largeur des colonnes du header avec celles du tableau de données
function syncHeaderWidths() {
    const headerTable = document.querySelector('.table-header-fixed');
    const dataTable = document.querySelector('.table-scroll-wrapper .table-skywings');
    if (!headerTable || !dataTable) return;
    const headerCells = headerTable.querySelectorAll('th');
    const dataRow = dataTable.querySelector('tr');
    if (!dataRow) return;
    const dataCells = dataRow.querySelectorAll('td');
    if (headerCells.length !== dataCells.length) return;
    // Reset widths
    headerCells.forEach(th => th.style.width = '');
    dataCells.forEach(td => td.style.width = '');
    // Get computed widths from data cells
    for (let i = 0; i < headerCells.length; i++) {
        const width = dataCells[i].getBoundingClientRect().width + 'px';
        headerCells[i].style.width = width;
        dataCells[i].style.width = width;
    }
    // Ajuste la largeur du headerTable pour ne pas dépasser le dataTable (évite le débordement dû au scrollbar)
    headerTable.style.width = dataTable.getBoundingClientRect().width + 'px';
}

window.addEventListener('load', syncHeaderWidths);
window.addEventListener('resize', syncHeaderWidths);
document.querySelector('.table-scroll-wrapper').addEventListener('scroll', function() {
    // Optionnel : si besoin de synchroniser lors du scroll horizontal
    document.querySelector('.table-header-fixed').scrollLeft = this.scrollLeft;
});

// Si le contenu du tableau change dynamiquement, on peut rappeler syncHeaderWidths()
</script>

<?php include '../includes/footer.php'; ?>
