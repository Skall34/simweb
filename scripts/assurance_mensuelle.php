<?php
/*
-------------------------------------------------------------
 Script : assurance_mensuelle.php
 Emplacement : scripts/

 Description :
 Ce script calcule et déduit chaque mois le coût d'assurance de la compagnie aérienne virtuelle.
 L'assurance est calculée comme un petit pourcentage (par défaut 0.2%) de la valeur absolue de la balance commerciale actuelle.
 La balance commerciale est ensuite mise à jour en conséquence.

 Log :
 Toutes les opérations et vérifications sont enregistrées dans scripts/logs/assurance_mensuelle.log.

 Notification :
 Un mail récapitulatif automatique est envoyé à l'administrateur à la fin du script pour indiquer le succès du traitement.

 Fonctionnement :
 1. Récupère la balance commerciale actuelle (champ balance_actuelle).
 2. Calcule l'assurance mensuelle : abs(balance_actuelle) * pourcentage.
 3. Vérifie la cohérence de la balance avec la formule :
    balance_actuelle = recettes - cout_avions + apport_initial - assurance
 4. Si la balance est cohérente, déduit l'assurance mensuelle de la balance_actuelle.
 5. Logue la balance avant/après et toute anomalie détectée.
 6. Envoie un mail récapitulatif automatique à la fin du script.

 Utilisation :
 - À lancer une fois par mois (cron ou manuel).
 - Adapter le pourcentage si besoin (variable $pourcentage).
 - Vérifier le log en cas d'anomalie ou d'alerte.

 Auteur :
 - Automatisé avec GitHub Copilot
-------------------------------------------------------------
*/

$mailSummaryEnabled = true; // Active l'envoi du mail récapitulatif
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/log_func.php';
require_once __DIR__ . '/../includes/mail_utils.php';
$logFile = __DIR__ . '/logs/assurance_mensuelle.log';
logMsg("--- Démarrage du script d'assurance mensuelle ---", $logFile);
logMsg("--- Script assurance_mensuelle.php lancé ---", $logFile);
echo "--- Script assurance_mensuelle.php lancé ---\n";

try {
    // Calculer l'assurance mensuelle comme un petit pourcentage de la balance actuelle (valeur absolue)
    $sqlBalance = "SELECT balance_actuelle FROM BALANCE_COMMERCIALE";
    $stmtBalance = $pdo->query($sqlBalance);
    $balance = $stmtBalance->fetchColumn();
    logMsg("Balance avant déduction: $balance", $logFile);
    $pourcentage = 0.002; // 0.2% par mois
    $assurance_mensuelle = round(abs($balance) * $pourcentage, 2);
    // Vérification de la cohérence de la balance
    $sqlCheck = "SELECT recettes, cout_avions, apport_initial, assurance FROM BALANCE_COMMERCIALE";
    $stmtCheck = $pdo->query($sqlCheck);
    $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    $balance_theorique = $row['recettes'] - $row['cout_avions'] + $row['apport_initial'] - $row['assurance'];
    $alerte_balance = false;
    $balance_apres = null;
    if (abs($balance - $balance_theorique) > 0.01) {
        $details = "[ALERTE] Balance incohérente :\n";
        $details .= "balance_actuelle = $balance\n";
        $details .= "balance_theorique = $balance_theorique\n";
        $details .= "recettes = {$row['recettes']}\n";
        $details .= "cout_avions = {$row['cout_avions']}\n";
        $details .= "apport_initial = {$row['apport_initial']}\n";
        $details .= "assurance = {$row['assurance']}\n";
        logMsg($details, $logFile);
        echo $details;
        $alerte_balance = true;
    } else {
        // Déduire l'assurance de la balance ET mettre à jour le cumul d'assurance en une seule requête
        $sqlUpdate = "UPDATE BALANCE_COMMERCIALE SET balance_actuelle = balance_actuelle - :assurance1, assurance = assurance + :assurance2";
        echo "Requête SQL exécutée : $sqlUpdate\n";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute(['assurance1' => $assurance_mensuelle, 'assurance2' => $assurance_mensuelle]);
        // Vérifier la balance après déduction
        $stmtBalance2 = $pdo->query($sqlBalance);
        $balance_apres = $stmtBalance2->fetchColumn();
        logMsg("Assurance mensuelle calculée sur balance actuelle (abs($balance)): $assurance_mensuelle déduite.", $logFile);
        logMsg("Balance après déduction: $balance_apres", $logFile);
    }
    logMsg("Traitement terminé.", $logFile);
    // Affichage récapitulatif pour l'admin
    $message = "Traitement d'assurance mensuelle terminé.\n";
    $message .= "Montant prélevé : $assurance_mensuelle €\n";
    $message .= "Balance avant : $balance €\n";
    if ($balance_apres !== null) {
        $message .= "Balance après : $balance_apres €\n";
    }
    if ($alerte_balance) {
        $message .= "[ALERTE] Balance incohérente : aucune opération effectuée.\n";
    }
    echo $message;
    // Envoi du mail récapitulatif enrichi
    if ($mailSummaryEnabled && function_exists('sendSummaryMail')) {
        $subject = "[SimWeb] Rapport assurance mensuelle - " . date('d/m/Y H:i');
        $body = "Bonjour,\n\nLe traitement d'assurance mensuelle s'est terminé.";
        $body .= "\nMontant prélevé : $assurance_mensuelle €";
        $body .= "\nBalance avant : $balance €";
        if ($balance_apres !== null) {
            $body .= "\nBalance après : $balance_apres €";
        }
        if ($alerte_balance) {
            $body .= "\n\n[ALERTE] Balance incohérente : aucune opération effectuée.";
        }
        $body .= "\n\nCeci est un message automatique.";
        $to = ADMIN_EMAIL;
        $mailResult = sendSummaryMail($subject, $body, $to);
        if ($mailResult === true || $mailResult === null) {
            logMsg("Mail récapitulatif envoyé à $to", $logFile);
        } else {
            logMsg("Erreur lors de l'envoi du mail récapitulatif : $mailResult", $logFile);
        }
    }
} catch (PDOException $e) {
    logMsg("Erreur SQL : " . $e->getMessage(), $logFile);
    echo "Erreur SQL : " . $e->getMessage() . "\n";
}

// Fin du script
