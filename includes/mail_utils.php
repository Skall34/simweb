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
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';

// Centralisation de l'adresse mail admin
if (!defined('ADMIN_EMAIL')) {
    define('ADMIN_EMAIL', 'zjfk7400@gmail.com');
}

function sendSummaryMail($subject, $body, $to = ADMIN_EMAIL) {
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'ssl0.ovh.net';
        $mail->SMTPAuth = true;
        $mail->Username = 'admin@skywings.ovh';
        $mail->Password = 'La6mulationCestCool!';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->setFrom('admin@skywings.ovh', 'Skywings');
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->CharSet = 'UTF-8';
        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Erreur lors de l\'envoi du mail récapitulatif : ' . $e->getMessage());
        return $e->getMessage();
    }
}
