<?php
/*
-------------------------------------------------------------
 Script : api_getAirportCoords.php
 Emplacement : api/

 Description :
 API REST permettant de récupérer les coordonnées géographiques d'un aéroport par son code ICAO.

 Paramètres GET :
 - icao : Code ICAO de l'aéroport (obligatoire)

 Réponse JSON :
 - {ok: true, icao: 'XXXX', lat: float, lon: float} : Coordonnées trouvées
 - {ok: false, error: 'Airport not found'} : Aéroport non trouvé (HTTP 404)
 - {ok: false, error: 'ICAO code required'} : Paramètre manquant (HTTP 400)
 - {ok: false, error: '...'} : Erreur serveur (HTTP 500)

 Utilisation :
 - Utilisé pour afficher les aéroports sur une carte.
 - Appelé par les interfaces web et clients ACARS.

 Auteur :
 - Équipe de développement SimWeb
-------------------------------------------------------------
*/
require_once __DIR__ . '/../includes/db_connect.php';

$icao = strtoupper(trim($_GET['icao'] ?? ''));

if (empty($icao)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'ICAO code required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT ident, latitude_deg, longitude_deg FROM AEROPORTS WHERE ident = :icao LIMIT 1");
    $stmt->execute(['icao' => $icao]);
    $airport = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$airport) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Airport not found']);
        exit;
    }

    header('Content-Type: application/json');
    echo json_encode([
        'ok' => true,
        'icao' => $airport['ident'],
        'lat' => (float)$airport['latitude_deg'],
        'lon' => (float)$airport['longitude_deg']
    ]);
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
