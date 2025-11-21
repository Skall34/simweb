<?php

session_start();

require_once __DIR__ . '/../includes/db_connect.php';

require_once __DIR__ . '/../includes/require_login.php';

$userId = $_SESSION['user']['id'];

// Requête pour récupérer les vols du pilote connecté avec la structure de tableau_vols.php

// Gestion des tris

// Filtres
$immatFilter = $_GET['immat'] ?? '';
$missionFilter = $_GET['mission'] ?? '';
$fleetTypeFilter = $_GET['fleetType'] ?? '';

// Récupérer la liste des missions pour le filtre
$missionsList = [];
try {
    $stmtMissions = $pdo->query("SELECT DISTINCT libelle FROM MISSIONS WHERE libelle IS NOT NULL AND libelle <> '' ORDER BY libelle ASC");
    $missionsList = $stmtMissions->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Ignore erreur
}
// Récupérer la liste des fleet types pour le filtre
$fleetTypeList = [];
try {
    $stmtFleetTypes = $pdo->query("SELECT DISTINCT fleet_type FROM FLEET_TYPE ORDER BY fleet_type ASC");
    $fleetTypeList = $stmtFleetTypes->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Ignore erreur
}

// Requête principale des vols

$sql = "
SELECT 
  c.id AS vol_id,
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
    ft.fleet_type AS fleet_type_label,
  c.cout_vol,
  c.pirep_maintenance,
  SEC_TO_TIME(
    (UNIX_TIMESTAMP(CONCAT(c.date_vol, ' ', c.heure_arrivee)) - UNIX_TIMESTAMP(CONCAT(c.date_vol, ' ', c.heure_depart)) + 
     IF(c.heure_arrivee < c.heure_depart, 86400, 0))
  ) AS block_time,
  (c.fuel_depart - c.fuel_arrivee) AS conso
FROM CARNET_DE_VOL_GENERAL c
LEFT JOIN FLOTTE f ON c.appareil_id = f.id
LEFT JOIN MISSIONS m ON c.mission_id = m.id
LEFT JOIN FLEET_TYPE ft ON f.fleet_type = ft.id
WHERE c.pilote_id = :id_pilote";

// Récupérer le nombre total de vols effectués par ce pilote (sans filtres)
try {
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM CARNET_DE_VOL_GENERAL WHERE pilote_id = :id_pilote");
    $stmtCount->execute(['id_pilote' => $userId]);
    $totalFlights = (int)$stmtCount->fetchColumn();
} catch (PDOException $e) {
    $totalFlights = 0;
}

$params = ['id_pilote' => $userId];
if ($immatFilter !== '') {
    $sql .= " AND f.immat LIKE :immat";
    $params['immat'] = '%' . $immatFilter . '%';
}
if ($missionFilter !== '') {
    $sql .= " AND m.libelle = :mission";
    $params['mission'] = $missionFilter;
}
if ($fleetTypeFilter !== '') {
    $sql .= " AND ft.fleet_type = :fleetType";
    $params['fleetType'] = $fleetTypeFilter;
}

$sql .= " ORDER BY c.date_vol DESC, c.heure_arrivee DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$flights = $stmt->fetchAll();

// --- Ajout : récupération des positions des aéroports de départ et d'arrivée ---
$aeroports = [];
$icaos = [];
foreach ($flights as $vol) {
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


include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>

<main>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <h2><?= t('flights_title') ?> (<?= $totalFlights ?>)</h2>
    <form method="get" action="flights.php" class="filters-form">
        <label for="immat"><?= t('flights_filter_immat') ?>:</label>
        <input type="text" id="immat" name="immat" value="<?= htmlspecialchars($immatFilter) ?>" placeholder="Ex: F-XXXX" class="fleet-filter-input input-160">

        <label for="mission" class="filter-margin"><?= t('flights_filter_mission') ?>:</label>
        <select id="mission" name="mission" class="fleet-filter-select">
            <option value=""><?= t('flights_filter_mission_all') ?></option>
            <?php foreach ($missionsList as $m): ?>
                <option value="<?= htmlspecialchars($m) ?>" <?= ($missionFilter === $m) ? 'selected' : '' ?>><?= htmlspecialchars($m) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="fleetType" class="filter-margin"><?= t('flights_filter_fleet_type') ?>:</label>
        <select id="fleetType" name="fleetType" class="fleet-filter-select input-160">
            <option value=""><?= t('flights_filter_fleet_type_all') ?></option>
            <?php foreach ($fleetTypeList as $f): ?>
                <option value="<?= htmlspecialchars($f) ?>" <?= ($fleetTypeFilter === $f) ? 'selected' : '' ?>><?= htmlspecialchars($f) ?></option>
            <?php endforeach; ?>
        </select>

        <button class="btn" type="submit"><?= t('flights_filter_button') ?></button>
        <button type="button" class="btn btn-reset" onclick="window.location.href='flights.php';"><?= t('flights_reset_button') ?></button>
    </form>
    <?php
        $nbResults = count($flights);
        if ($immatFilter !== '' || $missionFilter !== '') {
            echo '<p class="filter-info">' . $nbResults . ' ' . t('flights_filter_results') . '</p>';
        }
    ?>
    <?php if (empty($flights)): ?>
        <p><?= t('flights_no_results') ?></p>
    <?php else: ?>
        <div class="table-main-padding">
            <!-- Tableau d'en-tête fixe -->
            <table class="table-skywings">
                <thead class="table-skywings">
                    <tr class="table-skywings">
                        <th class="col-10"><?= t('flights_table_date') ?></th>
                        <th class="col-8"><?= t('flights_table_immat') ?></th>
                        <th class="col-6"><?= t('flights_table_fleet_type') ?></th>
                        <th class="col-5"><?= t('flights_table_depart') ?></th>
                        <th class="col-5"><?= t('flights_table_dest') ?></th>
                        <th class="col-5"><?= t('flights_table_fuel_arrivee') ?></th>
                        <th class="col-5"><?= t('flights_table_conso') ?></th>
                        <th class="col-5"><?= t('flights_table_payload') ?></th>
                        <th class="col-10"><?= t('flights_table_heure_arrivee') ?></th>
                        <th class="col-10"><?= t('flights_table_block_time') ?></th>
                        <th class="col-5"><?= t('flights_table_note') ?></th>
                        <th class="col-8"><?= t('flights_table_recette') ?></th>
                        <th class="col-8"><?= t('flights_table_mission') ?></th>
                    </tr>
                </thead>
                <tbody class="table-skywings">
                <?php foreach ($flights as $flight):
                    $pirep_complet = $flight['pirep_maintenance'];
                    $pirep_court = mb_strimwidth($pirep_complet, 0, 13, '...');
                        $date_formatee = date("d-m-Y", strtotime($flight['date_vol']));
                        // Format fuel arrival and conso: remove decimals when .00
                        $fuel_arrivee_val = isset($flight['fuel_arrivee']) ? (float)$flight['fuel_arrivee'] : 0;
                        $conso_val = isset($flight['conso']) ? (float)$flight['conso'] : 0;
                        $formatFuel = function($v) {
                            if ($v == (int)$v) {
                                return (string)(int)$v;
                            }
                            return number_format($v, 2, ',', ' ');
                        };
                        $fuel_arrivee_display = $formatFuel($fuel_arrivee_val);
                        $conso_display = $formatFuel($conso_val);
                    $details = [
                        'ID vol' => $flight['vol_id'],
                        'Date vol' => $date_formatee,
                        'Immat' => $flight['immat'],
                        'Départ' => $flight['depart'],
                        'Destination' => $flight['destination'],
                            'Fuel départ' => $flight['fuel_depart'],
                            'Fuel arrivée' => $fuel_arrivee_display,
                            'Conso' => $conso_display,
                        'Payload' => $flight['payload'],
                        'Heure départ' => $flight['heure_depart'],
                        'Heure arrivée' => $flight['heure_arrivee'],
                        'Block time' => $flight['block_time'],
                        'Note du vol' => $flight['note_du_vol'],
                        'Mission' => $flight['mission_libelle'],
                        'Type' => $flight['fleet_type_label'] ?? '',
                        'Recette du vol' => number_format(isset($flight['cout_vol']) && $flight['cout_vol'] !== null ? (float)$flight['cout_vol'] : 0.0, 2, ',', ' ') . ' €',
                        'Pirep' => $pirep_complet,
                        'lat_depart' => isset($aeroports[$flight['depart']]) ? $aeroports[$flight['depart']]['latitude_deg'] : null,
                        'long_depart' => isset($aeroports[$flight['depart']]) ? $aeroports[$flight['depart']]['longitude_deg'] : null,
                        'lat_destination' => isset($aeroports[$flight['destination']]) ? $aeroports[$flight['destination']]['latitude_deg'] : null,
                        'long_destination' => isset($aeroports[$flight['destination']]) ? $aeroports[$flight['destination']]['longitude_deg'] : null,
                        'name_depart' => isset($aeroports[$flight['depart']]) ? $aeroports[$flight['depart']]['municipality'] : '',
                        'name_dest' => isset($aeroports[$flight['destination']]) ? $aeroports[$flight['destination']]['municipality'] : ''
                    ];
                    $details_json = htmlspecialchars(json_encode($details), ENT_QUOTES, 'UTF-8');
                ?>
                    <tr class="vol-row"  title="<?= htmlspecialchars($pirep_complet) ?>" data-details="<?= $details_json ?>">
                        <td class="col-10"><?= $date_formatee ?></td>
                        <td class="col-8"><?php echo htmlspecialchars($flight['immat']); ?></td>
                        <td class="col-6"><?php echo htmlspecialchars($flight['fleet_type_label'] ?? ''); ?></td>
                        <td class="col-5"><?php echo htmlspecialchars($flight['depart']); ?></td>
                        <td class="col-5"><?php echo htmlspecialchars($flight['destination']); ?></td>
                        <td class="col-5"><?php echo htmlspecialchars($fuel_arrivee_display); ?></td>
                        <td class="col-5"><?php echo htmlspecialchars($conso_display); ?></td>
                        <td class="col-5"><?php echo htmlspecialchars($flight['payload']); ?></td>
                        <td class="col-10"><?php echo htmlspecialchars($flight['heure_arrivee']); ?></td>
                        <td class="col-10"><?php echo htmlspecialchars(substr($flight['block_time'], 0, 8)); ?></td>
                        <td class="col-5"><?php echo htmlspecialchars($flight['note_du_vol']); ?></td>
                        <td class="col-8">
                            <?php
                                $recette = isset($flight['cout_vol']) && $flight['cout_vol'] !== null ? (float)$flight['cout_vol'] : 0.0;
                                $recette_formatee = number_format($recette, 2, ',', ' ');
                                if ($recette < 0) {
                                    echo '<span class="flash-error">' . $recette_formatee . ' €</span>';
                                } else {
                                    echo $recette_formatee . ' €';
                                }
                            ?>
                        </td>
                        <td class="col-8"><?php echo htmlspecialchars($flight['mission_libelle']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Popup modale pour détails du vol -->
        <div id="vol-modal" class="vol-modal">
            <div class="vol-modal-content">
                <span class="vol-modal-close" id="vol-modal-close">&times;</span>
                <h3><?= t('flights_modal_title') ?></h3>
                <div class="modal-grid">
                    <div class="modal-left">
                        <div id="vol-modal-body">
                            <!-- Les détails du vol seront injectés ici -->
                        </div>
                    </div>
                    <div class="modal-right">
                        <div id="map" class="map-div"></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<script>
var map;

// Synchronisation du scroll horizontal de l'en-tête (modèle tableau_vols.php)
document.addEventListener('DOMContentLoaded', function() {
    var scrollWrapper = document.querySelector('.table-scroll-wrapper');
    var headerTable = document.querySelector('.table-header-fixed');
    //if (scrollWrapper && headerTable) {
    //    scrollWrapper.addEventListener('scroll', function() {
    //        headerTable.scrollLeft = this.scrollLeft;
    //    });
    //}

    // Gestion du popup détails vol (modèle tableau_vols.php)
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
                // Initialize the map with OpenStreetMap
                if (window.map) {
                    window.map.remove();
                }
                // Utilise un id unique pour la div de la carte
                var mapDiv = document.getElementById('map');
                if (!mapDiv) return;

                // Centrage par défaut
                var lat = 48.8566, lng = 2.3522, zoom = 8;

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
                                    // Conversion en tableau de [lat, lng]
                                    const latlngs = trace.map(pt => [parseFloat(pt.Lat), parseFloat(pt.Long)]);
                                    // Affiche le tracé sur la carte
                                    L.polyline(latlngs, {color: 'blue', weight: 3}).addTo(window.map);
                                    // Ajuste la vue sur le tracé
                                    window.map.fitBounds(latlngs);
                                }
                            }else{
                                console.error('Aucun tracé GPS trouvé pour ce vol.');
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
                }
                //ajoute un marker pour les aéroports de départ et d'arrivée
                if (detailsObj.lat_depart && detailsObj.long_depart) {
                    L.marker([detailsObj.lat_depart, detailsObj.long_depart]).addTo(window.map)
                        .bindPopup('Départ: ' + detailsObj['name_depart'] + ' (' + detailsObj['Départ'] + ')');
                }
                if (detailsObj.lat_destination && detailsObj.long_destination) {
                    L.marker([detailsObj.lat_destination, detailsObj.long_destination]).addTo(window.map)
                        .bindPopup('Destination: ' + detailsObj['name_dest'] + ' (' + detailsObj['Destination'] + ')' );
                }
            })
            .catch((e) => {
                // Handle error if details cannot be loaded
                console.error('Erreur lors du chargement des détails du vol.'+ e);

                document.getElementById('vol-modal-body').innerHTML = "<p><?= t('flights_modal_error') ?></p>";
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
});
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>