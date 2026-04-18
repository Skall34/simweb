<?php
/*
-------------------------------------------------------------
 Utilitaire : mail_utils.php
 Emplacement : includes/

 Description :
 Fournit une fonction centralisée pour envoyer un mail récapitulatif via PHPMailer.
 À utiliser en fin d'exécution des scripts pour notifier l'administrateur.
-------------------------------------------------------------
*/
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';

function sendSummaryMail($subject, $body, $to = null, $maxRetries = 10, $options = []) {
    // Verifier si les constantes SMTP sont definies
    if (!defined('SMTP_HOST') || !defined('SMTP_USERNAME') || !defined('SMTP_PASSWORD')) {
        error_log('Mail non envoye : configuration SMTP manquante (config.php non configure)');
        return 'Configuration SMTP manquante';
    }
    
    // Si aucun destinataire specifie, utiliser l'admin
    if ($to === null) {
        if (!defined('VA_ADMIN_EMAIL')) {
            error_log('Mail non envoye : VA_ADMIN_EMAIL non defini');
            return 'Email administrateur non configure';
        }
        $to = VA_ADMIN_EMAIL;
    }
    
    // Extraire les pièces jointes si présentes
    $attachments = isset($options['attachments']) ? $options['attachments'] : [];
    
    $lastError = '';
    $delaySeconds = 3; // Delai initial entre les tentatives
    $retryLog = []; // Historique des tentatives pour logging
    $startTime = microtime(true);
    
    // Options de retry
    $initialDelaySeconds = isset($options['initialDelaySeconds']) ? intval($options['initialDelaySeconds']) : 0;
    $baseDelaySeconds = isset($options['baseDelaySeconds']) ? intval($options['baseDelaySeconds']) : 3;
    $maxDelaySeconds = isset($options['maxDelaySeconds']) ? intval($options['maxDelaySeconds']) : 10;
    $jitterSeconds = isset($options['jitterSeconds']) ? intval($options['jitterSeconds']) : 3;
    $enableLock = isset($options['enableLock']) ? (bool)$options['enableLock'] : true;
    $lockFilePath = isset($options['lockFile']) ? $options['lockFile'] : (sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'simweb_mail.lock');
    $smtpTimeout = isset($options['smtpTimeout']) ? intval($options['smtpTimeout']) : 15;
    $lockHandle = null;
    
    // Appliquer le delai de base
    $delaySeconds = max($baseDelaySeconds, 1);
    
    // Attente initiale (ex: decaler l'envoi apres minuit)
    if ($initialDelaySeconds > 0) {
        $msg = "INFO: Attente initiale avant envoi: {$initialDelaySeconds}s";
        error_log($msg);
        $retryLog[] = $msg;
        sleep($initialDelaySeconds);
    }
    
    // Verrou fichier pour serialiser les envois
    if ($enableLock) {
        $lockHandle = @fopen($lockFilePath, 'c');
        if ($lockHandle) {
            if (@flock($lockHandle, LOCK_EX)) {
                $retryLog[] = "INFO: Verrou d'envoi acquis";
            } else {
                $msg = "WARN: Impossible d'obtenir le verrou d'envoi ($lockFilePath)";
                error_log($msg);
                $retryLog[] = $msg;
            }
        } else {
            $msg = "WARN: Ouverture du fichier de verrou echouee ($lockFilePath)";
            error_log($msg);
            $retryLog[] = $msg;
        }
    }
    
    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
        try {
            if ($attempt > 1) {
                $msg = "Retry $attempt/$maxRetries apres attente de {$delaySeconds}s";
                error_log($msg);
                $retryLog[] = $msg;
            }
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : 'tls';
            $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
            $mail->Timeout = $smtpTimeout;
            $mail->setFrom(
                defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : SMTP_USERNAME,
                defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Virtual Airline'
            );
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->CharSet = 'UTF-8';
            
            // Envoi en HTML uniquement si le corps contient des balises HTML
            if (preg_match('/<[^>]+>/', $body)) {
                $mail->isHTML(true);
                $mail->Encoding = 'base64';
                $mail->Body = $body;
                // AltBody sans balises ni espaces multiples
                $altBody = strip_tags($body);
                $altBody = preg_replace('/\s+/', ' ', $altBody);
                $mail->AltBody = trim($altBody);
            } else {
                // Texte brut : encodage quoted-printable comme l'API qui fonctionne
                $mail->isHTML(false);
                $mail->Encoding = 'quoted-printable';
                $mail->Body = $body;
            }
            
            // Ajouter les pièces jointes
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    if (is_array($attachment)) {
                        $mail->addAttachment($attachment['path'], $attachment['name'] ?? '');
                    } else {
                        $mail->addAttachment($attachment);
                    }
                }
            }
            
            $mail->send();
            if ($attempt > 1) {
                $msg = "SUCCESS: Mail envoye avec succes apres $attempt tentatives (to: $to)";
                error_log($msg);
                $retryLog[] = $msg;
            }
            
            // Liberer le verrou si acquis
            if ($lockHandle) {
                @flock($lockHandle, LOCK_UN);
                @fclose($lockHandle);
                $retryLog[] = "INFO: Verrou d'envoi libere";
            }
            
            // Retourner true avec les infos de retry si necessaire
            $totalElapsed = round((microtime(true) - $startTime), 3);
            if ($attempt > 1) {
                $retryLog[] = "SUMMARY: Tentatives={$attempt}, DureeTotale={$totalElapsed}s";
                return ['success' => true, 'attempts' => $attempt, 'log' => $retryLog, 'elapsed' => $totalElapsed];
            }
            return true;
        } catch (Exception $e) {
            $lastError = $e->getMessage();
            $msg = "FAILED: Tentative $attempt/$maxRetries echouee (to: $to) : " . $lastError;
            error_log($msg);
            $retryLog[] = $msg;
            
            // Si ce n'est pas la derniere tentative, attendre avant de reessayer
            if ($attempt < $maxRetries) {
                $jitter = ($jitterSeconds > 0) ? rand(0, $jitterSeconds) : 0;
                $wait = $delaySeconds + $jitter;
                $retryLog[] = "INFO: Attente avant retry: {$wait}s (base={$delaySeconds}s, jitter={$jitter}s)";
                sleep($wait);
                // Augmenter progressivement le delai (backoff progressif avec plafond)
                $delaySeconds = min($delaySeconds + 2, $maxDelaySeconds);
            }
        }
    }
    
    // Toutes les tentatives ont echoue
    $msg = "FATAL: Echec definitif envoi mail apres $maxRetries tentatives (to: $to) : $lastError";
    error_log($msg);
    $retryLog[] = $msg;
    $totalElapsed = round((microtime(true) - $startTime), 3);
    $retryLog[] = "SUMMARY: Tentatives={$maxRetries}, DureeTotale={$totalElapsed}s";
    
    // Liberer le verrou si acquis
    if ($lockHandle) {
        @flock($lockHandle, LOCK_UN);
        @fclose($lockHandle);
        $retryLog[] = "INFO: Verrou d'envoi libere";
    }
    
    return ['success' => false, 'error' => $lastError, 'attempts' => $maxRetries, 'log' => $retryLog, 'elapsed' => $totalElapsed];
}
