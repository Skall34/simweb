<?php
/*
-------------------------------------------------------------
 Script : api_getMissions.php
 Emplacement : api/

 Description :
 API REST retournant la liste des missions actives disponibles.
 Ne retourne que les missions avec active != 0, triées alphabétiquement.

 Paramètres : Aucun

 Réponse JSON :
 - {success: true, missions: [{libelle, active}, ...]} : Liste des missions actives
 - {success: false, error: '...'} : Erreur lors de la récupération (HTTP 500)

 Utilisation :
 - Utilisé pour peupler les listes de sélection de missions.
 - Appelé par SimAddon et les interfaces web de réservation/vol.

 Auteur :
 - Équipe de développement SimWeb
-------------------------------------------------------------
*/
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db_connect.php';

try {
    $stmt = $pdo->prepare("SELECT libelle, active FROM MISSIONS WHERE active != 0 ORDER BY libelle");
    $stmt->execute();
    $missions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'missions' => $missions
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des missions.'
    ]);
}