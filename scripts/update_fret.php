<?php
/*
-------------------------------------------------------------
 Script : update_fret.php
 Emplacement : scripts/

 Description :
 Ce script met à jour chaque semaine le fret disponible dans tous les aéroports.
 Il ajoute une valeur aléatoire (entre $min et $max) au fret de chaque aéroport.
 Toutes les opérations et erreurs sont loguées dans scripts/logs/update_fret.log via logMsg().

 Fonctionnement :
 1. Sélectionne tous les aéroports et leur fret actuel.
 2. Pour chaque aéroport, ajoute une valeur aléatoire au fret et met à jour la base.
 3. Logue chaque mise à jour et erreur dans le fichier log.

 Utilisation :
 - À lancer chaque semaine pour simuler l'arrivée de fret dans les aéroports.
 - Vérifier le log en cas d'anomalie ou d'échec d'opération.

 Auteur :
 - Automatisé avec GitHub Copilot
-------------------------------------------------------------
*/
$mailSummaryEnabled = true; // Active l'envoi du mail récapitulatif
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/log_func.php';
require_once __DIR__ . '/../includes/mail_utils.php';
$logFile = __DIR__ . '/logs/update_fret.log';

$min = 1;
$max = 100;

try {
    $stmt = $pdo->query("SELECT ident, fret FROM AEROPORTS");
    $aeroports = $stmt->fetchAll();
    $count_updated = 0;
    foreach ($aeroports as $aeroport) {
        $valeurAleatoire = random_int($min, $max);
        $nouveauFret = $aeroport['fret'] + $valeurAleatoire;
        $updateStmt = $pdo->prepare("UPDATE AEROPORTS SET fret = :fret WHERE ident = :ident");
        $updateStmt->execute([
            'fret' => $nouveauFret,
            'ident' => $aeroport['ident']
        ]);
        $count_updated++;
    }
    // Vérification cohérence
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM AEROPORTS");
    $nb_aeroports = (int)$stmtCount->fetchColumn();
    $coherent = ($count_updated === $nb_aeroports);
    $msg = "Traitement hebdomadaire terminé : $count_updated aéroports mis à jour (attendu : $nb_aeroports)";
    if ($coherent) {
        $msg .= " [COHERENT]";
    } else {
        $msg .= " [INCOHERENT]";
    }
    logMsg($msg, $logFile);
    echo $msg . "\n";
    // Envoi du mail récapitulatif enrichi
    if ($mailSummaryEnabled && function_exists('sendSummaryMail')) {
        $subject = "[SimWeb] Rapport fret aéroports - " . date('d/m/Y H:i');
        $body = "Bonjour,\n\nLe traitement hebdomadaire du fret aéroports s'est terminé.";
        $body .= "\nNombre d'aéroports mis à jour : $count_updated (attendu : $nb_aeroports)";
        $body .= "\nBornes utilisées : min=$min, max=$max";
        $body .= $coherent ? "\nCohérence : OK" : "\nCohérence : INCOHERENTE";
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
    logMsg("❌ Erreur lors de la mise à jour : " . $e->getMessage(), $logFile);
    echo "Erreur : " . $e->getMessage() . "\n";
}
