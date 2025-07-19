<?php

/**
 * Script de paiement mensuel des salaires des pilotes
 *
 * - Calcule le salaire mensuel de chaque pilote selon :
 *   - Heures de vol du mois précédent (taux horaire selon grade)
 *   - Bonus fret : 2€ par kg de payload transporté (champ 'payload' dans CARNET_DE_VOL_GENERAL)
 * - Insère le salaire dans la table SALAIRES
 * - Met à jour le revenu cumulé du pilote (PILOTES.revenus)
 * - Met à jour le paiement des salaires dans BALANCE_COMMERCIALE (champ paiement_salaires)
 * - Envoie un mail au pilote avec le détail (heures, fret, bonus, total)
 * - Envoie un mail récapitulatif à l'administrateur (ADMIN_EMAIL)
 * - Logue chaque étape dans logs/paiement_salaires.log
 */

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/mail_utils.php';
require_once __DIR__ . '/../includes/log_func.php';
require_once __DIR__ . '/../includes/fonctions_financieres.php';

// Mode test : si true, les mails aux pilotes sont envoyés à l'admin uniquement
$test_mode = true; // Passe à true pour tester sans envoi aux pilotes

// Date du paiement (dernier jour du mois précédent)
$date_paiement = date('Y-m-01', strtotime('first day of this month -1 day'));

logMsg('[SALAIRE] Début du script de paiement des salaires', __DIR__ . '/logs/paiement_salaires.log');
$requete_pilotes = "SELECT id, email, grade_id, prenom, nom, callsign FROM PILOTES";

$stmtPilotes = $pdo->query("SELECT id, email, grade_id, prenom, nom, callsign FROM PILOTES");
$pilotes = $stmtPilotes->fetchAll(PDO::FETCH_ASSOC);

// Trace du nombre de pilotes et de leur callsign
$nb_pilotes = count($pilotes);
$callsigns = array_map(function($p) { return $p['callsign']; }, $pilotes);
logMsg('[TRACE] Nombre de pilotes trouvés : ' . $nb_pilotes . ' | Callsigns : ' . implode(', ', $callsigns), __DIR__ . '/logs/paiement_salaires.log');

$debut_mois = date('Y-m-01', strtotime('first day of last month'));
$fin_mois = date('Y-m-01');
$recap = [];

foreach ($pilotes as $index => $pilote) {
    logMsg('[TRACE] --- Début traitement pilote ---', __DIR__ . '/logs/paiement_salaires.log');
    echo "<pre>--- Début traitement pilote ---\n";
    logMsg('[TRACE] Pilote : ' . print_r($pilote, true), __DIR__ . '/logs/paiement_salaires.log');
    echo "Pilote : " . htmlspecialchars(print_r($pilote, true)) . "\n";
    $requete_heures = "SELECT SUM(TIME_TO_SEC(temps_vol)) FROM CARNET_DE_VOL_GENERAL WHERE pilote_id = " . $pilote['id'] . " AND DATE(date_vol) >= '" . $debut_mois . "' AND DATE(date_vol) < '" . $fin_mois . "'";
    logMsg('[TRACE] Requête heures : ' . $requete_heures, __DIR__ . '/logs/paiement_salaires.log');
    echo "Requête heures : $requete_heures\n";
    try {
        $stmtTaux = $pdo->prepare("SELECT taux_horaire FROM GRADES WHERE id = ?");
        $stmtTaux->execute([$pilote['grade_id']]);
        $taux_horaire = (float)$stmtTaux->fetchColumn();
        logMsg('[TRACE] Taux horaire : ' . $taux_horaire, __DIR__ . '/logs/paiement_salaires.log');
        echo "Taux horaire : $taux_horaire\n";
    } catch (Exception $e) {
        logMsg('[ERREUR] Récupération taux horaire : ' . $e->getMessage(), __DIR__ . '/logs/paiement_salaires.log');
        echo "ERREUR Récupération taux horaire : " . htmlspecialchars($e->getMessage()) . "\n";
        continue;
    }

    try {
        $stmtHeures = $pdo->prepare("SELECT SUM(TIME_TO_SEC(temps_vol)) FROM CARNET_DE_VOL_GENERAL WHERE pilote_id = ? AND DATE(date_vol) >= ? AND DATE(date_vol) < ?");
        $stmtHeures->execute([$pilote['id'], $debut_mois, $fin_mois]);
        $total_sec = (int)$stmtHeures->fetchColumn();
        $heures_mois = $total_sec / 3600;
        logMsg('[TRACE] Total secondes vol : ' . $total_sec . ' | Heures mois : ' . $heures_mois, __DIR__ . '/logs/paiement_salaires.log');
        echo "Total secondes vol : $total_sec | Heures mois : $heures_mois\n";
    } catch (Exception $e) {
        logMsg('[ERREUR] Calcul heures de vol : ' . $e->getMessage(), __DIR__ . '/logs/paiement_salaires.log');
        echo "ERREUR Calcul heures de vol : " . htmlspecialchars($e->getMessage()) . "\n";
        continue;
    }

    try {
        $stmtFret = $pdo->prepare("SELECT SUM(payload) FROM CARNET_DE_VOL_GENERAL WHERE pilote_id = ? AND DATE(date_vol) >= ? AND DATE(date_vol) < ?");
        $stmtFret->execute([$pilote['id'], $debut_mois, $fin_mois]);
        $total_fret_kg = (float)$stmtFret->fetchColumn();
        $bonus_fret = round($total_fret_kg * 2, 2); // 2€ par kg
        logMsg('[TRACE] Total fret (kg) : ' . $total_fret_kg . ' | Bonus fret : ' . $bonus_fret, __DIR__ . '/logs/paiement_salaires.log');
        echo "Total fret (kg) : $total_fret_kg | Bonus fret : $bonus_fret\n";
    } catch (Exception $e) {
        logMsg('[ERREUR] Calcul fret : ' . $e->getMessage(), __DIR__ . '/logs/paiement_salaires.log');
        echo "ERREUR Calcul fret : " . htmlspecialchars($e->getMessage()) . "\n";
        continue;
    }

    $montant = round($heures_mois * $taux_horaire + $bonus_fret, 2);
    logMsg('[TRACE] Montant calculé : ' . $montant, __DIR__ . '/logs/paiement_salaires.log');
    echo "Montant calculé : $montant\n";

    // Ignorer les pilotes sans vol ni fret sur la période
    if (($total_sec === null || $total_sec == 0) && ($total_fret_kg === null || $total_fret_kg == 0)) {
        logMsg('[INFO] Aucun vol ni fret pour ce pilote sur la période, traitement ignoré.', __DIR__ . '/logs/paiement_salaires.log');
        echo "Aucun vol ni fret pour ce pilote sur la période, traitement ignoré.\n--- Fin traitement pilote ---\n\n";
        continue;
    }

    try {
        $stmtSalaire = $pdo->prepare("INSERT INTO SALAIRES (id_pilote, date_de_paiement, montant) VALUES (?, ?, ?)");
        $stmtSalaire->execute([$pilote['id'], $date_paiement, $montant]);
        logMsg('[TRACE] Insertion salaire OK', __DIR__ . '/logs/paiement_salaires.log');
        echo "Insertion salaire OK\n";
    } catch (Exception $e) {
        logMsg('[ERREUR] Insertion salaire : ' . $e->getMessage(), __DIR__ . '/logs/paiement_salaires.log');
        echo "ERREUR Insertion salaire : " . htmlspecialchars($e->getMessage()) . "\n";
        continue;
    }

    try {
        $stmtUpdate = $pdo->prepare("UPDATE PILOTES SET revenus = revenus + ? WHERE id = ?");
        $stmtUpdate->execute([$montant, $pilote['id']]);
        logMsg('[TRACE] Update revenus pilote OK', __DIR__ . '/logs/paiement_salaires.log');
        echo "Update revenus pilote OK\n";
    } catch (Exception $e) {
        logMsg('[ERREUR] Update revenus pilote : ' . $e->getMessage(), __DIR__ . '/logs/paiement_salaires.log');
        echo "ERREUR Update revenus pilote : " . htmlspecialchars($e->getMessage()) . "\n";
        continue;
    }

    try {
        // Enregistrer le paiement des salaires dans finances_depenses (nouveau système)
        mettreAJourDepenses($montant, $pilote['id'], '', $pilote['callsign'], 'salaire', 'Paiement salaire mensuel');
        logMsg('[TRACE] Paiement salaire enregistré dans finances_depenses', __DIR__ . '/logs/paiement_salaires.log');
        echo "Paiement salaire enregistré dans finances_depenses\n";
    } catch (Exception $e) {
        logMsg('[ERREUR] Paiement salaire finances_depenses : ' . $e->getMessage(), __DIR__ . '/logs/paiement_salaires.log');
        echo "ERREUR Paiement salaire finances_depenses : " . htmlspecialchars($e->getMessage()) . "\n";
        continue;
    }

    $log_msg = "Salaire: " . $pilote['callsign'] . " (" . $pilote['prenom'] . " " . $pilote['nom'] . ") - Heures: " . number_format($heures_mois, 2) . " - Fret: " . number_format($total_fret_kg, 2) . "kg - Bonus fret: " . number_format($bonus_fret, 2) . "€ - Montant: " . number_format($montant, 2) . "€";
    logMsg($log_msg, __DIR__ . '/logs/paiement_salaires.log');
    // Log utile : mail envoyé ou erreur
    $to = $test_mode ? ADMIN_EMAIL : $pilote['email'];
    $subject = "Votre salaire du mois";
    $message = "Bonjour " . $pilote['prenom'] . " " . $pilote['nom'] . ",\n\n";
    $message .= "Vous avez effectué " . number_format($heures_mois, 2) . " heures de vol ce mois-ci.\n";
    $message .= "Vous avez transporté " . number_format($total_fret_kg, 2) . " kg de fret, soit un bonus de " . number_format($bonus_fret, 2) . "€.\n";
    $message .= "Votre salaire total est de " . number_format($montant, 2) . "€.\n\n";
    $message .= "Cordialement,\nL'équipe Skywings";
    try {
        if ($index === count($pilotes) - 1) {
            sleep(5);
        }
        $mailResult = sendSummaryMail($subject, $message, $to);
        if ($mailResult === true || $mailResult === null) {
            logMsg("Mail de salaire envoyé à $to", __DIR__ . '/logs/paiement_salaires.log');
        } else {
            logMsg("Erreur lors de l'envoi du mail de salaire à $to : $mailResult", __DIR__ . '/logs/paiement_salaires.log');
        }
        sleep(1);
    } catch (Exception $e) {
        logMsg('[ERREUR] Envoi mail salaire : ' . $e->getMessage(), __DIR__ . '/logs/paiement_salaires.log');
    }
    // Log utile : fin traitement pilote
    $recap[] = date('Y-m-d H:i:s') . ' | ' . $pilote['callsign'] . ' (' . $pilote['prenom'] . ' ' . $pilote['nom'] . ') - Heures: ' . number_format($heures_mois, 2) . ' - Fret: ' . number_format($total_fret_kg, 2) . 'kg - Bonus fret: ' . number_format($bonus_fret, 2) . '€ - Montant: ' . number_format($montant, 2) . "€\n";
}

logMsg('[SALAIRE] Fin du script de paiement des salaires', __DIR__ . '/logs/paiement_salaires.log');
echo "Paiement des salaires terminé.";
// Envoi du mail récapitulatif à l'administrateur SKY0707
if (!empty($recap)) {
    $subject = "Récapitulatif des salaires versés";
    $maxLines = 50;
    $recap_limited = array_slice($recap, 0, $maxLines);
    $body = "Salut SKY0707,\n\nVoici la liste des salaires versés ce mois-ci (max $maxLines lignes) :\n" . implode("", $recap_limited) . "\nCordialement,\nLe système automatique Skywings";
    $mailResult = sendSummaryMail($subject, $body, ADMIN_EMAIL);
    if ($mailResult === true || $mailResult === null) {
        logMsg("Mail récapitulatif des salaires envoyé à " . ADMIN_EMAIL, __DIR__ . '/logs/paiement_salaires.log');
    } else {
        logMsg("Erreur lors de l'envoi du mail récapitulatif des salaires : $mailResult", __DIR__ . '/logs/paiement_salaires.log');
    }
} else {
    $subject = "Récapitulatif des salaires versés";
    $body = "Bonjour Administrateur,\n\nAucun salaire n'a été versé ce mois-ci.\n\nCordialement,\nLe système automatique Skywings";
    $mailResult = sendSummaryMail($subject, $body, ADMIN_EMAIL);
    if ($mailResult === true || $mailResult === null) {
        logMsg("Mail récapitulatif (aucun salaire) envoyé à " . ADMIN_EMAIL, __DIR__ . '/logs/paiement_salaires.log');
    } else {
        logMsg("Erreur lors de l'envoi du mail récapitulatif (aucun salaire) : $mailResult", __DIR__ . '/logs/paiement_salaires.log');
    }
}
