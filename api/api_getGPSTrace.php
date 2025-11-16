<?php
// filepath: api/api_get_gpstrace.php

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../lang.php';

if (!isset($_GET['vol_id']) || !is_numeric($_GET['vol_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètre vol_id manquant ou invalide.']);
    exit;
}

$vol_id = (int)$_GET['vol_id'];

try {
    $stmt = $pdo->prepare("SELECT path FROM TRACE_GPS WHERE id = :vol_id LIMIT 1");
    $stmt->execute(['vol_id' => $vol_id]);
    $path = $stmt->fetchColumn();

    if ($path === false) {
        echo json_encode(['error' => t('api_error_no_gps')]);
    } else {
        echo json_encode(['path' => $path]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => t('cli_error_sql') . ' ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => t('api_error_gps_fetch')]);
}
?>
