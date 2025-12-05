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

function sendSummaryMail($subject, $body, $to = null, $maxRetries = 5) {
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
    
    $lastError = '';
    $delaySeconds = 3; // Delai initial entre les tentatives
    
    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
        try {
            if ($attempt > 1) {
                error_log("Retry $attempt/$maxRetries apres attente de {$delaySeconds}s");
            }
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : 'tls';
            $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
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
            
            $mail->send();
            if ($attempt > 1) {
                error_log("SUCCESS: Mail envoye avec succes apres $attempt tentatives (to: $to)");
            }
            return true;
        } catch (Exception $e) {
            $lastError = $e->getMessage();
            error_log("FAILED: Tentative $attempt/$maxRetries echouee (to: $to) : " . $lastError);
            
            // Si ce n'est pas la derniere tentative, attendre avant de reessayer
            if ($attempt < $maxRetries) {
                sleep($delaySeconds);
                // Augmenter progressivement le delai (backoff exponentiel)
                $delaySeconds = min($delaySeconds + 2, 10); // Max 10 secondes
            }
        }
    }
    
    // Toutes les tentatives ont echoue
    error_log("FATAL: Echec definitif envoi mail apres $maxRetries tentatives (to: $to) : $lastError");
    return $lastError;
}
