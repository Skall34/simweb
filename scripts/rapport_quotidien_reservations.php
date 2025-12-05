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
    
    // Compter les reservations qui sont passees de 'reserved' a 'expired' hier
    // On cherche celles qui ont le statut 'expired' et dont la date_reservation est dans la periode
    // (car quand elles expirent, on ne modifie que le statut, pas la date)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM RESERVATIONS 
        WHERE statut = 'expired' 
        AND DATE(date_reservation) BETWEEN DATE_SUB(?, INTERVAL 2 DAY) AND ?
    ");
    $stmt->execute([$today, $today]);
    $result = $stmt->fetch();
    $expiredCount = $result['count'] ?? 0;
    
    logMsg("[$timestamp] Nombre de reservations expirees trouvees: $expiredCount", __DIR__ . '/logs/expire_reservations.log');
    
    if ($expiredCount > 0) {
        logMsg("[$timestamp] Preparation mail recapitulatif pour $expiredCount reservations", __DIR__ . '/logs/expire_reservations.log');
        $subject = "[SimWeb] Rapport quotidien reservations - " . date('d/m/Y');
        $body = "Bonjour,\r\n\r\n";
        $body .= "Rapport des reservations expirees pour la periode du " . date('d/m/Y', strtotime($yesterday)) . " au " . date('d/m/Y') . ".\r\n\r\n";
        $body .= "Nombre total de reservations expirees : " . $expiredCount . "\r\n\r\n";
        $body .= "Les reservations ont ete automatiquement liberees.\r\n";
        $body .= "\r\nCeci est un message automatique.\r\n";
        
        // Nettoyer avec iconv
        $subject = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $subject);
        $body = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $body);
        
        logMsg("[$timestamp] Envoi du rapport quotidien a " . VA_ADMIN_EMAIL, __DIR__ . '/logs/expire_reservations.log');
        $mailResult = sendSummaryMail($subject, $body);
        if ($mailResult !== true) {
            logMsg("[$timestamp] ERREUR: Echec envoi rapport quotidien apres retries: $mailResult", __DIR__ . '/logs/expire_reservations.log');
            echo "Erreur envoi rapport: $mailResult\n";
        } else {
            logMsg("[$timestamp] SUCCES: Rapport quotidien envoye ($expiredCount reservations)", __DIR__ . '/logs/expire_reservations.log');
            echo "Rapport quotidien envoye avec succes.\n";
        }
    } else {
        echo "Aucune reservation expiree hier, pas de rapport envoye.\n";
        logMsg("[$timestamp] Aucune reservation expiree hier, pas de rapport envoye", __DIR__ . '/logs/expire_reservations.log');
    }
    
    logMsg("[$timestamp] === FIN RAPPORT QUOTIDIEN RESERVATIONS ===", __DIR__ . '/logs/expire_reservations.log');
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    logMsg("[$timestamp] EXCEPTION rapport quotidien: " . $e->getMessage(), __DIR__ . '/logs/expire_reservations.log');
}
