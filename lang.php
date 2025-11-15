<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function load_lang() {
    if (isset($_GET['lang'])) {
        $_SESSION['lang'] = $_GET['lang'];
    }

    $lang = $_SESSION['lang'] ?? 'fr'; // par défaut français

    $file = __DIR__ . "/lang/$lang.php";

    if (file_exists($file)) {
        return include $file;
    }

    return include __DIR__ . "/lang/fr.php"; // fallback
}

$LANG = load_lang();

function t($key, $params = []) {
    global $LANG;
    $text = $LANG[$key] ?? $key;
    
    // Replace placeholders like :param with values from $params array
    foreach ($params as $param => $value) {
        $text = str_replace(':' . $param, $value, $text);
    }
    
    return $text;
}
