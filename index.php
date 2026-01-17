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
                <img src="assets/images/accueil.jpg" alt="<?= VA_NAME ?>" class="hero-image">
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

        // Checklist pour le compte ADM0001
        if ($callsign === 'ADM0001') {
            // Vérifier les étapes complétées
            $hasFleetType = $pdo->query('SELECT COUNT(*) FROM FLEET_TYPE')->fetchColumn() > 0;
            $hasFleet = $pdo->query('SELECT COUNT(*) FROM FLOTTE')->fetchColumn() > 0;
            $hasOtherAdmin = $pdo->query('SELECT COUNT(*) FROM PILOTES WHERE admin = 1 AND callsign != "ADM0001"')->fetchColumn() > 0;

            $allDone = $hasFleetType && $hasFleet && $hasOtherAdmin;


            
            if ($allDone) {
                echo '<div style="background:#d4edda;border:1px solid #c3e6cb;border-radius:8px;padding:15px;margin:15px 0;text-align:center;color:#155724;">';
                echo '<strong>✅ ' . t('index_adm_setup_complete') . '</strong><br>';
                echo t('index_adm_setup_delete_adm');
                echo '</div>';
            } else {
                echo '<ul style="list-style:none;padding:0;">';
                echo '<div style="background:#fff3cd;border:2px solid #ff8c00;border-radius:12px;padding:20px;margin:20px auto;max-width:800px;box-shadow:0 4px 8px rgba(0,0,0,0.1);">';
                echo '<h3 style="color:#856404;margin-top:0;text-align:center;">⚠️ ' . t('index_adm_setup_title') . '</h3>';
                
                echo '<p style="text-align:center;margin-bottom:20px;">' . t('index_adm_setup_intro') . '</p>';                
                // Tâche 1: Créer un autre compte admin
                echo '<li style="padding:10px;margin:8px 0;border-left:4px solid ' . ($hasOtherAdmin ? '#28a745' : '#ffc107') . ';background:' . ($hasOtherAdmin ? '#f1f9f4' : '#fffbf0') . ';border-radius:4px;">';
                echo '<span style="font-size:20px;margin-right:10px;">' . ($hasOtherAdmin ? '✓' : '○') . '</span>';
                echo '<strong>' . t('index_adm_task1_title') . '</strong><br>';
                echo '<span style="color:#666;">' . t('index_adm_task1_desc') . '</span> ';
                echo '<a href="admin/admin_gestion_pilotes.php" style="color:#0066cc;text-decoration:underline;">' . t('index_adm_task1_link') . '</a>';
                echo '</li>';
                
                // Tâche 2: Créer un fleet type
                echo '<li style="padding:10px;margin:8px 0;border-left:4px solid ' . ($hasFleetType ? '#28a745' : '#ffc107') . ';background:' . ($hasFleetType ? '#f1f9f4' : '#fffbf0') . ';border-radius:4px;">';
                echo '<span style="font-size:20px;margin-right:10px;">' . ($hasFleetType ? '✓' : '○') . '</span>';
                echo '<strong>' . t('index_adm_task2_title') . '</strong><br>';
                echo '<span style="color:#666;">' . t('index_adm_task2_desc') . '</span> ';
                echo '<a href="admin/admin_fleet_type.php" style="color:#0066cc;text-decoration:underline;">' . t('index_adm_task2_link') . '</a>';
                echo '</li>';
                
                // Tâche 3: Acheter un appareil
                echo '<li style="padding:10px;margin:8px 0;border-left:4px solid ' . ($hasFleet ? '#28a745' : '#ffc107') . ';background:' . ($hasFleet ? '#f1f9f4' : '#fffbf0') . ';border-radius:4px;">';
                echo '<span style="font-size:20px;margin-right:10px;">' . ($hasFleet ? '✓' : '○') . '</span>';
                echo '<strong>' . t('index_adm_task3_title') . '</strong><br>';
                echo '<span style="color:#666;">' . t('index_adm_task3_desc') . '</span> ';
                echo '<a href="admin/admin_flotte.php" style="color:#0066cc;text-decoration:underline;">' . t('index_adm_task3_link') . '</a>';
                echo '</li>';
                
                echo '</ul>';
            }
            
            echo '</div>';
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
                    <img src="assets/images/PDF.jpg" alt="<?= VA_NAME ?>" class="hero-image">
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
        
        // Récupérer l'évolution de la balance sur les 30 derniers jours
        $sqlEvolution = "
            SELECT DATE(date) as jour,
                   (SELECT IFNULL(SUM(montant), 0) FROM finances_recettes WHERE DATE(date) <= DATE(ops.date)) - 
                   (SELECT IFNULL(SUM(montant), 0) FROM finances_depenses WHERE DATE(date) <= DATE(ops.date)) as balance
            FROM (
                SELECT DISTINCT DATE(date) as date
                FROM (
                    SELECT date FROM finances_recettes WHERE DATE(date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    UNION ALL
                    SELECT date FROM finances_depenses WHERE DATE(date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                ) t
            ) ops
            ORDER BY jour ASC
        ";
        $stmtEvolution = $pdo->query($sqlEvolution);
        $evolution = $stmtEvolution->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $balance = null;
        $evolution = [];
    }
    ?>

    <?php
    $balanceColor = ($balance >= 0) ? '#1abc9c' : '#e74c3c';
    ?>
    <div class="balance-panel">
        <div class="balance-info">
            <span class="balance-label"><?= t('index_balance_label') ?></span>
            <span class="balance-value" style="color: <?= $balanceColor ?>;"><?= format_chiffre($balance) ?> €</span>
        </div>
        <?php if (!empty($evolution)): ?>
        <div class="balance-sparkline">
            <canvas id="balanceSparkline" width="400" height="120"></canvas>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
        const sparkCtx = document.getElementById('balanceSparkline').getContext('2d');
        const sparkData = <?= json_encode(array_column($evolution, 'balance')) ?>;
        const sparkDates = <?= json_encode(array_column($evolution, 'jour')) ?>;
        const sparkColor = '<?= $balanceColor ?>';
        
        new Chart(sparkCtx, {
            type: 'line',
            data: {
                labels: sparkDates,
                datasets: [{
                    data: sparkData,
                    borderColor: sparkColor,
                    backgroundColor: sparkColor + '15',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 0,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: false,
                plugins: { 
                    legend: { display: false }, 
                    tooltip: { enabled: true }
                },
                scales: {
                    x: { 
                        display: true,
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45,
                            callback: function(value, index) {
                                const date = this.getLabelForValue(value);
                                return new Date(date).toLocaleDateString('fr-FR', { 
                                    day: '2-digit', 
                                    month: '2-digit' 
                                });
                            }
                        }
                    },
                    y: { 
                        display: true,
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('fr-FR', { 
                                    style: 'currency', 
                                    currency: 'EUR',
                                    maximumFractionDigits: 0
                                }).format(value);
                            }
                        }
                    }
                }
            }
        });
        </script>
        <?php endif; ?>
    </div>

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
