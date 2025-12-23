<?php
/*
-------------------------------------------------------------
 Script : api_live_flights.php
 Emplacement : api/

 Description :
 API REST retournant les positions en temps réel des vols en cours.
 Récupère les coordonnées des avions et des aéroports de départ/arrivée pour affichage sur carte.

 Paramètres : Aucun

 Réponse JSON :
 - [{callsign, latitude, longitude, lat_dep, long_dep, lat_arr, long_arr}, ...] : Tableau des vols actifs
 - {error: '...'} : Erreur lors de la récupération (HTTP 500)

 Fonctionnalités :
 - Filtre les vols avec coordonnées valides (latitude/longitude numériques).
 - Enrichit les données avec les coordonnées des aéroports de départ et d'arrivée.
 - Gère les cas où les aéroports ne sont pas trouvés (null).

 Utilisation :
 - Utilisé pour afficher les vols en direct sur la carte des vols actifs.
 - Appelé en polling régulier depuis live_flights.php.

 Auteur :
 - Équipe de développement SimWeb
-------------------------------------------------------------
*/
require_once("../includes/db_connect.php");
require_once("../lang.php");
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
    echo json_encode(['error' => t('api_error_live_flights')]);
}