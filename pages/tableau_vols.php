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
      cdvg.id AS id_vol,
      cdvg.date_vol,
      p.callsign,
      f.immat,
      ft.fleet_type AS fleet_type_libelle,
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
    LEFT JOIN FLEET_TYPE ft ON f.fleet_type = ft.id
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
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
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
    <div style="height: 18px;"></div>
    <div class="table-main-padding">
        
    <!-- Tableau d'en-tête fixe -->
    <table class="table-header-fixed">
        <thead>
            <tr>
                <th style="width:98px;">Date vol</th>
                <th style="width:97px;">Callsign</th>
                <th style="width:98px;">Immat</th>
                <th style="width:98px;">Fleet type</th>
                <th style="width:98px;">Départ</th>
                <th style="width:98px;">Destination</th>
                <th style="width:99px;">Fuel départ</th>
                <th style="width:98px;">Fuel arrivée</th>
                <th style="width:99px;">Conso</th>
                <th style="width:97px;">Payload</th>
                <th style="width:98px;">Heure départ</th>
                <th style="width:97px;">Heure arrivée</th>
                <th style="width:98px;">Block time</th>
                <th style="width:60px;">Note</th>
                <th style="width:60px;">Mission</th>
                <th style="width:100px;">Recette</th>
                <th style="width:108px;">Pirep</th>
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
                    'ID vol' => $vol['id_vol'],
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
                    'Recette du vol' => number_format($vol['cout_vol'] !== null ? (float)$vol['cout_vol'] : 0, 2) . ' €',
                    'Pirep' => $pirep_complet
                ];
                $details_json = htmlspecialchars(json_encode($details), ENT_QUOTES, 'UTF-8');
            ?>
                <tr class="vol-row" data-details="<?= $details_json ?>">
                    <td><?= $date_formatee ?></td>
                    <td><?php echo htmlspecialchars($vol['callsign']); ?></td>
                    <td><?php echo htmlspecialchars($vol['immat']); ?></td>
                    <td><?php echo htmlspecialchars($vol['fleet_type_libelle']); ?></td>
                    <td><?php echo htmlspecialchars($vol['depart']); ?></td>
                    <td><?php echo htmlspecialchars($vol['destination']); ?></td>
                    <td><?php echo rtrim(rtrim(htmlspecialchars($vol['fuel_depart']), '0'), '.') ?: '0'; ?></td>
                    <td><?php echo rtrim(rtrim(htmlspecialchars($vol['fuel_arrivee']), '0'), '.') ?: '0'; ?></td>
                    <td><?php echo rtrim(rtrim(htmlspecialchars($vol['conso']), '0'), '.') ?: '0'; ?></td>
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
            </tbody>
        </table>
        </div>
    </div>
    <!-- Popup modale pour détails du vol -->
    <div id="vol-modal" class="vol-modal" style="display:none;">
        <div class="vol-modal-content" >
            <span class="vol-modal-close" id="vol-modal-close">&times;</span>
            <h3>Détails du vol</h3>
            <table style="width:100%;border-collapse:collapse;">
                <tr>
                    <td style="width: 30%; padding: 0; margin: 0; border: 0; vertical-align: top;">
                        <div id="vol-modal-body" >
                        <!-- Les détails du vol seront injectés ici -->
                        </div>
                    </td>
                    <td style="width: 70%; padding: 0; margin: 0; border: 0; vertical-align: middle;">
                        <div id="map" style="width: 100%; height: 400px;"></div>
                    </td>
                </tr>
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
    width: 95px; /* Largeur de la colonne "Date vol" */
    width: 95px; /* Largeur de la colonne "Callsign" */
    /* Définissez les largeurs des autres colonnes de la même manière */
    background: #0d47a1;
    color: #fff;
    border-bottom: 2px solid #08306b;
    border-right: 1px solid #b3c6e0;
    z-index: 10;
    box-shadow: 0 2px 4px rgba(0,0,0,0.03);
    padding: 8px 10px;
    text-align: center;
    font-weight: bold;
    letter-spacing: 0.5px;
    white-space: nowrap;
}
.table-header-fixed th:last-child {
    border-right: none;
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

<script>
var map;

// Synchronisation du scroll horizontal de l'en-tête
document.querySelector('.table-scroll-wrapper').addEventListener('scroll', function() {
    document.querySelector('.table-header-fixed').scrollLeft = this.scrollLeft;
});

// Gestion du popup détails vol
document.querySelectorAll('.vol-row').forEach(function(row) {
    row.addEventListener('click', function() {
        const details = this.getAttribute('data-details');
        const detailsObj = JSON.parse(details);
        fetch('../includes/flight_details_table.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'details=' + encodeURIComponent(details)
        })
        .then(response => response.text())
        .then(html => {
            document.getElementById('vol-modal-body').innerHTML = html;
            document.getElementById('vol-modal').style.display = 'flex';

            // Initialisation de la carte
            if (window.map) {
                window.map.remove();
            }
            var mapDiv = document.getElementById('map');
            if (!mapDiv) return;

            // Centrage par défaut
            var lat = 48.8566, lng = 2.3522, zoom = 8;
            if (detailsObj.Latitude && detailsObj.Longitude) {
                lat = detailsObj.Latitude;
                lng = detailsObj.Longitude;
            }
            window.map = L.map(mapDiv).setView([lat, lng], zoom);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(window.map);

            // Ajout du marker de position (optionnel)
            L.marker([lat, lng]).addTo(window.map)
                .bindPopup('Position du vol')
                .openPopup();

            // Récupération et affichage du tracé GPS
            if (detailsObj['ID vol']) {
                fetch('../api/api_getGPSTrace.php?vol_id=' + encodeURIComponent(detailsObj['ID vol']))
                    .then(resp => resp.json())
                    .then(data => {
                        if (data.path) {
                            let trace = [];
                            try {
                                trace = JSON.parse(data.path);
                            } catch (e) {
                                console.error('Erreur de parsing du tracé GPS:', e);
                                return;
                            }
                            if (Array.isArray(trace) && trace.length > 0) {
                                const latlngs = trace.map(pt => [parseFloat(pt.Lat), parseFloat(pt.Long)]);
                                L.polyline(latlngs, {color: 'red', weight: 3}).addTo(window.map);
                                window.map.fitBounds(latlngs);
                            }
                        }
                    })
                    .catch(e => {
                        console.error('Erreur lors du chargement du tracé GPS:', e);
                    });
            }
        })
        .catch((e) => {
            console.error('Erreur lors du chargement des détails du vol.' + e);
            document.getElementById('vol-modal-body').innerHTML = "<p>Erreur lors du chargement des détails du vol.</p>";
            document.getElementById('vol-modal').style.display = 'flex';
        });
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
