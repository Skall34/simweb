<?php
/*
-------------------------------------------------------------
 Script : api_getAirports.php
 Emplacement : api/

 Description :
 API REST retournant la liste complète de tous les aéroports disponibles.
 Triés par code ICAO pour faciliter la recherche.

 Paramètres : Aucun

 Réponse JSON :
 - {success: true, aeroports: [{...}, ...]} : Liste des aéroports
 - {success: false, error: '...'} : Erreur lors de la récupération (HTTP 500)

 Utilisation :
 - Utilisé pour peupler les listes déroulantes de sélection d'aéroport.
 - Appelé au chargement des pages de réservation et création de vols.

 Auteur :
 - Équipe de développement SimWeb
-------------------------------------------------------------
*/
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db_connect.php';

try {
    $stmt = $pdo->prepare("SELECT * FROM AEROPORTS ORDER BY ident");
    $stmt->execute();
    $airports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'aeroports' => $airports
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des aéroports.'
    ]);
}