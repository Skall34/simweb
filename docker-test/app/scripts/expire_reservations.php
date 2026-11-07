<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/log_func.php';
require_once __DIR__ . '/../includes/mail_utils.php';

// This script releases reservations older than configured hours (default 24h)
try {
    $stmt = $pdo->query("SELECT valeur FROM VARIABLES_CONFIG WHERE nom = 'reservation_timeout_hours' LIMIT 1");
    $row = $stmt->fetch();
    $hours = $row ? intval($row['valeur']) : 24;

    $threshold = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

    // find reservations older than threshold and still reserved
    $sel = $pdo->prepare("SELECT * FROM RESERVATIONS WHERE statut = 'reserved' AND date_reservation <= ?");
    $sel->execute([$threshold]);
    $rows = $sel->fetchAll(PDO::FETCH_ASSOC);

    $expiredDetails = [];
    foreach ($rows as $r) {
        $pdo->beginTransaction();
        try {
            // mark reservation expired
            $upd = $pdo->prepare("UPDATE RESERVATIONS SET statut = 'expired' WHERE id = ?");
            $upd->execute([$r['id']]);

            // free aircraft immat if reserved
            if (!empty($r['immat'])) {
                $upd2 = $pdo->prepare("UPDATE FLOTTE SET reservee = 0 WHERE immat = ?");
                $upd2->execute([$r['immat']]);
            }

            $pdo->commit();
            logMsg("Expire reservation id={$r['id']} immat={$r['immat']}", __DIR__ . '/logs/expire_reservations.log');
            // collect detail for mail
            $expiredDetails[] = $r;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            logMsg("Failed to expire reservation id={$r['id']}: " . $e->getMessage(), __DIR__ . '/logs/expire_reservations.log');
        }
    }

    $expiredCount = count($rows);
    $timestamp = date('Y-m-d H:i:s');
    echo "Expired " . $expiredCount . " reservations (threshold: $threshold)\n";
    logMsg("[$timestamp] Script execution - Threshold: $threshold - Expired count: $expiredCount", __DIR__ . '/logs/expire_reservations.log');

    // Only send a summary email if at least one reservation was expired.
    if ($expiredCount <= 0) {
        echo "Aucune reservation expiree.\n";
        logMsg("[$timestamp] Aucune reservation expiree pour ce run", __DIR__ . '/logs/expire_reservations.log');
    } else {
        // Log detaille de chaque reservation expiree
        $expiredIds = array_map(function($x){ return $x['id']; }, $expiredDetails);
        $idsStr = implode(',', $expiredIds);
        
        // Log avec details pour chaque reservation
        foreach ($expiredDetails as $d) {
            $resId = $d['id'];
            $immat = $d['immat'] ?? 'N/A';
            $piloteId = $d['pilote_id'] ?? 'N/A';
            $ligneId = $d['ligne_id'] ?? 'N/A';
            $dateRes = $d['date_reservation'] ?? 'N/A';
            logMsg("[$timestamp] Expired: ID=$resId | Immat=$immat | Pilote=$piloteId | Ligne=$ligneId | Reserved=$dateRes", __DIR__ . '/logs/expire_reservations.log');
        }
        
        $debugMsg = "[$timestamp] Total expirees: $expiredCount | IDs: [$idsStr] | Rapport quotidien sera envoye a minuit.";
        echo $debugMsg . "\n";
        logMsg($debugMsg, __DIR__ . '/logs/expire_reservations.log');
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
