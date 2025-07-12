<?php
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