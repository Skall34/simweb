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

// Récupérer les grades et seuils
$grades = [
    1 => 0,
    2 => 100,
    3 => 200,
    4 => 300,
    5 => 400
];

logMsg('[PROMOTION] Début du script de promotion automatique');
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
        $subject = "Félicitations, vous avez été promu au grade $grade_nom !";
        $message = "Bonjour " . htmlspecialchars($pilote['prenom']) . " " . htmlspecialchars($pilote['nom']) . ",<br><br>";
        $message .= "Votre nouveau grade est <strong>$grade_nom</strong>.<br>Continuez à voler pour progresser !<br><br>";
        $message .= "Cordialement,<br>L'équipe Skywings";
        $mailResult = send_mail($to, $subject, $message);
        if ($mailResult === true || $mailResult === null) {
            logMsg("Mail de promotion envoyé à $to", __DIR__ . '/logs/promotion_grades.log');
        } else {
            logMsg("Erreur lors de l'envoi du mail de promotion à $to : $mailResult", __DIR__ . '/logs/promotion_grades.log');
        }
    }
}

// Envoi d'un mail récapitulatif à l'administrateur SKY0707
if (!empty($promotions)) {
    $subject = "Récapitulatif des promotions de grades";
    $body = "Bonjour Administrateur,<br><br>Voici la liste des promotions effectuées cette nuit :<br><pre>" . implode("", $promotions) . "</pre><br>Cordialement,<br>Le système automatique Skywings";
    $mailResult = sendSummaryMail($subject, $body, ADMIN_EMAIL);
    if ($mailResult === true || $mailResult === null) {
        logMsg("Mail récapitulatif envoyé à " . ADMIN_EMAIL, __DIR__ . '/logs/promotion_grades.log');
    } else {
        logMsg("Erreur lors de l'envoi du mail récapitulatif : $mailResult", __DIR__ . '/logs/promotion_grades.log');
    }
} else {
    $subject = "Récapitulatif des promotions de grades";
    $body = "Bonjour Administrateur,<br><br>Aucune promotion de grade n'a eu lieu cette nuit.<br><br>Cordialement,<br>Le système automatique Skywings";
    $mailResult = sendSummaryMail($subject, $body, ADMIN_EMAIL);
    if ($mailResult === true || $mailResult === null) {
        logMsg("Mail récapitulatif (aucune promotion) envoyé à " . ADMIN_EMAIL, __DIR__ . '/logs/promotion_grades.log');
    } else {
        logMsg("Erreur lors de l'envoi du mail récapitulatif (aucune promotion) : $mailResult", __DIR__ . '/logs/promotion_grades.log');
    }
}

logMsg('[PROMOTION] Fin du script de promotion automatique', __DIR__ . '/logs/promotion_grades.log');
echo "Promotions terminées.";
