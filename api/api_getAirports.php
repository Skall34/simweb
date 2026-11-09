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
 - {success: true, aeroports: [{..., pistes: [{id, designation, longueur_m, surface}, ...],
     Piste, Longueur_de_piste, Type_de_piste}, ...]} : Liste des aéroports,
   chacun avec son tableau de pistes (longueurs en mètres).
   Les 3 clés historiques Piste / Longueur_de_piste / Type_de_piste sont conservées
   (reconstruites depuis PISTES, longueur remise en ft) pour compatibilité SimAddon :
   le JSON est identique avant/après la migration 002 (drop des anciennes colonnes).
 - {success: false, error: '...'} : Erreur lors de la récupération (HTTP 500)

 Utilisation :
 - Utilisé pour peupler les listes déroulantes de sélection d'aéroport.
 - Appelé au chargement des pages de réservation et création de vols.
 - Consommé par le client SimAddon (liste des aéroports, cache JSON).

 Auteur :
 - Équipe de développement SimWeb
-------------------------------------------------------------
*/
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db_connect.php';

try {
    // Colonnes explicites (et non a.*) pour que le JSON soit identique avant/après
    // la migration 002 : les 3 anciennes colonnes sont recalculées depuis PISTES.
    $stmt = $pdo->prepare("
        SELECT a.ident, a.type_aeroport, a.name, a.municipality,
               a.latitude_deg, a.longitude_deg, a.elevation_ft,
               a.Observations, a.wikipedia_link, a.fret,
               COALESCE((SELECT JSON_ARRAYAGG(JSON_OBJECT('id', p.id, 'designation', p.designation, 'longueur_m', p.longueur_m, 'surface', p.surface))
                         FROM PISTES p WHERE p.aeroport_ident = a.ident), JSON_ARRAY()) AS pistes,
               (SELECT GROUP_CONCAT(p.designation ORDER BY p.id SEPARATOR ' ')
                FROM PISTES p WHERE p.aeroport_ident = a.ident) AS `Piste`,
               (SELECT GROUP_CONCAT(CAST(ROUND(p.longueur_m / 0.3048, 0) AS UNSIGNED) ORDER BY p.id SEPARATOR '/')
                FROM PISTES p WHERE p.aeroport_ident = a.ident AND p.longueur_m IS NOT NULL) AS `Longueur_de_piste`,
               (SELECT GROUP_CONCAT(p.surface ORDER BY p.id SEPARATOR '/')
                FROM PISTES p WHERE p.aeroport_ident = a.ident AND p.surface IS NOT NULL) AS `Type_de_piste`
        FROM AEROPORTS a
        ORDER BY a.ident
    ");
    $stmt->execute();
    $airports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($airports as &$a) {
        $a['pistes'] = json_decode($a['pistes'], true) ?: [];
    }
    unset($a);

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