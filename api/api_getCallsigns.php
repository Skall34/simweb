<?php
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