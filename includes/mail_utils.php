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
    // Vérifier si les constantes SMTP sont définies
    if (!defined('SMTP_HOST') || !defined('SMTP_USERNAME') || !defined('SMTP_PASSWORD')) {
        error_log('Mail non envoyé : configuration SMTP manquante (config.php non configuré)');
        return 'Configuration SMTP manquante';
    }
    
    // Si aucun destinataire spécifié, utiliser l'admin
    if ($to === null) {
        if (!defined('VA_ADMIN_EMAIL')) {
            error_log('Mail non envoyé : VA_ADMIN_EMAIL non défini');
            return 'Email administrateur non configuré';
        }
        $to = VA_ADMIN_EMAIL;
    }
    
    try {
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
        $mail->Encoding = '8bit';
        $mail->WordWrap = 70; // Limiter longueur des lignes
        
        // Envoi en HTML uniquement si le corps contient des balises HTML
        if (preg_match('/<[^>]+>/', $body)) {
            $mail->isHTML(true);
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);
        } else {
            $mail->isHTML(false);
            $mail->Body = $body;
        }
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Erreur lors de l\'envoi du mail récapitulatif : ' . $e->getMessage());
        return $e->getMessage();
    }
}
