<?php
/*
-------------------------------------------------------------
 Script : api_getFretByIcao.php
 Emplacement : api/

 Description :
 API REST permettant de récupérer la quantité de fret disponible à un aéroport.

 Paramètres GET :
 - ICAO : Code ICAO de l'aéroport (obligatoire)

 Réponse JSON :
 - {success: true, ICAO: 'XXXX', fret: float} : Quantité de fret disponible
 - {success: false, error: 'Aéroport non trouvé.'} : ICAO invalide
 - {success: false, error: 'Paramètre ICAO manquant.'} : Paramètre manquant
 - {success: false, error: '...'} : Erreur serveur (HTTP 500)

 Utilisation :
 - Utilisé par SimAddon pour afficher le fret disponible au départ.
 - Permet aux pilotes de planifier leur chargement.

 Auteur :
 - Équipe de développement SimWeb
-------------------------------------------------------------
*/
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db_connect.php';

if (!isset($_GET['ICAO']) || empty($_GET['ICAO'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Paramètre ICAO manquant.'
    ]);
    exit;
}

$icao = $_GET['ICAO'];

try {
    $stmt = $pdo->prepare("SELECT fret FROM AEROPORTS WHERE ident = :icao");
    $stmt->execute(['icao' => $icao]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode([
            'success' => true,
            'ICAO' => $icao,
            'fret' => $result['fret']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Aéroport non trouvé.'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération du fret.'
    ]);
}