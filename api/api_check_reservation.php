<?php
require_once __DIR__ . '/../includes/db_connect.php';
//require_once __DIR__ . '/../includes/api_auth.php';
// Enable display of errors temporarily for debugging (remove or guard in production)
header('Content-Type: application/json');


// Vérification optionnelle de la clé API (retrocompatible : si pas configurée, passthrough)
//require_api_key($pdo);

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
        // Remplacer pilote_id numérique par callsign lisible
        if (!empty($res['pilote_id'])) {
            $stmtP = $pdo->prepare('SELECT callsign FROM PILOTES WHERE id = ? LIMIT 1');
            $stmtP->execute([$res['pilote_id']]);
            $p = $stmtP->fetch(PDO::FETCH_ASSOC);
            $res['callsign'] = $p['callsign'] ?? null;
            // Optionnel: supprimer pilote_id si on veut seulement renvoyer le callsign
            unset($res['pilote_id']);
        }
        // Ajouter les informations de la ligne régulière (icao_dep / icao_arr)
        if (!empty($res['ligne_id'])) {
            $stmtL = $pdo->prepare('SELECT icao_dep, icao_arr FROM LIGNES_REGULIERES WHERE id = ? LIMIT 1');
            $stmtL->execute([$res['ligne_id']]);
            $l = $stmtL->fetch(PDO::FETCH_ASSOC);
            if ($l) {
                $res['icao_dep'] = $l['icao_dep'];
                $res['icao_arr'] = $l['icao_arr'];
            }
        }
        echo json_encode(['status'=>'ok','reserved'=>true,'reservation'=>$res]);
    } else {
        echo json_encode(['status'=>'ok','reserved'=>false]);
    }
} catch (Exception $e) {
    // Return detailed error for debugging (temporary)
    $payload = [
        'status' => 'error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ];
    echo json_encode($payload);
}
