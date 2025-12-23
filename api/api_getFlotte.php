<?php
/*
-------------------------------------------------------------
 Script : api_getFlotte.php
 Emplacement : api/

 Description :
 API REST retournant la liste complète des appareils de la flotte avec leurs informations.
 Inclut l'immatriculation, catégorie, état de vol, dernier utilisateur, état et statut de réservation.

 Paramètres : Aucun

 Réponse JSON :
 - {success: true, immats: [{immat, categorie, en_vol, callsign, etat, reservee}, ...]} : Liste de la flotte
 - {success: false, error: '...'} : Erreur lors de la récupération (HTTP 500)

 Utilisation :
 - Utilisé pour afficher la flotte disponible lors de la réservation.
 - Appelé par SimAddon et les interfaces web pour sélectionner un appareil.
 - Ne retourne que les appareils actifs (actif = 1).

 Auteur :
 - Équipe de développement SimWeb
-------------------------------------------------------------
*/
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db_connect.php';

try {
    $stmt = $pdo->prepare("SELECT 
    F.immat, 
    FT.type AS categorie, 
    F.en_vol, 
    P.callsign, 
    F.etat,
    F.reservee
FROM 
    FLOTTE F
LEFT JOIN 
    FLEET_TYPE FT ON F.fleet_type = FT.id
LEFT JOIN 
    PILOTES P ON F.dernier_utilisateur = P.id
WHERE 
    F.actif = 1
ORDER BY 
    F.immat;
");
    $stmt->execute();
    $immats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'immats' => $immats
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des immatriculations.'
    ]);
}