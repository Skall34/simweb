<?php
/**
 * Configuration générale de la Virtual Airline
 * 
 * INSTRUCTIONS :
 * 1. Renommez ce fichier en config.php
 * 2. Modifiez les valeurs ci-dessous selon votre compagnie
 * 3. Ne modifiez PAS config_exemple.php (gardez-le comme référence)
 */

// ==================== BASE DE DONNÉES ====================

// Hôte de la base de données
define('DB_HOST', 'localhost');

// Nom de la base de données
define('DB_NAME', 'nom_de_votre_base');

// Utilisateur de la base de données
define('DB_USER', 'votre_utilisateur');

// Mot de passe de la base de données
define('DB_PASS', 'votre_mot_de_passe');

// Charset de la base de données
define('DB_CHARSET', 'utf8mb4');

// ==================== INFORMATIONS COMPAGNIE ====================

// Nom de votre Virtual Airline
define('VA_NAME', 'Nom de votre VA');

// Code ICAO de votre compagnie (3-4 lettres)
define('VA_ICAO', 'SKW');

// Code IATA de votre compagnie (2 lettres, optionnel)
define('VA_IATA', 'SW');

// Slogan ou description courte
define('VA_TAGLINE', 'Votre compagnie aérienne virtuelle');

// ==================== CONTACT ====================

// Email de contact principal (support, questions générales)
define('VA_CONTACT_EMAIL', 'contact@votre-domaine.com');

// Email administrateur (notifications système)
define('VA_ADMIN_EMAIL', 'admin@votre-domaine.com');

// ==================== ADMINISTRATION ====================

// Callsigns des super-administrateurs (séparés par des virgules)
define('VA_ADMIN_CALLSIGNS', 'XXX0001,XXX0002');

// URL de base du site (sans slash final)
define('VA_BASE_URL', 'https://www.votre-domaine.com');

// ==================== CONFIGURATION SMTP ====================

// Serveur SMTP
define('SMTP_HOST', 'smtp.votre-serveur.com');

// Port SMTP (587 pour TLS, 465 pour SSL)
define('SMTP_PORT', 587);

// Sécurité (tls ou ssl)
define('SMTP_SECURE', 'tls');

// Authentification SMTP
define('SMTP_USERNAME', 'votre-email@votre-domaine.com');
define('SMTP_PASSWORD', 'votre-mot-de-passe');

// Email d'expédition (From)
define('SMTP_FROM_EMAIL', 'noreply@votre-domaine.com');
define('SMTP_FROM_NAME', 'Nom de votre VA');

// ==================== RÉSEAUX SOCIAUX ====================

// Discord (laisser vide si vous n'en avez pas)
define('VA_DISCORD_URL', '');

// Site web (si différent de celui-ci)
define('VA_WEBSITE_URL', '');

// Forum
define('VA_FORUM_URL', '');

// ==================== PARAMÈTRES FINANCIERS ====================

// Devise utilisée (EUR, USD, GBP, etc.)
define('VA_CURRENCY', 'EUR');

// Symbole de la devise
define('VA_CURRENCY_SYMBOL', '€');

// Position du symbole : 'before' (€100) ou 'after' (100€)
define('VA_CURRENCY_POSITION', 'after');

// Balance de départ pour nouveaux pilotes
define('VA_STARTING_BALANCE', 10000);

// ==================== PARAMÈTRES SYSTÈME ====================

// Fuseau horaire (liste : https://www.php.net/manual/fr/timezones.php)
define('VA_TIMEZONE', 'Europe/Paris');

// Langue par défaut (fr, en, es)
define('VA_DEFAULT_LANGUAGE', 'fr');

// Activer l'inscription des nouveaux pilotes (true/false)
define('VA_REGISTRATION_ENABLED', true);

// Nombre de vols minimum pour promotion automatique
define('VA_MIN_FLIGHTS_FOR_PROMOTION', 10);

// ==================== PARAMÈTRES SIMADDON ====================

// Activer l'intégration SimAddon (true/false)
define('VA_SIMADDON_ENABLED', true);

// URL de l'API SimAddon (ne pas modifier sauf cas particulier)
define('VA_SIMADDON_API_URL', 'https://api.simaddon.com');

// ==================== MODE DEBUG ====================

// Activer le mode debug (true = affiche les erreurs, false = masque)
// ⚠️ TOUJOURS false en production !
define('VA_DEBUG_MODE', false);

// ==================== NE PAS MODIFIER ====================

// Application du fuseau horaire
date_default_timezone_set(VA_TIMEZONE);

// Gestion des erreurs selon le mode
if (VA_DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    ini_set('display_errors', 0);
}
