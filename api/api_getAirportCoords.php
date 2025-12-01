<?php
require_once __DIR__ . '/../includes/db_connect.php';

// Endpoint to fetch airport coordinates by ICAO
// Returns JSON: {ok: true, icao: string, lat: float, lon: float} or {ok: false, error: string}

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
