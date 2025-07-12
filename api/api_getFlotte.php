<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db_connect.php';

try {
    $stmt = $pdo->prepare("SELECT 
    F.immat, 
    F.type, 
    F.en_vol, 
    P.callsign, 
    F.etat
FROM 
    FLOTTE F
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