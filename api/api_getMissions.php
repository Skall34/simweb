<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db_connect.php';

try {
    $stmt = $pdo->prepare("SELECT libelle FROM MISSIONS WHERE active = 1 ORDER BY libelle");
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