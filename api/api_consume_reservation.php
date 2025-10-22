<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/api_auth.php';
header('Content-Type: application/json');

// Vérification optionnelle de la clé API (retrocompatible : si pas configurée, passthrough)
require_api_key($pdo);

// API to consume a reservation when ACARS starts the flight
// Accepts POST parameters: reservation_id OR pilote_id OR callsign
// Optional: immat, acars_cle

$input = $_POST;
$reservation_id = isset($input['reservation_id']) ? intval($input['reservation_id']) : 0;
$pilote_id = isset($input['pilote_id']) ? intval($input['pilote_id']) : 0;
$callsign = isset($input['callsign']) ? trim($input['callsign']) : '';
$immat = isset($input['immat']) ? trim($input['immat']) : '';
$acars_cle = isset($input['acars_cle']) ? trim($input['acars_cle']) : '';

if (!$reservation_id && !$pilote_id && !$callsign) {
    echo json_encode(['status'=>'error','message'=>'Missing reservation_id or pilote_id or callsign']);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($reservation_id) {
        $stmt = $pdo->prepare("SELECT * FROM RESERVATIONS WHERE id = ? FOR UPDATE");
        $stmt->execute([$reservation_id]);
    } else {
        if ($callsign && !$pilote_id) {
            $stmtPil = $pdo->prepare("SELECT id FROM PILOTES WHERE callsign = ?");
            $stmtPil->execute([$callsign]);
            $row = $stmtPil->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $pdo->rollBack();
                echo json_encode(['status'=>'ok','consumed'=>false,'message'=>'Pilot not found']);
                exit;
            }
            $pilote_id = $row['id'];
        }
        // select reservation for this pilote
        if ($immat) {
            $stmt = $pdo->prepare("SELECT * FROM RESERVATIONS WHERE pilote_id = ? AND immat = ? AND statut = 'reserved' LIMIT 1 FOR UPDATE");
            $stmt->execute([$pilote_id, $immat]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM RESERVATIONS WHERE pilote_id = ? AND statut = 'reserved' LIMIT 1 FOR UPDATE");
            $stmt->execute([$pilote_id]);
        }
    }

    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reservation) {
        $pdo->rollBack();
        echo json_encode(['status'=>'ok','consumed'=>false]);
        exit;
    }

    // mark reservation in_flight
    $now = date('Y-m-d H:i:s');
    $update = $pdo->prepare("UPDATE RESERVATIONS SET statut = 'in_flight', date_debut = ?, acars_cle = ? WHERE id = ?");
    $update->execute([$now, $acars_cle, $reservation['id']]);

    // ensure FLOTTE.reservee stays set (or set it) for the aircraft immat
    if (!empty($reservation['immat'])) {
        $updF = $pdo->prepare("UPDATE FLOTTE SET reservee = 1 WHERE immat = ?");
        $updF->execute([$reservation['immat']]);
    }

    $pdo->commit();

    echo json_encode(['status'=>'ok','consumed'=>true,'reservation_id'=>$reservation['id'],'immat'=>$reservation['immat']]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // log somewhere? For now return error
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
