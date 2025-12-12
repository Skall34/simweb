<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/log_func.php';
require_once __DIR__ . '/../includes/mail_utils.php';

// Script pour envoyer un rapport quotidien des reservations expirees
// A executer 1 fois par jour (ex: 8h du matin)

$timestamp = date('Y-m-d H:i:s');
logMsg("[$timestamp] === DEBUT RAPPORT QUOTIDIEN RESERVATIONS ===", __DIR__ . '/logs/expire_reservations.log');

try {
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $today = date('Y-m-d');
    
    logMsg("[$timestamp] Periode analysee: $yesterday au $today", __DIR__ . '/logs/expire_reservations.log');
    
    // Recuperer les details des reservations expirees
    $stmt = $pdo->prepare("
        SELECT r.id, r.immat, r.date_reservation, r.pilote_id, r.ligne_id,
               p.callsign, p.nom, p.prenom,
               l.icao_dep, l.icao_arr
        FROM RESERVATIONS r
        LEFT JOIN PILOTES p ON r.pilote_id = p.id
        LEFT JOIN LIGNES_REGULIERES l ON r.ligne_id = l.id
        WHERE r.statut = 'expired' 
        AND DATE(r.date_reservation) BETWEEN DATE_SUB(?, INTERVAL 2 DAY) AND ?
        ORDER BY r.date_reservation DESC
    ");
    $stmt->execute([$today, $today]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $expiredCount = count($reservations);
    
    logMsg("[$timestamp] Nombre de reservations expirees trouvees: $expiredCount", __DIR__ . '/logs/expire_reservations.log');
    
    // Preparer le mail (meme si 0 reservations pour valider les envois)
    logMsg("[$timestamp] Preparation mail recapitulatif pour $expiredCount reservations", __DIR__ . '/logs/expire_reservations.log');
    $subject = "[SimWeb] Rapport quotidien reservations - " . date('d/m/Y') . " ($expiredCount expirees)";
    $body = "Bonjour,\r\n\r\n";
    $body .= "Rapport des reservations expirees pour la periode du " . date('d/m/Y', strtotime($yesterday)) . " au " . date('d/m/Y') . ".\r\n\r\n";
    $body .= "Nombre total de reservations expirees : " . $expiredCount . "\r\n\r\n";
    
    if ($expiredCount > 0) {
        // Ajouter les details de chaque reservation
        $body .= "Details des reservations expirees :\r\n";
        $body .= "=====================================\r\n\r\n";
        
        foreach ($reservations as $res) {
            $piloteInfo = $res['callsign'] ?? ($res['nom'] . ' ' . $res['prenom']);
            $piloteInfo = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $piloteInfo);
            $immat = $res['immat'] ?? 'N/A';
            $immat = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $immat);
            $ligne = ($res['icao_dep'] ?? 'N/A') . ' -> ' . ($res['icao_arr'] ?? 'N/A');
            $dateRes = $res['date_reservation'] ? date('d/m/Y H:i', strtotime($res['date_reservation'])) : 'N/A';
            
            $body .= "Reservation #" . $res['id'] . "\r\n";
            $body .= "  Pilote      : " . $piloteInfo . "\r\n";
            $body .= "  Ligne       : " . $ligne . "\r\n";
            $body .= "  Appareil    : " . $immat . "\r\n";
            $body .= "  Reserve le  : " . $dateRes . "\r\n";
            $body .= "\r\n";
        }
        
        $body .= "Les reservations ont ete automatiquement liberees.\r\n";
    } else {
        $body .= "Aucune reservation n'a expire durant cette periode.\r\n";
    }
    
    $body .= "\r\nCeci est un message automatique.\r\n";
    
    // Nettoyer avec iconv
    $subject = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $subject);
    $body = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $body);
    
    logMsg("[$timestamp] Envoi du rapport quotidien a " . VA_ADMIN_EMAIL, __DIR__ . '/logs/expire_reservations.log');
    // Ajouter un delai initial de 7 minutes et jitter pour eviter les pics
    $mailResult = sendSummaryMail($subject, $body, null, 5, [
        'initialDelaySeconds' => 420, // 7 minutes
        'baseDelaySeconds' => 3,
        'maxDelaySeconds' => 10,
        'jitterSeconds' => 3,
        'enableLock' => true,
    ]);
    if (is_array($mailResult)) {
        // Nouveau format avec retry log
        if ($mailResult['success']) {
            logMsg("[$timestamp] SUCCES: Rapport quotidien envoye ($expiredCount reservations) apres {$mailResult['attempts']} tentative(s)", __DIR__ . '/logs/expire_reservations.log');
            echo "Rapport quotidien envoye avec succes.\n";
        } else {
            logMsg("[$timestamp] ERREUR: Echec envoi rapport quotidien apres {$mailResult['attempts']} tentatives: {$mailResult['error']}", __DIR__ . '/logs/expire_reservations.log');
            echo "Erreur envoi rapport: {$mailResult['error']}\n";
        }
        // Enregistrer tous les logs de retry
        foreach ($mailResult['log'] as $logLine) {
            logMsg("[$timestamp] RETRY: $logLine", __DIR__ . '/logs/expire_reservations.log');
        }
    } elseif ($mailResult !== true) {
        // Ancien format (string = erreur)
        logMsg("[$timestamp] ERREUR: Echec envoi rapport quotidien apres retries: $mailResult", __DIR__ . '/logs/expire_reservations.log');
        echo "Erreur envoi rapport: $mailResult\n";
    } else {
        // Ancien format (true = succes premier coup)
        logMsg("[$timestamp] SUCCES: Rapport quotidien envoye ($expiredCount reservations)", __DIR__ . '/logs/expire_reservations.log');
        echo "Rapport quotidien envoye avec succes.\n";
    }
    
    logMsg("[$timestamp] === FIN RAPPORT QUOTIDIEN RESERVATIONS ===", __DIR__ . '/logs/expire_reservations.log');
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    logMsg("[$timestamp] EXCEPTION rapport quotidien: " . $e->getMessage(), __DIR__ . '/logs/expire_reservations.log');
}
