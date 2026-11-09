<?php
/*
-------------------------------------------------------------
 Script : api_getMissions.php
 Emplacement : api/

 Description :
 API REST retournant la liste des missions actives disponibles.
 Ne retourne que les missions avec active != 0, triées alphabétiquement.

 Paramètres : type (optionnel) = catégorie FLEET_TYPE.type pour filtrer les missions compatibles

 Réponse JSON :
 - {success: true, missions: [{libelle, active, est_controle}, ...]} : Liste des missions actives (est_controle = 1 pour la mission de contrôle)
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
    // Paramètre optionnel ?type=Monomoteur|Bimoteur|Liner|Helico : ne renvoyer que les missions
    // compatibles avec cette catégorie d'appareil (MISSIONS.types_autorises, '*' = toutes)
    $type = isset($_GET['type']) ? trim($_GET['type']) : '';
    if ($type !== '') {
        $stmt = $pdo->prepare("SELECT libelle, active, est_controle FROM MISSIONS WHERE active != 0 AND (types_autorises = '*' OR FIND_IN_SET(:type, types_autorises) > 0) ORDER BY libelle");
        $stmt->execute(['type' => $type]);
    } else {
        $stmt = $pdo->prepare("SELECT libelle, active, est_controle FROM MISSIONS WHERE active != 0 ORDER BY libelle");
        $stmt->execute();
    }
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