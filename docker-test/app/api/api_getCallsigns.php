<?php
/*
-------------------------------------------------------------
 Script : api_getCallsigns.php
 Emplacement : api/

 Description :
 API REST retournant la liste de tous les callsigns (indicatifs) des pilotes.
 Triés alphabétiquement pour faciliter la recherche.

 Paramètres : Aucun

 Réponse JSON :
 - {success: true, callsigns: [{callsign: '...'}, ...]} : Liste des callsigns
 - {success: false, error: '...'} : Erreur lors de la récupération (HTTP 500)

 Utilisation :
 - Utilisé pour peupler les listes de sélection de pilotes.
 - Appelé par les interfaces d'administration et de réservation.

 Auteur :
 - Équipe de développement SimWeb
-------------------------------------------------------------
*/
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db_connect.php';

try {
    $stmt = $pdo->prepare("SELECT callsign FROM PILOTES ORDER BY callsign");
    $stmt->execute();
    $callsigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'callsigns' => $callsigns
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des callsigns.'
    ]);
}