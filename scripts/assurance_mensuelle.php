<?php
/*
-------------------------------------------------------------
 Script : assurance_mensuelle.php
 Emplacement : scripts/

 Description :
 Ce script calcule et déduit chaque mois le coût d'assurance de la compagnie aérienne virtuelle.
 L'assurance est calculée comme un pourcentage annuel de la valeur totale de la flotte active,
 divisé par 12 pour obtenir la mensualité.

 Log :
 Toutes les opérations et vérifications sont enregistrées dans scripts/logs/assurance_mensuelle.log.

 Notification :
 Un mail récapitulatif automatique est envoyé à l'administrateur à la fin du script.

 Fonctionnement :
 1. Calcule la valeur totale de la flotte active (somme des cout_appareil via FLEET_TYPE).
 2. Récupère le taux d'assurance annuel depuis VARIABLES_CONFIG.
 3. Calcule l'assurance mensuelle : valeur_flotte * taux / 12.
 4. Insère la dépense dans finances_depenses.
 5. Met à jour la balance commerciale.
 6. Envoie un mail récapitulatif automatique.

 Utilisation :
 - À lancer une fois par mois (cron ou manuel).
 - Adapter le taux si besoin via l'admin (variable taux_assurance).

 Auteur :
 - Automatisé avec GitHub Copilot
-------------------------------------------------------------
*/

$mailSummaryEnabled = true; // Active l'envoi du mail récapitulatif
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/log_func.php';
require_once __DIR__ . '/../includes/mail_utils.php';
require_once __DIR__ . '/../includes/fonctions_financieres.php';
$logFile = dirname(__DIR__) . '/scripts/logs/assurance_mensuelle.log';

logMsg("--- Démarrage du script d'assurance mensuelle ---", $logFile);
logMsg("--- Script assurance_mensuelle.php lancé ---", $logFile);
echo "--- Script assurance_mensuelle.php lancé ---\n";

try {
    // Calculer la valeur totale de la flotte active
    $sqlFlotte = "SELECT COALESCE(SUM(ft.cout_appareil), 0) 
                  FROM FLOTTE f 
                  JOIN FLEET_TYPE ft ON f.fleet_type = ft.id 
                  WHERE f.Actif = 1";
    $valeur_flotte = floatval($pdo->query($sqlFlotte)->fetchColumn());
    logMsg("Valeur totale de la flotte active : $valeur_flotte", $logFile);

    // Nombre d'appareils actifs (pour info)
    $nb_appareils = intval($pdo->query("SELECT COUNT(*) FROM FLOTTE WHERE Actif = 1")->fetchColumn());

    // Récupérer dynamiquement le taux d'assurance annuel depuis VARIABLES_CONFIG
    $taux_annuel = 0.02; // valeur par défaut 2%
    $stmtTaux = $pdo->prepare("SELECT valeur FROM VARIABLES_CONFIG WHERE nom = 'taux_assurance'");
    if ($stmtTaux->execute()) {
        $valeurTaux = $stmtTaux->fetchColumn();
        if ($valeurTaux !== false && is_numeric($valeurTaux)) {
            $taux_annuel = floatval($valeurTaux);
        }
    }

    // Assurance mensuelle = valeur flotte * taux annuel / 12
    $assurance_mensuelle = round($valeur_flotte * $taux_annuel / 12, 2);

    // Formatage pour affichage
    $valeur_flotte_fmt = number_format($valeur_flotte, 2, ',', ' ');
    $assurance_fmt = number_format($assurance_mensuelle, 2, ',', ' ');
    $taux_display = rtrim(rtrim(number_format($taux_annuel * 100, 3, ',', ' '), '0'), ',') . '%';

    if ($assurance_mensuelle > 0) {
        $commentaire_assurance = "Assurance mensuelle — {$taux_display} annuel sur flotte active ({$nb_appareils} appareils, valeur {$valeur_flotte_fmt} €)";
        mettreAJourDepenses($assurance_mensuelle, null, '', 'SYSTEM', 'assurance', $commentaire_assurance);
        logMsg("Assurance enregistrée : {$assurance_fmt} € | $commentaire_assurance", $logFile);
    } else {
        logMsg("Aucune assurance à prélever (flotte vide ou valeur nulle).", $logFile);
    }

    logMsg("Traitement terminé.", $logFile);

    // Affichage récapitulatif
    $message = "Traitement d'assurance mensuelle terminé.\n";
    $message .= "Appareils actifs : {$nb_appareils}\n";
    $message .= "Valeur flotte : {$valeur_flotte_fmt} €\n";
    $message .= "Taux annuel : {$taux_display}\n";
    $message .= "Montant prélevé : {$assurance_fmt} €\n";

    echo $message;
    // Envoi du mail recapitulatif enrichi
    if ($mailSummaryEnabled && function_exists('sendSummaryMail')) {
        $subject = "[SimWeb] Rapport assurance mensuelle - " . date('d/m/Y H:i');
        $body = "Bonjour,\n\nLe traitement d'assurance mensuelle s'est terminé.";
        $body .= "\nAppareils actifs : {$nb_appareils}";
        $body .= "\nValeur flotte : {$valeur_flotte_fmt} €";
        $body .= "\nTaux annuel : {$taux_display}";
        $body .= "\nMontant prélevé : {$assurance_fmt} €";
        $body .= "\n\nCeci est un message automatique.";
        $to = VA_ADMIN_EMAIL;
        $mailResult = sendSummaryMail($subject, $body, $to);
        if ($mailResult === true || $mailResult === null) {
            logMsg("Mail recapitulatif envoye a $to", $logFile);
        } else {
            logMsg("Erreur lors de l'envoi du mail recapitulatif : $mailResult", $logFile);
        }
    }
} catch (PDOException $e) {
    logMsg("Erreur SQL : " . $e->getMessage(), $logFile);
    echo "Erreur SQL : " . $e->getMessage() . "\n";
}

// Fin du script
