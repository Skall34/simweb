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
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/log_func.php';
require_once __DIR__ . '/../includes/mail_utils.php';
require_once __DIR__ . '/../lang.php';
if (!isset($_SESSION['lang'])) $_SESSION['lang'] = VA_DEFAULT_LANGUAGE;
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
    // Envoi du mail recapitulatif enrichi
    if ($mailSummaryEnabled && function_exists('sendSummaryMail')) {
        $subject = str_replace('{date}', date('d/m/Y H:i'), t('script_fret_mail_subject'));
        $body = t('script_fret_mail_greeting') . "\n\n" . t('script_fret_mail_intro');
        $body .= "\n" . t('script_fret_mail_updated') . " : $count_updated (" . t('script_fret_mail_expected') . " : $nb_aeroports)";
        $body .= "\n" . t('script_fret_mail_bounds') . " : min=$min, max=$max";
        $body .= "\n" . t('script_fret_mail_coherence') . " : " . ($coherent ? t('script_fret_mail_coherence_ok') : t('script_fret_mail_coherence_error'));
        $body .= "\n\n" . t('script_fret_mail_automatic');
        $to = VA_ADMIN_EMAIL;
        $mailResult = sendSummaryMail($subject, $body, $to);
        if ($mailResult === true || $mailResult === null) {
            logMsg("Mail recapitulatif envoye a $to", $logFile);
        } else {
            logMsg("Erreur lors de l'envoi du mail recapitulatif : $mailResult", $logFile);
        }
    }
} catch (PDOException $e) {
    logMsg("❌ Erreur lors de la mise à jour : " . $e->getMessage(), $logFile);
    echo t('cli_error') . " " . $e->getMessage() . "\n";
}
