<?php
session_start();
include("includes/header.php");
require_once("includes/db_connect.php"); // Connexion PDO

// Inclusion du menu ici, juste après le header
if (!isset($_SESSION['user'])) {
    include("includes/menu_guest.php");
} else {
    include("includes/menu_logged.php");
}
?>

<main>
    <?php
    if (!isset($_SESSION['user'])) {
        ?>
        <div class="hero-layout">
            <!-- Texte à gauche -->
            <div class="hero-card">
                <h2><?= t('index_hero_title') ?></h2>                
                <p><?= t('index_hero_intro') ?></p>
                <p><?= t('index_hero_simaddon_1') ?></p>
                <p><?= t('index_hero_simaddon_2') ?></p>
                <p><?= t('index_hero_steps_intro') ?></p>
                <ul class="inline-ul-margin">
                    <li><?= t('index_hero_step1') ?></li>
                    <li><?= t('index_hero_step2') ?></li>
                    <li><?= t('index_hero_step3') ?></li>
                    <li><?= t('index_hero_step4') ?></li>
                    <li><?= t('index_hero_step5') ?></li>
                    <li><?= t('index_hero_step6') ?></li>
                </ul>
                <p><?= t('index_hero_maintenance') ?></p>
                <p><?= t('index_hero_grades') ?></p>
            </div>

            <!-- Image + Vols en cours à droite -->
            <div class="hero-right">
                <img src="assets/images/accueil.jpg" alt="SkyWings" class="hero-image">
                <section>
                    <h2 class="text-center no-top-margin"><?= t('index_liveflights_title') ?></h2>
                    <div id="live-flights-container">
                        <p><?= t('index_liveflights_loading') ?></p>
                    </div>
                </section>
            </div>
        </div>
        <?php
    } else {
        // Message de bienvenue personnalisé
        $callsign = isset($_SESSION['user']['callsign']) ? htmlspecialchars($_SESSION['user']['callsign']) : '';
        if ($callsign) {
            echo '<div class="welcome-msg">' . t('index_welcome') . ' ' . $callsign . ' 👋</div>';
        }

        // Message d'accueil administrable (3 lignes max)
        $stmtMsg = $pdo->prepare("SELECT valeur FROM VARIABLES_CONFIG WHERE nom = 'message_accueil'");
        $stmtMsg->execute();
        $message_accueil = $stmtMsg->fetchColumn();
        if ($message_accueil) {
            echo '<div class="notice-box">'
                . '<div class="notice-card">'
                . '<div style="font-weight:bold; color:#2a4d7a; margin-bottom:0.4em; text-align:center;">' . t('index_notice_title') . '</div>'
                . nl2br(htmlspecialchars($message_accueil))
                . '</div></div>';
        }
        // Vérifier si le pilote connecté a une réservation active (statut 'reserved')
        try {
            if (isset($_SESSION['user']['id'])) {
                $stmtRes = $pdo->prepare(
                    "SELECT r.*, lr.icao_dep, lr.icao_arr
                     FROM RESERVATIONS r
                     LEFT JOIN LIGNES_REGULIERES lr ON r.ligne_id = lr.id
                     WHERE r.pilote_id = ? AND r.statut = 'reserved'
                     ORDER BY r.date_reservation DESC
                     LIMIT 1"
                );
                $stmtRes->execute([$_SESSION['user']['id']]);
                $res = $stmtRes->fetch();
                if ($res) {
                    $immat = isset($res['immat']) ? htmlspecialchars($res['immat']) : '';
                    $dep = isset($res['icao_dep']) ? htmlspecialchars($res['icao_dep']) : '';
                    $arr = isset($res['icao_arr']) ? htmlspecialchars($res['icao_arr']) : '';
                    $date = isset($res['date_reservation']) ? date("d-m-Y H:i", strtotime($res['date_reservation'])) : '';
                    echo '<div class="reservation-box">'
                        . '<strong>' . t('index_reservation_active') . '</strong> '
                        . ($immat ? t('index_reservation_plane') . $immat . ' — ' : '')
                        . t('index_reservation_line') . ($dep ?: 'N/A') . ' → ' . ($arr ?: 'N/A') . ' — ' . t('index_reservation_date') . $date
                        . ' — <a href="pages/mon_compte.php">' . t('index_reservation_link') . '</a>'
                        . '</div>';
                }
            }
        } catch (PDOException $e) {
            // Ne pas bloquer la page d'accueil si la vérification échoue
        }
        try {
            $sql = "
                SELECT 
                    cdvg.date_vol,
                    p.callsign,
                    f.immat,
                    cdvg.depart,
                    cdvg.destination,
                    cdvg.heure_depart,
                    cdvg.heure_arrivee
                FROM CARNET_DE_VOL_GENERAL cdvg
                LEFT JOIN PILOTES p ON cdvg.pilote_id = p.id
                LEFT JOIN FLOTTE f ON cdvg.appareil_id = f.id
                ORDER BY cdvg.date_vol DESC, cdvg.heure_arrivee DESC
                LIMIT 10
            ";
            $stmt = $pdo->query($sql);
            $vols = $stmt->fetchAll();
        } catch (PDOException $e) {
            echo "<p>" . t('index_error_flights') . htmlspecialchars($e->getMessage()) . "</p>";
            $vols = [];
        }
    ?>


        <div class="content-row">
                <div class="flex-1">
                    <section>
                        <h2 class="no-top-margin"><?= t('index_liveflights_title') ?></h2>
                    <div id="live-flights-container">
                        <p><?= t('index_liveflights_loading') ?></p>
                    </div>
                </section>
            </div>
                <div class="col-fixed-320">
                    <img src="assets/images/PDF.jpg" alt="SkyWings" class="hero-image">
            </div>
        </div>

    <!-- Espace vertical avant le tableau -->
    <div class="spacer-xl"></div>
    <?php
    // Affichage de la balance commerciale sous le tableau
    // Fonction de formatage (copiée de finances.php)
    function format_chiffre($valeur) {
        if ($valeur === null) return '0';
        if (floor($valeur) == $valeur) {
            return number_format($valeur, 0, ',', ' ');
        } else {
            return number_format($valeur, 2, ',', ' ');
        }
    }
    // Récupère la balance financière depuis la table BALANCE_COMMERCIALE
    try {
        $sqlBalance = "SELECT balance_actuelle FROM BALANCE_COMMERCIALE";
        $stmtBalance = $pdo->query($sqlBalance);
        $balance = $stmtBalance->fetchColumn();
    } catch (PDOException $e) {
        $balance = null;
    }
    ?>

    <?php
    $balanceColor = ($balance >= 0) ? '#1abc9c' : '#e74c3c';
    ?>
    <div class="balance-panel">
        <span class="balance-label"><?= t('index_balance_label') ?></span>
        <span class="balance-value" style="color: <?= $balanceColor ?>;"><?= format_chiffre($balance) ?> €</span>
    </div>
    <!-- Titre du tableau -->
    <h2><?= t('index_last10_title') ?></h2>

    <!-- Tableau des vols -->
    <table class="table-skywings">
        <thead>
            <tr>
                <th><?= t('index_table_date') ?></th>
                <th><?= t('index_table_callsign') ?></th>
                <th><?= t('index_table_plane') ?></th>
                <th><?= t('index_table_dep') ?></th>
                <th><?= t('index_table_arr') ?></th>
                <th><?= t('index_table_duration') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vols as $vol): 
                $start = strtotime($vol['heure_depart']);
                $end = strtotime($vol['heure_arrivee']);
                $duration = $end && $start ? gmdate("H:i", $end - $start) : "N/A";
                $date_formatee = date("d-m-Y", strtotime($vol['date_vol']));
            ?>
                <tr>
                    <td><?= $date_formatee ?></td>
                    <td><?= htmlspecialchars($vol['callsign']) ?></td>
                    <td><?= htmlspecialchars($vol['immat']) ?></td>
                    <td><?= htmlspecialchars($vol['depart']) ?></td>
                    <td><?= htmlspecialchars($vol['destination']) ?></td>
                    <td><?= $duration ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php
// Vérifier s'il y a des vols en cours (Live_FLIGHTS)
$vols_en_cours = [];
try {
    $stmt = $pdo->query("SELECT callsign FROM Live_FLIGHTS LIMIT 1");
    $vols_en_cours = $stmt->fetchAll();
} catch (PDOException $e) {
    $vols_en_cours = [];
}
?>
<?php if (!empty($vols_en_cours)): ?>
    <!--affiche une carte openstreetmap avec les vols en cours-->
    <h2><?= t('index_map_title') ?></h2>
    <div id="map" class="map-div"></div>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script>
        // Initialisation de la carte
        var map = L.map('map').setView([48.8566, 2.3522], 5); // Vue centrée sur Paris

        // Ajout de la couche OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Stockage des marqueurs pour pouvoir les supprimer lors du rafraîchissement
        var flightMarkers = [];
        var flightSegments = [];

        // Fonction pour ajouter un marqueur
        function addMarker(lat, lon, callsign) {
            var marker = L.marker([lat, lon]).addTo(map)
                .bindPopup(callsign);
            flightMarkers.push(marker);
        }

        // Fonction pour supprimer tous les marqueurs existants
        function clearMarkers() {
            flightMarkers.forEach(function(marker) {
                map.removeLayer(marker);
            });
            flightMarkers = [];
        }

        function addSegment(latlngs, color = 'blue') {
            var segment = L.polyline(latlngs, { color: color }).addTo(map);
            flightSegments.push(segment);

        }

        function clearSegments() {
            flightSegments.forEach(function(segment) {
                map.removeLayer(segment);
            });
            flightSegments = [];
        }

        // Fonction pour charger et afficher les vols en cours sur la carte
        function updateLiveFlightsMap() {
            fetch('api/api_live_flights.php')
                .then(response => response.json())
                .then(data => {
                    clearMarkers();
                    clearSegments(); // Supprimer les segments précédents
                    data.forEach(flight => {
                        addMarker(flight.latitude, flight.longitude, flight.callsign);
                        // Si les aéroports de départ et d'arrivée sont disponibles, trace une ligne entre eux
                        if (flight.lat_dep && flight.long_dep && flight.lat_arr && flight.long_arr) {
                            // Tracer une ligne entre les aéroports de départ et d'arrivée
                            var latlngs = [
                                [flight.lat_dep, flight.long_dep],
                                [flight.lat_arr, flight.long_arr]
                            ];
                            addSegment(latlngs, 'red'); // Ligne rouge pour le segment de vol
                        }else {
                            console.log(`Aéroports non disponibles pour le vol ${flight.callsign}`);
                        }
                    });
                })
                .catch(error => console.error('Erreur lors du chargement des vols :', error));
        }

        // Chargement initial
        updateLiveFlightsMap();
    

        // Rafraîchissement toutes les 30 secondes
        setInterval(updateLiveFlightsMap, 30000);
    </script>
<?php endif; ?>
    <?php } ?>



    <script>
    function chargerVolsEnCours() {
        fetch('live_flights.php')
            .then(response => response.text())
            .then(html => {
                document.getElementById('live-flights-container').innerHTML = html;
            })
            .catch(error => {
                document.getElementById('live-flights-container').innerHTML = "<p>" + t('index_liveflights_error') + "</p>";
                console.error("Erreur AJAX :", error);
            });
    }

    // Chargement initial
    chargerVolsEnCours();

    // Rafraîchissement toutes les 20 secondes
    setInterval(chargerVolsEnCours, 20000);
    </script>

</main>

<?php include("includes/footer.php"); ?>
</body>
</html>
