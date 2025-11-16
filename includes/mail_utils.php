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

function sendSummaryMail($subject, $body, $to = null) {
    // Si aucun destinataire spécifié, utiliser l'admin
    if ($to === null) {
        $to = VA_ADMIN_EMAIL;
    }
    
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->CharSet = 'UTF-8';
        // Envoi en HTML uniquement si le corps contient des balises HTML
        if (preg_match('/<[^>]+>/', $body)) {
            $mail->isHTML(true);
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);
        } else {
            $mail->Body = $body;
        }
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Erreur lors de l\'envoi du mail récapitulatif : ' . $e->getMessage());
        return $e->getMessage();
    }
}
