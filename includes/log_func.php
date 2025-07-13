<?php
// includes/log_func.php
// Fonction de log harmonisée pour tous les scripts

function logMsg($msg, $logFile = null) {
    if ($logFile === null) {
        $logFile = __DIR__ . '/../scripts/logs/general.log';
    }
    $date = date('Y-m-d H:i:s');
    $line = "$date $msg\n";
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}
