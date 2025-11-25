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
    // Also support placeholders with braces {param}
    foreach ($params as $param => $value) {
        $text = str_replace('{' . $param . '}', $value, $text);
    }
    
    // Replace {VA_NAME}, {VA_CONTACT_EMAIL}, etc. with config values
    if (defined('VA_NAME')) {
        $text = str_replace('{VA_NAME}', VA_NAME, $text);
    }
    if (defined('VA_CONTACT_EMAIL')) {
        $text = str_replace('{VA_CONTACT_EMAIL}', VA_CONTACT_EMAIL, $text);
    }
    if (defined('VA_ADMIN_EMAIL')) {
        $text = str_replace('{VA_ADMIN_EMAIL}', VA_ADMIN_EMAIL, $text);
    }
    // Auto-replace common dynamic placeholders
    // {year} : current year
    $text = str_replace('{year}', date('Y'), $text);
    $text = str_replace('{YEAR}', date('Y'), $text);
    
    return $text;
}
