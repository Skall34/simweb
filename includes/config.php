<?php
/**
 * Lecteur de configuration depuis config.ini
 * 
 * Ce fichier lit le fichier config.ini situé à la racine du projet
 * et initialise toutes les constantes de configuration nécessaires.
 * 
 * @version 1.0
 */

// Localiser le fichier config.ini à la racine du projet
$configPath = __DIR__ . '/../config.ini';

// Vérifier que le fichier existe
if (!file_exists($configPath)) {
    die('Erreur : Fichier de configuration config.ini introuvable à la racine du projet.');
}

// Lire le fichier config.ini
$config = parse_ini_file($configPath, true);

if ($config === false) {
    die('Erreur : Impossible de lire le fichier config.ini.');
}

// ==================== BASE DE DONNÉES ====================

define('DB_HOST', $config['database']['host'] ?? 'localhost');
define('DB_NAME', $config['database']['name'] ?? '');
define('DB_USER', $config['database']['user'] ?? '');
define('DB_PASS', $config['database']['password'] ?? '');
define('DB_CHARSET', $config['database']['charset'] ?? 'utf8mb4');

// ==================== INFORMATIONS COMPAGNIE ====================

define('VA_NAME', $config['company']['name'] ?? 'Virtual Airline');
define('VA_ICAO', $config['company']['icao'] ?? 'VA');
define('VA_IATA', $config['company']['iata'] ?? 'VA');
define('VA_TAGLINE', $config['company']['tagline'] ?? '');

// ==================== CONTACT ====================

define('VA_CONTACT_EMAIL', $config['contact']['contact_email'] ?? '');
define('VA_ADMIN_EMAIL', $config['contact']['admin_email'] ?? '');

// ==================== ADMINISTRATION ====================

define('VA_SUPER_ADMIN_CALLSIGNS', $config['admin']['super_admin_callsigns'] ?? '');
define('VA_BASE_URL', $config['admin']['base_url'] ?? '');

// ==================== CONFIGURATION SMTP ====================

define('SMTP_HOST', $config['smtp']['host'] ?? 'localhost');
define('SMTP_PORT', (int)($config['smtp']['port'] ?? 587));
define('SMTP_SECURE', $config['smtp']['secure'] ?? 'tls');
define('SMTP_USERNAME', $config['smtp']['username'] ?? '');
define('SMTP_PASSWORD', $config['smtp']['password'] ?? '');
define('SMTP_FROM_EMAIL', $config['smtp']['from_email'] ?? '');
define('SMTP_FROM_NAME', $config['smtp']['from_name'] ?? '');

// ==================== RÉSEAUX SOCIAUX ====================

define('VA_DISCORD_URL', $config['social']['discord_url'] ?? '');
define('VA_WEBSITE_URL', $config['social']['website_url'] ?? '');
define('VA_FORUM_URL', $config['social']['forum_url'] ?? '');

// ==================== PARAMÈTRES FINANCIERS ====================

define('VA_CURRENCY', $config['financial']['currency'] ?? 'EUR');
define('VA_CURRENCY_SYMBOL', $config['financial']['currency_symbol'] ?? '€');
define('VA_CURRENCY_POSITION', $config['financial']['currency_position'] ?? 'after');
define('VA_STARTING_BALANCE', (float)($config['financial']['starting_balance'] ?? 0));

// ==================== PARAMÈTRES SYSTÈME ====================

define('VA_TIMEZONE', $config['system']['timezone'] ?? 'UTC');
define('VA_DEFAULT_LANGUAGE', $config['system']['default_language'] ?? 'en');

// ==================== MODE DEBUG ====================

define('VA_DEBUG_MODE', filter_var($config['debug']['debug_mode'] ?? false, FILTER_VALIDATE_BOOLEAN));

// ==================== CONFIGURATION SYSTÈME ====================

date_default_timezone_set(VA_TIMEZONE);

if (VA_DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    ini_set('display_errors', 0);
}
