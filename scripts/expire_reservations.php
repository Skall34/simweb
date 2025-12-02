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
    echo "Expired " . $expiredCount . " reservations (threshold: $threshold)\n";

    // Envoi d'un mail récapitulatif à l'administrateur (texte brut comme l'API)
    $subject = "[SimWeb] Expiration de reservations - $expiredCount expirees";
    $body = "Bonjour,\r\n\r\nLe script d'expiration des reservations a ete execute.\r\n\r\n";
    $body .= "Nombre de reservations expirees : $expiredCount\r\n";
    $body .= "Seuil utilise : $threshold\r\n";
    
    if ($expiredCount > 0) {
        $body .= "\r\nDetails des reservations expirees :\r\n";
        $body .= "----------------------------------------\r\n";
        // prepare statements for lookup
        $stmtPilote = $pdo->prepare('SELECT callsign, nom, prenom FROM PILOTES WHERE id = ?');
        $stmtLigne = $pdo->prepare('SELECT icao_dep, icao_arr FROM LIGNES_REGULIERES WHERE id = ?');
        
        foreach ($expiredDetails as $d) {
            $immat = $d['immat'] ?? 'N/A';
            $piloteInfo = 'N/A';
            if (!empty($d['pilote_id'])) {
                $stmtPilote->execute([$d['pilote_id']]);
                $p = $stmtPilote->fetch(PDO::FETCH_ASSOC);
                if ($p) {
                    $cs = $p['callsign'] ?? ($p['nom'] . ' ' . $p['prenom']);
                    $piloteInfo = $d['pilote_id'] . ' / ' . $cs;
                } else {
                    $piloteInfo = $d['pilote_id'];
                }
            }
            $ligneLabel = 'N/A';
            if (!empty($d['ligne_id'])) {
                $stmtLigne->execute([$d['ligne_id']]);
                $lr = $stmtLigne->fetch(PDO::FETCH_ASSOC);
                if ($lr) $ligneLabel = $lr['icao_dep'] . ' -> ' . $lr['icao_arr'];
            }
            $dateResRaw = $d['date_reservation'] ?? '';
            try {
                $dtres = new DateTime($dateResRaw);
                $dateRes = $dtres->format('d/m/Y H:i');
            } catch (Exception $e) {
                $dateRes = $dateResRaw;
            }
            $body .= "- Immat: $immat | Pilote: $piloteInfo | Ligne: $ligneLabel | Date: $dateRes\r\n";
        }
    }
    
    $body .= "\r\n\r\nCeci est un message automatique.\r\n";

    // Only send a summary email if at least one reservation was expired.
    if ($expiredCount <= 0) {
        echo "Aucune réservation expirée — aucun mail envoyé.\n";
    // logMsg('Aucune réservation expirée; pas d\'envoi de mail récapitulatif.', __DIR__ . '/logs/expire_reservations.log');
    } else {
        // Diagnostic: log expired reservation IDs to help trace why mail may not be received
        $expiredIds = array_map(function($x){ return $x['id']; }, $expiredDetails);
        $idsStr = implode(',', $expiredIds);
        $debugMsg = 'Reservations expirées ids=[' . $idsStr . '] count=' . $expiredCount;
        echo $debugMsg . "\n";
    logMsg($debugMsg, __DIR__ . '/logs/expire_reservations.log');

        // Log the recipient address used by sendSummaryMail
        $recipient = VA_ADMIN_EMAIL;
        $recMsg = 'Envoi mail récapitulatif vers: ' . $recipient;
        echo $recMsg . "\n";
    logMsg($recMsg, __DIR__ . '/logs/expire_reservations.log');

        $mailResult = sendSummaryMail($subject, $body);
        if ($mailResult !== true) {
            logMsg('Erreur envoi mail expiration reservations: ' . print_r($mailResult, true), __DIR__ . '/logs/expire_reservations.log');
            echo "Erreur envoi mail: " . print_r($mailResult, true) . "\n";
        } else {
            logMsg('Mail récapitulatif envoyé (' . $expiredCount . ' expirées)', __DIR__ . '/logs/expire_reservations.log');
            echo "Mail récapitulatif envoyé.\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
