<?php
/*
-------------------------------------------------------------
 Script : rotate_logs.php
 Emplacement : scripts/

 Description :
 Script autonome de rotation des logs pour le dossier scripts/logs/.
 Archive chaque mois les fichiers .log (sauf rotate_logs.log) dont la date de modification correspond au mois précédent, dans une archive zip nommée logs_YYYY-MM.zip.
 Supprime les archives zip de plus d'un an.
 Toutes les opérations et erreurs sont tracées dans scripts/logs/rotate_logs.log via logMsg().
 Un mail récapitulatif est envoyé à l'admin à la fin de l'exécution, listant les fichiers archivés et supprimés.

 Fonctionnement :
 - Recherche tous les fichiers .log du dossier scripts/logs/ (hors rotate_logs.log).
 - Archive ceux dont la date de modification est dans le mois précédent.
 - Supprime les archives zip de plus d'un an.
 - Logue chaque étape et envoie un mail récapitulatif.

 Utilisation :
 - À lancer en tâche planifiée (cron) en début de chaque mois.
 - Vérifier le log rotate_logs.log ou le mail en cas d'anomalie.

 Auteur :
 - Automatisé avec GitHub Copilot
-------------------------------------------------------------
*/
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/log_func.php';
require_once __DIR__ . '/../includes/mail_utils.php';

// Dossier logs à traiter : uniquement scripts/logs/
// Variables pour le mail récap
$logDir = __DIR__ . '/logs/';
$logFile = $logDir . 'rotate_logs.log';
$mailSummaryEnabled = true;
$archivedFiles = [];
$deletedArchives = [];
logMsg('[TRACE] Démarrage de la rotation des logs', $logFile);
if (!is_dir($logDir)) {
    $msg = "Dossier logs introuvable: $logDir";
    logMsg($msg, $logFile);
    echo "$msg\n";
    exit;
}

// Détermination de la période à archiver (mois précédent)
$lastMonth = date("Y-m", strtotime("first day of last month"));
$zipFile = $logDir . "logs_" . $lastMonth . ".zip";
if (!file_exists($zipFile)) {
    // On prend tous les .log sauf rotate_logs.log, dont la date de modif est dans le mois précédent
    $allLogs = glob($logDir . "*.log");
    $files = array_filter($allLogs, function($f) use ($logFile, $lastMonth) {
        if (basename($f) === basename($logFile)) return false;
        $ts = filemtime($f);
        $fileMonth = date("Y-m", $ts);
        return $fileMonth === $lastMonth;
    });
    logMsg("[TRACE] Fichiers logs à archiver pour $lastMonth (par date de modif): " . implode(', ', $files), $logFile);
    if (!empty($files)) {
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
            foreach ($files as $file) {
                $zip->addFile($file, basename($file));
                $archivedFiles[] = basename($file);
            }
            $zip->close();
            foreach ($files as $file) {
                unlink($file);
            }
            $msg = "Archive créée: $zipFile";
            logMsg($msg, $logFile);
            echo "$msg\n";
        } else {
            $msg = "Erreur: impossible de créer l’archive $zipFile";
            logMsg($msg, $logFile);
            echo "$msg\n";
        }
    } else {
        logMsg("[TRACE] Aucun fichier à archiver pour $lastMonth", $logFile);
    }
} else {
    logMsg("[TRACE] Archive déjà existante pour $lastMonth : $zipFile", $logFile);
}
// Suppression des archives de plus d'un an
foreach (glob($logDir . "logs_*.zip") as $zip) {
    // Ne jamais supprimer rotate_logs.log (ce n'est pas une archive zip, mais sécurité)
    if (basename($zip) === basename($logFile)) continue;
    if (filemtime($zip) < strtotime("-1 year")) {
        unlink($zip);
        $msg = "Archive supprimée: $zip";
        $deletedArchives[] = basename($zip);
        logMsg($msg, $logFile);
        echo "$msg\n";
    }
}
logMsg('[TRACE] Fin de la rotation des logs', $logFile);

// Envoi du mail récapitulatif
if ($mailSummaryEnabled && function_exists('sendSummaryMail')) {
    $subject = str_replace('{date}', date('d/m/Y H:i'), t('script_rotate_mail_subject'));
    $body = t('script_rotate_mail_intro') . "\n";
    if (!empty($archivedFiles)) {
        $body .= "\n" . t('script_rotate_mail_archived') . "\n";
        foreach ($archivedFiles as $f) {
            $body .= " - $f\n";
        }
    } else {
        $body .= "\n" . t('script_rotate_mail_no_archive') . "\n";
    }
    if (!empty($deletedArchives)) {
        $body .= "\n" . t('script_rotate_mail_deleted') . "\n";
        foreach ($deletedArchives as $a) {
            $body .= " - $a\n";
        }
    }
    $body .= "\n" . t('script_fret_mail_automatic');
    $to = VA_ADMIN_EMAIL;
    $mailResult = sendSummaryMail($subject, $body, $to);
    if ($mailResult === true || $mailResult === null) {
        logMsg("Mail récapitulatif envoyé à $to", $logFile);
    } else {
        logMsg("Erreur lors de l'envoi du mail récapitulatif : $mailResult", $logFile);
    }
}
?>
