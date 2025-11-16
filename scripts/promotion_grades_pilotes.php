<?php
/**
 * Script : promotion_grades_pilotes.php
 * --------------------------------------
 * Fonction métier :
 *   Automatise la promotion des pilotes selon leurs heures de vol cumulées.
 *   Met à jour le grade, envoie un mail de notification au pilote promu, logue chaque promotion et envoie un récapitulatif à l'administrateur.
 *
 * Automatisation :
 *   - Calcul des heures de vol pour chaque pilote
 *   - Détermination du grade éligible et mise à jour en base
 *   - Envoi d'un mail de notification au pilote promu
 *   - Log des promotions dans le fichier dédié
 *   - Envoi d'un mail récapitulatif à l'administrateur
 */
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/mail_utils.php';
require_once __DIR__ . '/../includes/log_func.php';
require_once __DIR__ . '/../lang.php';

// Définir la langue par défaut pour les scripts (pas de session)
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = VA_DEFAULT_LANGUAGE;
}

// Récupérer les grades et seuils
$grades = [
    1 => 0,
    2 => 100,
    3 => 200,
    4 => 300,
    5 => 400
];

logMsg('[PROMOTION] Début du script de promotion automatique', __DIR__ . '/logs/promotion_grades.log');
$stmtPilotes = $pdo->query("SELECT id, email, grade_id, prenom, nom, callsign FROM PILOTES");
$pilotes = $stmtPilotes->fetchAll(PDO::FETCH_ASSOC);

foreach ($pilotes as $pilote) {
    // Calculer le total d'heures de vol
    $stmtHeures = $pdo->prepare("SELECT SUM(TIME_TO_SEC(temps_vol)) FROM CARNET_DE_VOL_GENERAL WHERE pilote_id = ?");
    $stmtHeures->execute([$pilote['id']]);
    $total_sec = (int)$stmtHeures->fetchColumn();
    $total_heures = $total_sec / 3600;

    // Déterminer le grade éligible
    $nouveau_grade = $pilote['grade_id'];
    foreach ($grades as $grade_id => $seuil) {
        if ($total_heures >= $seuil) {
            $nouveau_grade = $grade_id;
        }
    }

    // Si le grade doit être augmenté
    if ($nouveau_grade > $pilote['grade_id']) {
        // Mettre à jour le grade
        $stmtUpdate = $pdo->prepare("UPDATE PILOTES SET grade_id = ? WHERE id = ?");
        $stmtUpdate->execute([$nouveau_grade, $pilote['id']]);

        // Récupérer le nom du nouveau grade
        $stmtGrade = $pdo->prepare("SELECT nom FROM GRADES WHERE id = ?");
        $stmtGrade->execute([$nouveau_grade]);
        $grade_nom = $stmtGrade->fetchColumn();

        // Log de la promotion (système commun)
        $log_msg = "Promotion: " . $pilote['callsign'] . " (" . $pilote['prenom'] . " " . $pilote['nom'] . ") promu au grade $grade_nom (heures: " . number_format($total_heures, 2) . ")";
        logMsg($log_msg, __DIR__ . '/logs/promotion_grades.log');
        $promotions[] = date('Y-m-d H:i:s') . ' | ' . $log_msg . "\n";

        // Envoyer un mail de notification au pilote
        $to = $pilote['email'];
        $subject = t('script_promotion_subject', ['grade' => $grade_nom]);
        $message = t('script_promotion_greeting', ['firstname' => htmlspecialchars($pilote['prenom']), 'lastname' => htmlspecialchars($pilote['nom'])]) . "<br><br>";
        $message .= t('script_promotion_congrats', ['grade' => $grade_nom]) . "<br>";
        $message .= t('script_promotion_continue') . "<br><br>";
        $message .= t('script_promotion_team');
        $mailResult = sendSummaryMail($subject, $message, $to);
        if ($mailResult === true || $mailResult === null) {
            logMsg("Mail de promotion envoyé à $to", __DIR__ . '/logs/promotion_grades.log');
        } else {
            logMsg("Erreur lors de l'envoi du mail de promotion à $to : $mailResult", __DIR__ . '/logs/promotion_grades.log');
        }
    }
}

// Envoi d'un mail récapitulatif à l'administrateur
if (!empty($promotions)) {
    $subject = t('script_promotion_recap_subject');
    $body = t('script_promotion_recap_greeting') . "<br><br>";
    $body .= t('script_promotion_recap_intro') . "<br><pre>" . implode("", $promotions) . "</pre><br>";
    $body .= t('script_promotion_recap_signature');
    $mailResult = sendSummaryMail($subject, $body, VA_ADMIN_EMAIL);
    if ($mailResult === true || $mailResult === null) {
        logMsg("Mail récapitulatif envoyé à " . VA_ADMIN_EMAIL, __DIR__ . '/logs/promotion_grades.log');
    } else {
        logMsg("Erreur lors de l'envoi du mail récapitulatif : $mailResult", __DIR__ . '/logs/promotion_grades.log');
    }
} else {
    $subject = t('script_promotion_recap_subject');
    $body = t('script_promotion_recap_greeting') . "<br><br>";
    $body .= t('script_promotion_recap_none') . "<br><br>";
    $body .= t('script_promotion_recap_signature');
    $mailResult = sendSummaryMail($subject, $body, VA_ADMIN_EMAIL);
    if ($mailResult === true || $mailResult === null) {
        logMsg("Mail récapitulatif (aucune promotion) envoyé à " . VA_ADMIN_EMAIL, __DIR__ . '/logs/promotion_grades.log');
    } else {
        logMsg("Erreur lors de l'envoi du mail récapitulatif (aucune promotion) : $mailResult", __DIR__ . '/logs/promotion_grades.log');
    }
}

logMsg('[PROMOTION] Fin du script de promotion automatique', __DIR__ . '/logs/promotion_grades.log');
echo "Promotions terminées.";
