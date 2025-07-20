<?php
/*
-------------------------------------------------------------
 Script : assurance_mensuelle.php
 Emplacement : scripts/


 Description :
 Ce script calcule et déduit chaque mois le coût d'assurance de la compagnie aérienne virtuelle.
 L'assurance est calculée comme un petit pourcentage (par défaut 0.2%) de la valeur absolue de la balance commerciale actuelle (champ balance_actuelle dans BALANCE_COMMERCIALE).
 La balance commerciale est ensuite mise à jour en conséquence.

 Log :
 Toutes les opérations et vérifications sont enregistrées dans scripts/logs/assurance_mensuelle.log.

 Notification :
 Un mail récapitulatif automatique est envoyé à l'administrateur à la fin du script pour indiquer le succès du traitement.


 Fonctionnement :
 1. Récupère la balance commerciale actuelle (champ balance_actuelle).
 2. Calcule l'assurance mensuelle : valeur absolue de balance_actuelle * pourcentage.
 3. Insère la dépense dans finances_depenses avec un commentaire explicite.
 4. Met à jour la balance commerciale.
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
require_once __DIR__ . '/../includes/fonctions_financieres.php';
$logFile = dirname(__DIR__) . '/scripts/logs/assurance_mensuelle.log';

logMsg("--- Démarrage du script d'assurance mensuelle ---", $logFile);
logMsg("--- Script assurance_mensuelle.php lancé ---", $logFile);
echo "--- Script assurance_mensuelle.php lancé ---\n";

try {
    // Calculer l'assurance mensuelle comme un petit pourcentage de la valeur absolue de la balance actuelle
    $sqlBalanceActuelle = "SELECT balance_actuelle FROM BALANCE_COMMERCIALE";
    $stmtBalanceActuelle = $pdo->query($sqlBalanceActuelle);
    $balance_actuelle = $stmtBalanceActuelle->fetchColumn();
    logMsg("Balance actuelle (balance_actuelle): $balance_actuelle", $logFile);
    $pourcentage = 0.002; // 0.2% par mois
    $assiette = abs($balance_actuelle);
    $assurance_mensuelle = round($assiette * $pourcentage, 2);

    $commentaire_assurance = "Prélèvement assurance mensuelle (0.2% de la valeur absolue de la balance actuelle : $assiette €)";
    mettreAJourDepenses($assurance_mensuelle, null, '', 'SYSTEM', 'assurance', $commentaire_assurance);
    logMsg("Assurance mensuelle enregistrée dans finances_depenses: $assurance_mensuelle | $commentaire_assurance", $logFile);
    logMsg("Traitement terminé.", $logFile);
    // Affichage récapitulatif pour l'admin
    $message = "Traitement d'assurance mensuelle terminé.\n";
    $message .= "Montant prélevé : $assurance_mensuelle €\n";
    $message .= "Base de calcul (valeur absolue de la balance) : $assiette €\n";
    $message .= "Balance avant : $balance_actuelle €\n";

    echo $message;
    // Envoi du mail récapitulatif enrichi
    if ($mailSummaryEnabled && function_exists('sendSummaryMail')) {
        $subject = "[SimWeb] Rapport assurance mensuelle - " . date('d/m/Y H:i');
        $body = "Bonjour,\n\nLe traitement d'assurance mensuelle s'est terminé.";
        $body .= "\nMontant prélevé : $assurance_mensuelle €";
        $body .= "\nBalance avant : $balance €";
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
