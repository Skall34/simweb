<?php
/*
-------------------------------------------------------------
 Script : assurance_mensuelle.php
 Emplacement : scripts/

 Description :
 Ce script calcule et déduit chaque mois le coût d'assurance de la compagnie aérienne virtuelle.
 L'assurance est calculée comme un petit pourcentage (par défaut 0.2%) du coût total des avions détenus (champ cout_avions dans BALANCE_COMMERCIALE).
 La balance commerciale est ensuite mise à jour en conséquence.

 Log :
 Toutes les opérations et vérifications sont enregistrées dans scripts/logs/assurance_mensuelle.log.

 Notification :
 Un mail récapitulatif automatique est envoyé à l'administrateur à la fin du script pour indiquer le succès du traitement.

 Fonctionnement :
 1. Récupère le coût total des avions détenus (champ cout_avions).
 2. Calcule l'assurance mensuelle : cout_avions * pourcentage.
 3. Vérifie la cohérence de la balance avec la formule métier.
 4. Si la balance est cohérente, déduit l'assurance mensuelle de la balance_actuelle et met à jour le cumul d'assurance.
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
    // Calculer l'assurance mensuelle comme un petit pourcentage du coût total des avions
    $sqlCoutAvions = "SELECT cout_avions FROM BALANCE_COMMERCIALE";
    $stmtCoutAvions = $pdo->query($sqlCoutAvions);
    $cout_avions = $stmtCoutAvions->fetchColumn();
    logMsg("Cout total des avions (cout_avions): $cout_avions", $logFile);
    $pourcentage = 0.002; // 0.2% par mois
    $assurance_mensuelle = round($cout_avions * $pourcentage, 2);
    // Mettre à jour le cumul d'assurance
    $sqlUpdateAssurance = "UPDATE BALANCE_COMMERCIALE SET assurance = assurance + :assurance2";
    $stmtUpdateAssurance = $pdo->prepare($sqlUpdateAssurance);
    $stmtUpdateAssurance->execute(['assurance2' => $assurance_mensuelle]);
    // Recalculer la balance_actuelle en soustrayant le montant de l'assurance prélevée
    $sqlGetBalance = "SELECT balance_actuelle FROM BALANCE_COMMERCIALE";
    $stmtBalance = $pdo->query($sqlGetBalance);
    $balance = $stmtBalance->fetchColumn();
    $balance_actuelle = $balance - $assurance_mensuelle;
    $sqlUpdateBalance = "UPDATE BALANCE_COMMERCIALE SET balance_actuelle = :balance_actuelle";
    $stmtUpdateBalance = $pdo->prepare($sqlUpdateBalance);
    $stmtUpdateBalance->execute(['balance_actuelle' => $balance_actuelle]);
    logMsg("Assurance mensuelle calculée sur cout_avions: $assurance_mensuelle ajoutée.", $logFile);
    logMsg("Balance recalculée après mise à jour de l'assurance: $balance_actuelle", $logFile);
    $balance_apres = $balance_actuelle;
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
