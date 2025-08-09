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

// Gestion des tris

// Filtres
$immatFilter = $_GET['immat'] ?? '';
$missionFilter = $_GET['mission'] ?? '';

// Récupérer la liste des missions pour le filtre
$missionsList = [];
try {
    $stmtMissions = $pdo->query("SELECT DISTINCT libelle FROM MISSIONS WHERE libelle IS NOT NULL AND libelle <> '' ORDER BY libelle ASC");
    $missionsList = $stmtMissions->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Ignore erreur
}

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
WHERE c.pilote_id = :id_pilote";

$params = ['id_pilote' => $userId];
if ($immatFilter !== '') {
    $sql .= " AND f.immat LIKE :immat";
    $params['immat'] = '%' . $immatFilter . '%';
}
if ($missionFilter !== '') {
    $sql .= " AND m.libelle = :mission";
    $params['mission'] = $missionFilter;
}
$sql .= " ORDER BY c.date_vol DESC, c.heure_arrivee DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$flights = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>

<main>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <h2>Mes vols</h2>
    <form method="get" action="flights.php" style="margin-bottom:12px;">
        <label for="immat">Filtrer par immatriculation:</label>
        <input type="text" id="immat" name="immat" value="<?= htmlspecialchars($immatFilter) ?>" placeholder="Ex: F-XXXX">

        <label for="mission" style="margin-left:18px;">Filtrer par Mission:</label>
        <select id="mission" name="mission">
            <option value="">-- Toutes les missions --</option>
            <?php foreach ($missionsList as $m): ?>
                <option value="<?= htmlspecialchars($m) ?>" <?= ($missionFilter === $m) ? 'selected' : '' ?>><?= htmlspecialchars($m) ?></option>
            <?php endforeach; ?>
        </select>

        <button class="btn" type="submit">Filtrer</button>
        <button type="button" class="btn" style="margin-left:10px;" onclick="window.location.href='flights.php';">Réinitialiser</button>
    </form>
    <?php
        $nbResults = count($flights);
        if ($immatFilter !== '' || $missionFilter !== '') {
            echo '<p style="margin-bottom:8px;color:#1565c0;font-weight:bold;">' . $nbResults . ' vol' . ($nbResults > 1 ? 's' : '') . ' trouvé' . ($nbResults > 1 ? 's' : '') . ' avec ce filtre.</p>';
        }
    ?>
    <?php if (empty($flights)): ?>
        <p>Aucun vol trouvé pour ce pilote.</p>
    <?php else: ?>
        <div class="table-main-padding">
            <!-- Tableau d'en-tête fixe -->
            <table class="table-skywings">
                <thead class="table-skywings">
                    <tr class="table-skywings">
                        <th style="width:10%;">Date vol</th>
                        <th style="width:8%;">Immat</th>
                        <th style="width:5%;">Départ</th>
                        <th style="width:5%;">Dest.</th>
                        <th style="width:5%;">Fuel arrivée</th>
                        <th style="width:5%;">Conso</th>
                        <th style="width:5%;">Payload</th>
                        <th style="width:10%;">Heure arrivée</th>
                        <th style="width:10%;">Block time</th>
                        <th style="width:5%;">Note du vol</th>
                        <th style="width:8%;">Recette du vol</th>
                        <th style="width:8%;">Mission</th>
                    </tr>
                </thead>
                <tbody class="table-skywings">
                <?php foreach ($flights as $flight):
                    $pirep_complet = $flight['pirep_maintenance'];
                    $pirep_court = mb_strimwidth($pirep_complet, 0, 13, '...');
                    $date_formatee = date("d-m-Y", strtotime($flight['date_vol']));
                    $details = [
                        'ID vol' => $flight['vol_id'],
                        'Date vol' => $date_formatee,
                        'Immat' => $flight['immat'],
                        'Départ' => $flight['depart'],
                        'Destination' => $flight['destination'],
                        'Fuel départ' => $flight['fuel_depart'],
                        'Fuel arrivée' => $flight['fuel_arrivee'],
                        'Conso' => $flight['conso'],
                        'Payload' => $flight['payload'],
                        'Heure départ' => $flight['heure_depart'],
                        'Heure arrivée' => $flight['heure_arrivee'],
                        'Block time' => $flight['block_time'],
                        'Note du vol' => $flight['note_du_vol'],
                        'Mission' => $flight['mission_libelle'],
                        'Recette du vol' => number_format($flight['cout_vol'], 2) . ' €',
                        'Pirep' => $pirep_complet
                    ];
                    $details_json = htmlspecialchars(json_encode($details), ENT_QUOTES, 'UTF-8');
                ?>
                    <tr class="vol-row"  title="<?= htmlspecialchars($pirep_complet) ?>" data-details="<?= $details_json ?>">
                        <td style="width:10%;"><?= $date_formatee ?></td>
                        <td style="width:8%;"><?php echo htmlspecialchars($flight['immat']); ?></td>
                        <td style="width:5%;"><?php echo htmlspecialchars($flight['depart']); ?></td>
                        <td style="width:5%;"><?php echo htmlspecialchars($flight['destination']); ?></td>
                        <td style="width:5%;"><?php echo htmlspecialchars($flight['fuel_arrivee']); ?></td>
                        <td style="width:5%;"><?php echo htmlspecialchars($flight['conso']); ?></td>
                        <td style="width:5%;"><?php echo htmlspecialchars($flight['payload']); ?></td>
                        <td style="width:10%;"><?php echo htmlspecialchars($flight['heure_arrivee']); ?></td>
                        <td style="width:10%;"><?php echo htmlspecialchars(substr($flight['block_time'], 0, 8)); ?></td>
                        <td style="width:5%"><?php echo htmlspecialchars($flight['note_du_vol']); ?></td>
                        <td style="width:8%;"><?php echo number_format($flight['cout_vol'], 2) . ' €'; ?></td>
                        <td style="width:8%;"><?php echo htmlspecialchars($flight['mission_libelle']); ?></td>
                    </tr>
                <?php endforeach; ?>
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
                // Si tu as stocké la position du vol dans detailsObj, utilise-les
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
                                    // Conversion en tableau de [lat, lng]
                                    const latlngs = trace.map(pt => [parseFloat(pt.Lat), parseFloat(pt.Long)]);
                                    // Affiche le tracé sur la carte
                                    L.polyline(latlngs, {color: 'red', weight: 3}).addTo(window.map);
                                    // Ajuste la vue sur le tracé
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
                // Handle error if details cannot be loaded
                console.error('Erreur lors du chargement des détails du vol.'+ e);

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
});
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>