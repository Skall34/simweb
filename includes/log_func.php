<?php
// includes/log_func.php
// Fonction de log harmonisée pour tous les scripts

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
