<?php
require_once("../includes/db_connect.php");
// filepath: live_flights_json.php

header('Content-Type: application/json');

// Exemple : on suppose qu'il existe une table FLIGHTS_EN_COURS avec latitude, longitude, callsign
// Adapte la requête à ta structure réelle si besoin
try {
    $sql = "SELECT callsign, latitude, longitude FROM Live_FLIGHTS";
    $stmt = $pdo->query($sql);
    $flights = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Nettoyage des données (optionnel)
    $result = [];
    foreach ($flights as $flight) {
        if (
            isset($flight['latitude'], $flight['longitude'], $flight['callsign']) &&
            is_numeric($flight['latitude']) && is_numeric($flight['longitude'])
        ) {
            $result[] = [
                'callsign' => $flight['callsign'],
                'latitude' => floatval($flight['latitude']),
                'longitude' => floatval($flight['longitude'])
            ];
        }
    }
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération des vols en cours.']);
}