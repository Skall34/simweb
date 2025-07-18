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
        <table class="table-skywings table-header-fixed" style="table-layout:fixed;">
            <thead>
                <tr>
                    <th style="width:95px;">Date vol</th>
                    <th style="width:95px;">Callsign</th>
                    <th style="width:98px;">Immat</th>
                    <th style="width:90px;">Départ</th>
                    <th style="width:90px;">Destination</th>
                    <th style="width:95px;">Fuel départ</th>
                    <th style="width:95px;">Fuel arrivée</th>
                    <th style="width:94px;">Conso</th>
                    <th style="width:97px;">Payload</th>
                    <th style="width:95px;">Heure départ</th>
                    <th style="width:95px;">Heure arrivée</th>
                    <th style="width:102px;">Block time</th>
                    <th style="width:60px;">Note</th>
                    <th style="width:70px;">Mission</th>
                    <th style="width:100px;">Recette</th>
                    <th style="width:100px;">Pirep</th>
                </tr>
            </thead>
        </table>
        <!-- Tableau scrollable des données -->
        <div class="table-scroll-wrapper">
            <table class="table-skywings" style="table-layout:fixed;">
                <tbody>
                <?php foreach ($vols as $i => $vol):
                    $pirep_complet = $vol['pirep_maintenance'];
                    $pirep_court = mb_strimwidth($pirep_complet, 0, 13, '...');
                    $date_formatee = date("d-m-Y", strtotime($vol['date_vol']));
                    // Préparer les données pour le popup (JSON encodé, puis échappé)
                    $details = [
                        'Date vol' => $date_formatee,
                        'Callsign' => $vol['callsign'],
                        'Immat' => $vol['immat'],
                        'Départ' => $vol['depart'],
                        'Destination' => $vol['destination'],
                        'Fuel départ' => $vol['fuel_depart'],
                        'Fuel arrivée' => $vol['fuel_arrivee'],
                        'Conso' => $vol['conso'],
                        'Payload' => $vol['payload'],
                        'Heure départ' => $vol['heure_depart'],
                        'Heure arrivée' => $vol['heure_arrivee'],
                        'Block time' => $vol['block_time'],
                        'Note du vol' => $vol['note_du_vol'],
                        'Mission' => $vol['mission_libelle'],
                        'Coût du vol' => number_format($vol['cout_vol'] !== null ? (float)$vol['cout_vol'] : 0, 2) . ' €',
                        'Pirep' => $pirep_complet
                    ];
                    $details_json = htmlspecialchars(json_encode($details), ENT_QUOTES, 'UTF-8');
                ?>
                    <tr class="vol-row" data-details="<?= $details_json ?>">
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
                        <td style="width:90px;max-width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;\"><?php echo htmlspecialchars($vol['note_du_vol']); ?></td>
                        <td style="width:90px;max-width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($vol['mission_libelle']); ?>">
                            <?php echo mb_strimwidth($vol['mission_libelle'], 0, 11, '...'); ?>
                        </td>
                        <td><?php echo number_format($vol['cout_vol'] !== null ? (float)$vol['cout_vol'] : 0, 2) . ' €'; ?></td>
                        <td title="<?= htmlspecialchars($pirep_complet) ?>"><?= htmlspecialchars($pirep_court) ?></td>
                    </tr>
                <?php endforeach; ?>
</div>
<!-- Popup modale pour détails du vol -->
<div id="vol-modal" class="vol-modal" style="display:none;">
    <div class="vol-modal-content">
        <span class="vol-modal-close" id="vol-modal-close">&times;</span>
        <h3>Détails du vol</h3>
        <div id="vol-modal-body">
            <!-- Les détails du vol seront injectés ici -->
        </div>
    </div>
</div>
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
    table-layout: fixed;
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
    table-layout: fixed;
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

.vol-modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.35);
    display: flex;
    align-items: center;
    justify-content: center;
}
.vol-modal-content {
    background: #fff;
    padding: 24px 32px;
    border-radius: 10px;
    min-width: 320px;
    max-width: 90vw;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 4px 24px rgba(0,0,0,0.18);
    position: relative;
}
.vol-modal-close {
    position: absolute;
    top: 12px;
    right: 18px;
    font-size: 2em;
    color: #0d47a1;
    cursor: pointer;
}

</style>


</style>

<script>

// Synchronisation du scroll horizontal de l'en-tête
document.querySelector('.table-scroll-wrapper').addEventListener('scroll', function() {
    document.querySelector('.table-header-fixed').scrollLeft = this.scrollLeft;
});

// Gestion du popup détails vol
document.querySelectorAll('.vol-row').forEach(function(row) {
    row.addEventListener('click', function() {
        const details = JSON.parse(this.getAttribute('data-details'));
        let html = '<table style="width:100%;border-collapse:collapse;">';
        for (const key in details) {
            html += '<tr><td style="font-weight:bold;padding:4px 8px;color:#0d47a1;">' + key + '</td><td style="padding:4px 8px;">' + (details[key] ?? '') + '</td></tr>';
        }
        html += '</table>';
        document.getElementById('vol-modal-body').innerHTML = html;
        document.getElementById('vol-modal').style.display = 'flex';
    });
});
document.getElementById('vol-modal-close').onclick = function() {
    document.getElementById('vol-modal').style.display = 'none';
};
window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') document.getElementById('vol-modal').style.display = 'none';
});
document.getElementById('vol-modal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>

<?php include '../includes/footer.php'; ?>
