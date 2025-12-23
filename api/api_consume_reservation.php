<?php
/*
-------------------------------------------------------------
 Script : api_consume_reservation.php
 Emplacement : api/

 Description :
 API REST permettant de consommer une réservation au démarrage d'un vol ACARS.
 Change le statut de 'reserved' à 'in_flight' et marque l'appareil comme réservé.

 Paramètres GET/POST/JSON :
 - reservation_id : ID de la réservation (optionnel si pilote_id/callsign fourni)
 - pilote_id : ID du pilote (optionnel si reservation_id/callsign fourni)
 - callsign : Callsign du pilote (optionnel si reservation_id/pilote_id fourni)
 - immat : Immatriculation de l'appareil (optionnel, affine la recherche)
 - acars_cle : Clé ACARS du vol (optionnel, stockée pour traçabilité)

 Réponse JSON :
 - {status: 'ok', consumed: true, reservation: {...}} : Réservation consommée avec succès
 - {status: 'ok', consumed: false} : Aucune réservation trouvée
 - {status: 'error', message: '...'} : Erreur lors du traitement

 Utilisation :
 - Appelé au démarrage d'un vol ACARS pour activer la réservation.
 - Gère les transactions pour éviter les doubles réservations.

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
