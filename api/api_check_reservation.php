<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/api_auth.php';
header('Content-Type: application/json');

// Vérification optionnelle de la clé API (retrocompatible : si pas configurée, passthrough)
require_api_key($pdo);

// API pour vérifier si un pilote a une réservation active
// Paramètres GET : pilote_id (ou callsign)
$pilote_id = isset($_GET['pilote_id']) ? intval($_GET['pilote_id']) : 0;
$callsign = isset($_GET['callsign']) ? trim($_GET['callsign']) : '';

if (!$pilote_id && !$callsign) {
    echo json_encode(['status' => 'error', 'message' => 'Missing pilote_id or callsign']);
    exit;
}

try {
    if ($pilote_id) {
        $stmt = $pdo->prepare("SELECT r.* FROM RESERVATIONS r WHERE r.pilote_id = ? AND r.statut = 'reserved' LIMIT 1");
        $stmt->execute([$pilote_id]);
    } else {
        $stmtPil = $pdo->prepare("SELECT id FROM PILOTES WHERE callsign = ?");
        $stmtPil->execute([$callsign]);
        $row = $stmtPil->fetch();
        if (!$row) {
            echo json_encode(['status'=>'ok','reserved'=>false]);
            exit;
        }
        $stmt = $pdo->prepare("SELECT r.* FROM RESERVATIONS r WHERE r.pilote_id = ? AND r.statut = 'reserved' LIMIT 1");
        $stmt->execute([$row['id']]);
    }
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($res) {
        echo json_encode(['status'=>'ok','reserved'=>true,'reservation'=>$res]);
    } else {
        echo json_encode(['status'=>'ok','reserved'=>false]);
    }
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
