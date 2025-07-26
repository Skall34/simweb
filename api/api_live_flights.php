<?php
require_once("../includes/db_connect.php");
// filepath: live_flights_json.php

header('Content-Type: application/json');

// Exemple : on suppose qu'il existe une table FLIGHTS_EN_COURS avec latitude, longitude, callsign
// Adapte la requête à ta structure réelle si besoin
try {
    $sql = "SELECT callsign, latitude, longitude, ICAO_Dep, ICAO_Arr FROM Live_FLIGHTS";
    $stmt = $pdo->query($sql);
    $flights = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Nettoyage des données (optionnel)
    $result = [];
    foreach ($flights as $flight) {
        if (
            isset($flight['latitude'], $flight['longitude'], $flight['callsign'],$flight['ICAO_Dep'],$flight['ICAO_Arr']) &&
            is_numeric($flight['latitude']) && is_numeric($flight['longitude'])
        ) {
            //si ICAO_Dep et ICAO_Arr sont présents, chercher les coordonnées des aéroports correspondants 
            // sinon, les laisser vides
            if (!isset($flight['ICAO_Dep']) || !isset($flight['ICAO_Arr'])) {
                $flight['ICAO_Dep'] = '';
                $flight['ICAO_Arr'] = '';
            }else{
                $stmtDep = $pdo->prepare("SELECT latitude_deg, longitude_deg FROM AEROPORTS WHERE ident = :icao");
                $stmtDep->execute(['icao' => $flight['ICAO_Dep']]);
                $depAirport = $stmtDep->fetch(PDO::FETCH_ASSOC);
                
                $stmtArr = $pdo->prepare("SELECT latitude_deg, longitude_deg FROM AEROPORTS WHERE ident = :icao");
                $stmtArr->execute(['icao' => $flight['ICAO_Arr']]);
                $arrAirport = $stmtArr->fetch(PDO::FETCH_ASSOC);
                
                if ($depAirport) {
                    $flight['lat_dep'] = floatval($depAirport['latitude_deg']);
                    $flight['long_dep'] = floatval($depAirport['longitude_deg']);
                } else {
                    $flight['lat_dep'] = null;
                    $flight['long_dep'] = null;
                }
                
                if ($arrAirport) {
                    $flight['lat_arr'] = floatval($arrAirport['latitude_deg']);
                    $flight['long_arr'] = floatval($arrAirport['longitude_deg']);
                } else {
                    $flight['lat_arr'] = null;
                    $flight['long_arr'] = null;
                }
            }

            // Ajouter les données formatées au résultat
            $result[] = [
                'callsign' => $flight['callsign'],
                'latitude' => floatval($flight['latitude']),
                'longitude' => floatval($flight['longitude']),
                'lat_dep' => $flight['lat_dep'] ?? null,
                'long_dep' => $flight['long_dep'] ?? null,
                'lat_arr' => $flight['lat_arr'] ?? null,
                'long_arr' => $flight['long_arr'] ?? null,
            ];
        }
    }
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération des vols en cours.']);
}