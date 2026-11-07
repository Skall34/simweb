<?php
/*
-------------------------------------------------------------
 Script : api_complete_reservation.php
 Emplacement : api/

 Description :
 API REST permettant de marquer une réservation comme terminée (vol fini).
 Met à jour le statut de la réservation et libère l'appareil.

 Paramètres GET/POST/JSON :
 - reservation_id : ID de la réservation (optionnel si pilote_id/callsign fourni)
 - pilote_id : ID du pilote (optionnel si reservation_id/callsign fourni)
 - callsign : Callsign du pilote (optionnel si reservation_id/pilote_id fourni)
 - immat : Immatriculation de l'appareil (optionnel, affine la recherche)
 - debug : Active le mode debug avec détails d'erreur (optionnel)

 Réponse JSON :
 - {status: 'ok', completed: true/false, consumed: true/false} : Résultat de l'opération
 - {status: 'error', message: '...'} : Erreur lors du traitement

 Utilisation :
 - Appelé à la fin d'un vol pour libérer la réservation et l'appareil.
 - Gère les transactions pour garantir la cohérence des données.

 Auteur :
 - Équipe de développement SimWeb
-------------------------------------------------------------
*/
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/log_func.php';
header('Content-Type: application/json');

// Accept both GET and POST (and allow JSON body via php://input where applicable)
$input = array_merge($_GET, $_POST);
$raw = file_get_contents('php://input');
if (empty($input) && $raw) {
    $decoded = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $input = $decoded;
    }
}

$reservation_id = isset($input['reservation_id']) ? intval($input['reservation_id']) : 0;
$pilote_id = isset($input['pilote_id']) ? intval($input['pilote_id']) : 0;
$callsign = isset($input['callsign']) ? trim($input['callsign']) : '';
$immat = isset($input['immat']) ? trim($input['immat']) : '';
$debug = !empty($input['debug']);

if ($debug) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

register_shutdown_function(function() use ($debug) {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        $out = ['status' => 'error', 'message' => 'Fatal error', 'error' => $err];
        if ($debug) $out['debug'] = $err;
        if (function_exists('logMsg')) logMsg('api_complete_reservation fatal: ' . json_encode($err), __DIR__ . '/../scripts/logs/api_complete_reservation.log');
        echo json_encode($out);
        @flush();
    }
});

if (!$reservation_id && !$pilote_id && !$callsign) {
    $msg = 'Missing reservation_id or pilote_id or callsign';
    if ($debug) {
        echo json_encode(['status' => 'error', 'message' => $msg, 'received' => $input]);
    } else {
        echo json_encode(['status' => 'error', 'message' => $msg]);
    }
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
                echo json_encode(['status'=>'ok','completed'=>false,'consumed'=>false,'message'=>'Pilot not found']);
                exit;
            }
            $pilote_id = $row['id'];
        }

        if ($immat) {
            $stmt = $pdo->prepare("SELECT * FROM RESERVATIONS WHERE pilote_id = ? AND immat = ? AND statut IN ('in_flight','reserved') LIMIT 1 FOR UPDATE");
            $stmt->execute([$pilote_id, $immat]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM RESERVATIONS WHERE pilote_id = ? AND statut IN ('in_flight','reserved') ORDER BY date_debut DESC LIMIT 1 FOR UPDATE");
            $stmt->execute([$pilote_id]);
        }
    }

    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reservation) {
        $pdo->rollBack();
        echo json_encode(['status'=>'ok','completed'=>false,'consumed'=>false]);
        exit;
    }

    // mark reservation completed
    $now = date('Y-m-d H:i:s');
    $update = $pdo->prepare("UPDATE RESERVATIONS SET statut = 'completed', date_fin = ? WHERE id = ?");
    $update->execute([$now, $reservation['id']]);

    // free the aircraft (if any)
    if (!empty($reservation['immat'])) {
        $updF = $pdo->prepare("UPDATE FLOTTE SET reservee = 0 WHERE immat = ?");
        $updF->execute([$reservation['immat']]);
    }

    $pdo->commit();

    // Return both 'completed' and 'consumed' for compatibility with ACARS clients
    echo json_encode(['status'=>'ok','completed'=>true,'consumed'=>true,'reservation_id'=>$reservation['id'],'immat'=>$reservation['immat']]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if (function_exists('logMsg')) logMsg('api_complete_reservation exception: ' . $e->getMessage(), __DIR__ . '/../scripts/logs/api_complete_reservation.log');
    if ($debug) {
        echo json_encode(['status'=>'error','message'=>$e->getMessage(),'trace'=>$e->getTrace()]);
    } else {
        echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    }
}
