<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require __DIR__ . '/../includes/db_connect.php'; // $pdo
require __DIR__ . '/calcul_cout.php';

// Requête pour récupérer les vols + coût horaire + majoration_mission
$sql = "SELECT v.id, v.payload, v.heure_arrivee, v.heure_depart, v.note_du_vol, v.fuel_depart, v.fuel_arrivee,
               f.cout_horaire, v.mission_id, v.date_vol, 
               m.majoration_mission,
               (v.fuel_depart - v.fuel_arrivee) AS carburant_consomme
        FROM CARNET_DE_VOL_GENERAL v
        JOIN FLOTTE fl ON fl.id = v.appareil_id
        JOIN FLEET_TYPE f ON f.id = fl.fleet_type
        LEFT JOIN MISSIONS m ON m.id = v.mission_id ORDER BY v.date_vol DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$vols = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo '<table border="1" cellpadding="5" cellspacing="0">';
echo '<tr><th>ID Vol</th><th>Date</th><th>Payload (kg)</th><th>Temps vol</th><th>Carburant (L)</th><th>Note</th><th>Mission</th><th>Coef Mission</th><th>Coût horaire</th><th>Revenu Net Calculé (€)</th></tr>';

foreach ($vols as $vol) {
    $payload = $vol['payload'];
    // Calcul temps vol à partir des heures départ et arrivée
    $temps_vol = '00:00:00';
    if ($vol['heure_depart'] && $vol['heure_arrivee']) {
        $t1 = DateTime::createFromFormat('H:i:s', $vol['heure_depart']);
        $t2 = DateTime::createFromFormat('H:i:s', $vol['heure_arrivee']);
        if ($t1 && $t2) {
            $interval = $t1->diff($t2);
            $temps_vol = $interval->format('%H:%I:%S');
        }
    }

    $carburant = $vol['carburant_consomme'] ?? 0;
    $note = $vol['note_du_vol'] ?? 10;
    $majoration_mission = $vol['majoration_mission'] ?? 1;
    $cout_horaire = $vol['cout_horaire'] ?? 0;

    $revenu_net = calculerRevenuNetVol($payload, $temps_vol, $majoration_mission, $carburant, $note, $cout_horaire);

    echo '<tr>';
    echo '<td>' . htmlspecialchars($vol['id']) . '</td>';
    echo '<td>' . htmlspecialchars($vol['date_vol']) . '</td>';
    echo '<td>' . htmlspecialchars($payload) . '</td>';
    echo '<td>' . htmlspecialchars($temps_vol) . '</td>';
    echo '<td>' . htmlspecialchars($carburant) . '</td>';
    echo '<td>' . htmlspecialchars($note) . '</td>';
    echo '<td>' . htmlspecialchars($vol['mission_id']) . '</td>';
    echo '<td>' . htmlspecialchars($majoration_mission) . '</td>';
    echo '<td>' . htmlspecialchars($cout_horaire) . '</td>';
    echo '<td>' . htmlspecialchars(number_format($revenu_net, 2, ',', ' ')) . ' €</td>';
    echo '</tr>';
}
echo '</table>';
