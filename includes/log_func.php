<?php
/*
-------------------------------------------------------------
 Script : log_func.php
 Emplacement : includes/

 Description :
 Fonction de logging harmonisée pour tous les scripts de l'application.
 Permet d'enregistrer des messages horodatés dans des fichiers de log.

 Utilisation :
 - logMsg("Message") : Log dans scripts/logs/general.log
 - logMsg("Message", "expire_reservations") : Log dans scripts/logs/expire_reservations.log
 - logMsg("Message", "/chemin/complet/fichier.log") : Log dans le fichier spécifié

 Auteur :
 - Équipe de développement SimWeb
-------------------------------------------------------------
*/

/**
 * Enregistre un message horodaté dans un fichier de log.
 *
 * @param string $msg Le message à enregistrer dans le log
 * @param string|null $logFile Le fichier de log (nom court, chemin relatif ou absolu). 
 *                             null = scripts/logs/general.log par défaut
 * @return void
 */
function logMsg($msg, $logFile = null) {
    // Ensure logs directory exists
    $defaultLogDir = __DIR__ . '/../scripts/logs';
    if (!is_dir($defaultLogDir)) {
        @mkdir($defaultLogDir, 0755, true);
    }

    // Determine final log file path.
    // Accept either:
    //  - null -> default general.log in scripts/logs
    //  - a full/relative path (contains a directory separator or a drive letter on Windows)
    //  - a short key like 'expire_reservations' -> maps to scripts/logs/expire_reservations.log
    if ($logFile === null) {
        $filePath = $defaultLogDir . '/general.log';
    } else {
        $hasSeparator = (strpos($logFile, '/') !== false) || (strpos($logFile, '\\') !== false);
        $isWindowsAbs = preg_match('#^[A-Za-z]:\\\\#', $logFile) || preg_match('#^[A-Za-z]:/#', $logFile);
        if ($hasSeparator || $isWindowsAbs) {
            // treat as path as provided
            $filePath = $logFile;
        } else {
            // treat as short name and store under default logs dir with .log suffix
            $filePath = $defaultLogDir . '/' . $logFile . '.log';
        }
    }

    $date = date('Y-m-d H:i:s');
    $line = "$date $msg\n";
    @file_put_contents($filePath, $line, FILE_APPEND | LOCK_EX);
}
