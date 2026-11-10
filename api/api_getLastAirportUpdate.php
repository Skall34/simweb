<?php
/*
-------------------------------------------------------------
 Script : api_getLastAirportUpdate.php
 Emplacement : api/

 Description :
 API REST retournant la date de dernière mise à jour des données aéroports.
 Permet aux clients de vérifier si une synchronisation est nécessaire.

 Paramètres : Aucun

 Réponse JSON :
 - {success: true, last_update: 'YYYY-MM-DD HH:MM:SS'} : Date de dernière mise à jour
 - {success: false, error: 'Date non trouvée.'} : Aucune date disponible
 - {success: false, error: '...'} : Erreur serveur (HTTP 500)

 Utilisation :
 - Utilisé par SimAddon pour détecter les mises à jour de la base aéroports.
 - Permet une synchronisation incrémentale des données.

 Auteur :
 - Équipe de développement SimWeb
-------------------------------------------------------------
*/
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db_connect.php';

try {
    // Depuis la migration 014, la date est dans VARIABLES_CONFIG ; repli sur l'ancienne
    // table pour les bases pas encore migrées.
    $stmt = $pdo->query("SELECT valeur FROM VARIABLES_CONFIG WHERE nom = 'aeroports_last_admin_update'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$result) {
        try {
            $old = $pdo->query("SELECT last_update FROM AEROPORTS_LAST_ADMIN_UPDATE LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($old) {
                $result = ['valeur' => $old['last_update']];
            }
        } catch (Exception $e) {
            // table supprimée par la migration 014 : ignorer
        }
    }

    if ($result) {
        echo json_encode([
            'success' => true,
            'last_update' => $result['valeur']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Date non trouvée.'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération de la date de dernière mise à jour.'
    ]);
}
?>
