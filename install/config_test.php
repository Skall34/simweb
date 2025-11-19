<?php
/**
 * Configuration de la Virtual Airline
 * Généré automatiquement par l'installateur le 2025-11-18 20:39:11
 */

// ==================== BASE DE DONNÉES ====================

define('DB_HOST', 'localhost');
define('DB_NAME', 'test_va_db');
define('DB_USER', 'test_user');
define('DB_PASS', 'test_pass');
define('DB_CHARSET', 'utf8mb4');

// ==================== INFORMATIONS COMPAGNIE ====================

define('VA_NAME', 'Test Virtual Airlines');
define('VA_ICAO', 'TVA');
define('VA_IATA', 'TV');
define('VA_TAGLINE', 'La meilleure VA de test');

// ==================== CONTACT ====================

define('VA_CONTACT_EMAIL', 'contact@testva.com');
define('VA_ADMIN_EMAIL', 'admin@testva.com');

// ==================== ADMINISTRATION ====================

define('VA_ADMIN_CALLSIGNS', 'ADM0001');
define('VA_BASE_URL', 'https://www.testva.com');

// ==================== CONFIGURATION SMTP ====================

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'test@gmail.com');
define('SMTP_PASSWORD', 'password123');
define('SMTP_FROM_EMAIL', 'noreply@testva.com');
define('SMTP_FROM_NAME', 'Test VA');

// ==================== RÉSEAUX SOCIAUX ====================

define('VA_DISCORD_URL', 'https://discord.gg/test');
define('VA_WEBSITE_URL', '');
define('VA_FORUM_URL', '');

// ==================== PARAMÈTRES FINANCIERS ====================

define('VA_CURRENCY', 'EUR');
define('VA_CURRENCY_SYMBOL', '€');
define('VA_CURRENCY_POSITION', 'after');
define('VA_STARTING_BALANCE', 15000);

// ==================== PARAMÈTRES SYSTÈME ====================

define('VA_TIMEZONE', 'Europe/Paris');
define('VA_DEFAULT_LANGUAGE', 'fr');
define('VA_REGISTRATION_ENABLED', true);
define('VA_MIN_FLIGHTS_FOR_PROMOTION', 10);

// ==================== PARAMÈTRES SIMADDON ====================

define('VA_SIMADDON_ENABLED', true);
define('VA_SIMADDON_API_URL', 'https://api.simaddon.com');

// ==================== MODE DEBUG ====================

define('VA_DEBUG_MODE', false);

// ==================== NE PAS MODIFIER ====================

date_default_timezone_set(VA_TIMEZONE);

if (VA_DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    ini_set('display_errors', 0);
}
