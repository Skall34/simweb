<?php
/*
-------------------------------------------------------------
 Script : api_getFlotte.php
 Emplacement : api/

 Description :
 API REST retournant la liste complète des appareils de la flotte avec leurs informations.
 Inclut l'immatriculation, catégorie, état de vol, dernier utilisateur, état et statut de réservation.

 Paramètres : mission (optionnel) = libellé d'une mission : ne retourne que les appareils actifs
             dont le type (FLEET_TYPE.type) est autorisé par la mission (MISSIONS.types_autorises,
             CSV de types ou '*' = tous ; mission inconnue = aucun filtre).
             Sans paramètre : toute la flotte active.

 Réponse JSON :
 - {success: true, immats: [{immat, categorie, en_vol, callsign, etat, reservee}, ...]} : Liste de la flotte
 - {success: false, error: '...'} : Erreur lors de la récupération (HTTP 500)

 Utilisation :
 - Utilisé pour afficher la flotte disponible lors de la réservation.
 - Appelé par SimAddon et les interfaces web pour sélectionner un appareil.
 - SimAddon (ACARS) : à chaque changement de mission, rappeler api_getFlotte.php?mission=<libellé>
   pour recharger la combo des immatriculations (uniquement les avions habilités pour la mission).
 - Ne retourne que les appareils actifs (actif = 1).

 Auteur :
 - Équipe de développement SimWeb
-------------------------------------------------------------
*/
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db_connect.php';

try {
    // Paramètre optionnel ?mission=<libellé> : ne renvoyer que les appareils actifs dont le type
    // (FLEET_TYPE.type) est autorisé par la mission (MISSIONS.types_autorises : CSV de types,
    // '*' = tous). Mission inconnue = aucun filtre (cohérent avec missionCompatibleAvecType()).
    // Le SimAddon rappelle cet endpoint à chaque changement de mission pour recharger la liste
    // des immatriculations autorisées (combo des avions habilités).
    $mission = isset($_GET['mission']) ? trim($_GET['mission']) : '';

    $sql = "SELECT 
    F.immat, 
    FT.type AS categorie,
    FT.fleet_type AS fleet_type,
    F.en_vol, 
    P.callsign, 
    F.etat,
    F.reservee,
    F.controle_a_faire,
    F.status,
    PRESA.callsign AS reserved_by
FROM 
    FLOTTE F
LEFT JOIN 
    FLEET_TYPE FT ON F.fleet_type = FT.id
LEFT JOIN 
    PILOTES P ON F.dernier_utilisateur = P.id
LEFT JOIN 
    RESERVATIONS R ON R.immat = F.immat AND R.statut IN ('reserved', 'in_flight')
LEFT JOIN 
    PILOTES PRESA ON R.pilote_id = PRESA.id
";
    if ($mission !== '') {
        // La mission restreint la flotte aux types d'appareil qu'elle autorise
        $sql .= "LEFT JOIN 
    MISSIONS M ON M.libelle = :mission
WHERE 
    F.actif = 1
    AND (
        M.libelle IS NULL
        OR M.types_autorises IS NULL
        OR M.types_autorises = '*'
        OR FIND_IN_SET(FT.type, M.types_autorises) > 0
    )
ORDER BY 
    F.immat;
";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['mission' => $mission]);
    } else {
        $sql .= "WHERE 
    F.actif = 1
ORDER BY 
    F.immat;
";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
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