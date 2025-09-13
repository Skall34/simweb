<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/db_connect.php';
include '../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';

// Récupérer les filtres

$callsignFilter = isset($_GET['callsign']) ? trim($_GET['callsign']) : '';
$immatFilter = isset($_GET['immat']) ? trim($_GET['immat']) : '';
$missionFilter = isset($_GET['mission']) ? trim($_GET['mission']) : '';

// Récupérer la liste des missions pour le filtre
$missionsList = [];
try {
    $stmtMissions = $pdo->query("SELECT DISTINCT libelle FROM MISSIONS WHERE libelle IS NOT NULL AND libelle <> '' ORDER BY libelle ASC");
    $missionsList = $stmtMissions->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Ignore erreur
}

// Requête principale des vols
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
      SEC_TO_TIME(
        (UNIX_TIMESTAMP(CONCAT(cdvg.date_vol, ' ', cdvg.heure_arrivee)) - UNIX_TIMESTAMP(CONCAT(cdvg.date_vol, ' ', cdvg.heure_depart)) + 
         IF(cdvg.heure_arrivee < cdvg.heure_depart, 86400, 0))
      ) AS block_time,
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

    if ($missionFilter !== '') {
        $conditions[] = "m.libelle = :mission";
        $params['mission'] = $missionFilter;
    }
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }

    $sql .= " ORDER BY cdvg.date_vol DESC, cdvg.heure_arrivee DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $vols = $stmt->fetchAll();

    // --- Ajout : récupération des positions des aéroports de départ et d'arrivée ---
    $aeroports = [];
    $icaos = [];
    foreach ($vols as $vol) {
        if (!empty($vol['depart'])) $icaos[] = $vol['depart'];
        if (!empty($vol['destination'])) $icaos[] = $vol['destination'];
    }
    $icaos = array_values(array_unique($icaos));

    if (count($icaos) > 0) {
        $placeholders = implode(',', array_fill(0, count($icaos), '?'));
        $stmtAero = $pdo->prepare("SELECT ident, latitude_deg, longitude_deg, municipality FROM AEROPORTS WHERE ident IN ($placeholders)");
        $stmtAero->execute($icaos);
        while ($row = $stmtAero->fetch(PDO::FETCH_ASSOC)) {
            $aeroports[$row['ident']] = [
                'latitude_deg' => $row['latitude_deg'],
                'longitude_deg' => $row['longitude_deg'],
                'municipality' => $row['municipality'] ?: ''
            ];
        }
    }
    // --- Fin ajout ---

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

        <label for="mission" style="margin-left:18px;">Filtrer par Mission:</label>
        <select id="mission" name="mission">
            <option value="">-- Toutes les missions --</option>
            <?php foreach ($missionsList as $m): ?>
                <option value="<?= htmlspecialchars($m) ?>" <?= ($missionFilter === $m) ? 'selected' : '' ?>><?= htmlspecialchars($m) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn">Filtrer</button>
        <button type="button" class="btn" onclick="window.location.href='<?= basename($_SERVER['PHP_SELF']) ?>';">Réinitialiser</button>
    </form>
    <div style="height: 18px;"></div>
    <div class="table-main-padding">
        
    <!-- Tableau d'en-tête fixe -->
    <table class="table-skywings">
        <thead class="table-skywings" >
            <tr class="table-skywings">
                <th style="width:10%">Date vol</th>
                <th style="width:8%">Callsign</th>
                <th style="width:5%">Immat</th>
                <th style="width:10%">Fleet type</th>
                <th style="width:5%">Départ</th>
                <th style="width:5%">Dest.</th>
                <th style="width:5%">Fuel arrivée</th>
                <th style="width:5%">Conso</th>
                <th style="width:5%">Payload</th>
                <th style="width:10%">Heure arrivée</th>
                <th style="width:10%">Block time</th>
                <th style="width:5%">Note</th>
                <th style="width:10%">Recette</th>
                <th style="width:5%">Mission</th>
                <th style="width:10px">&nbsp</th>
            </tr>
        </thead>
        <tbody class="table-skywings" >
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
                'Pirep' => $pirep_complet,
                'lat_depart' => isset($aeroports[$vol['depart']]) ? $aeroports[$vol['depart']]['latitude_deg'] : null,
                'long_depart' => isset($aeroports[$vol['depart']]) ? $aeroports[$vol['depart']]['longitude_deg'] : null,
                'lat_destination' => isset($aeroports[$vol['destination']]) ? $aeroports[$vol['destination']]['latitude_deg'] : null,
                'long_destination' => isset($aeroports[$vol['destination']]) ? $aeroports[$vol['destination']]['longitude_deg'] : null,
                'name_départ' => isset($aeroports[$vol['depart']]) ? $aeroports[$vol['depart']]['municipality'] : '',
                'name_dest' => isset($aeroports[$vol['destination']]) ? $aeroports[$vol['destination']]['municipality'] : ''
            ];
            $details_json = htmlspecialchars(json_encode($details), ENT_QUOTES, 'UTF-8');
        ?>
            <tr class="vol-row" title="<?= htmlspecialchars($pirep_complet) ?>" data-details="<?= $details_json ?>">
                <td style="width:10%"><?= $date_formatee ?></td>
                <td style="width:8%"><?php echo htmlspecialchars($vol['callsign']); ?></td>
                <td style="width:5%"><?php echo htmlspecialchars($vol['immat']); ?></td>
                <td style="width:10%"><?php echo htmlspecialchars($vol['fleet_type_libelle']); ?></td>
                <td style="width:5%"><?php echo htmlspecialchars($vol['depart']); ?></td>
                <td style="width:5%"><?php echo htmlspecialchars($vol['destination']); ?></td>
                <td style="width:5%"><?php echo rtrim(rtrim(htmlspecialchars($vol['fuel_arrivee']), '0'), '.') ?: '0'; ?></td>
                <td style="width:5%"><?php echo rtrim(rtrim(htmlspecialchars($vol['conso']), '0'), '.') ?: '0'; ?></td>
                <td style="width:5%"><?php echo htmlspecialchars($vol['payload']); ?></td>
                <td style="width:10%"><?php echo htmlspecialchars($vol['heure_arrivee']); ?></td>
                <td style="width:10%"><?php echo htmlspecialchars(substr($vol['block_time'], 0, 8)); ?></td>
                <td style="width=5%"><?php echo htmlspecialchars($vol['note_du_vol']); ?></td>
                <td style="width:10%">
                    <?php
                    $recette = $vol['cout_vol'] !== null ? (float)$vol['cout_vol'] : 0;
                    if ($recette < 0) {
                        echo '<span style="color:#d60000;">' . number_format($recette, 2) . ' €</span>';
                    } else {
                        echo number_format($recette, 2) . ' €';
                    }
                    ?>
                </td>
                <td style="width=5%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($vol['mission_libelle']); ?>">
                    <?php echo mb_strimwidth($vol['mission_libelle'], 0, 11, '...'); ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </div>
        </tbody>
    </table>
    </div>
    <!-- Popup modale pour détails du vol -->
    <div id="vol-modal" class="vol-modal" style="display:none;">
        <div class="vol-modal-content" >
            <span class="vol-modal-close" id="vol-modal-close">&times;</span>
            <h3>Détails du vol</h3>
            <table style="width:100%;border-collapse:collapse;">
                <tr>
                    <td style="width: 365px; padding: 0; margin: 0; border: 0; vertical-align: top;">
                        <div id="vol-modal-body" >
                        <!-- Les détails du vol seront injectés ici -->
                        </div>
                    </td>
                    <td style="width: 70%; padding: 0; margin: 0; border: 0; vertical-align: middle;">
                        <div id="map" style="width: 600px; height: 400px;"></div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

</main>

<style>

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
//document.querySelector('.table-scroll-wrapper').addEventListener('scroll', function() {
//    document.querySelector('.table-header-fixed').scrollLeft = this.scrollLeft;
//});

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
                                L.polyline(latlngs, {color: 'blue', weight: 3}).addTo(window.map);
                                window.map.fitBounds(latlngs);
                            }
                        }else {
                            console.warn('Aucun tracé GPS trouvé pour ce vol.');
                            //trace un segment entre les aéroports de départ et d'arrivée
                            if (detailsObj.lat_depart && detailsObj.long_depart && detailsObj.lat_destination && detailsObj.long_destination) {
                                const latlngs = [
                                    [detailsObj.lat_depart, detailsObj.long_depart],
                                    [detailsObj.lat_destination, detailsObj.long_destination]
                                ];
                                L.polyline(latlngs, {color: 'red', weight: 3, dashArray: '8, 8'}).addTo(window.map);
                                window.map.fitBounds(latlngs);
                            }
                        }
                    })
                    .catch(e => {
                        console.error('Erreur lors du chargement du tracé GPS:', e);
                    });
            }else{
            }

            //ajoute un marker pour les aéroports de départ et d'arrivée
            if (detailsObj.lat_depart && detailsObj.long_depart) {
                L.marker([detailsObj.lat_depart, detailsObj.long_depart]).addTo(window.map)
                    .bindPopup('Départ: ' + detailsObj['name_départ'] + ' (' + detailsObj['Départ'] + ')');
            }
            if (detailsObj.lat_destination && detailsObj.long_destination) {
                L.marker([detailsObj.lat_destination, detailsObj.long_destination]).addTo(window.map)
                    .bindPopup('Destination: ' + detailsObj['name_dest'] + ' (' + detailsObj['Destination'] + ')' );
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
</body>
</html>