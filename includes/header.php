<?php
/*
-------------------------------------------------------------
 Script : header.php
 Emplacement : includes/

 Description :
 En-tête HTML principal de l'application avec gestion de la sécurité,
 du logo, du formulaire de connexion et du sélecteur de langue.

 Fonctionnalités :
 - Headers de sécurité HTTP (X-Frame-Options, X-Content-Type-Options, etc.)
 - Affichage du logo et nom de la compagnie
 - Formulaire de connexion pour les visiteurs
 - Message de bienvenue et déconnexion pour les utilisateurs connectés
 - Sélecteur de langue (FR/EN/ES)

 Utilisation :
 - À inclure au début de chaque page : require_once __DIR__ . '/includes/header.php';
 - Doit être suivi de menu_guest.php ou menu_logged.php selon l'état de connexion.

 Auteur :
 - Équipe de développement SimWeb
-------------------------------------------------------------
*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Headers HTTP de sécurité
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../lang.php';
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'fr' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= t('site_title'); ?></title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/images/favicon-16x16.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
</head>
<body>
<header>
    <div class="bandeau">
        <div class="logo-nom">
            <img src="/assets/images/logo.png" alt="Logo" class="logo">
            <span class="nom-compagnie">
                <a href="/index.php" style="color: inherit; text-decoration: none;">
                    <?= t('company_name'); ?>
                </a>
            </span>
        </div>
        <div style="position: absolute; top: 53px; right: 12px; z-index: 10;">
            <form method="get" id="lang-switcher-form" style="margin:0;">
                <select name="lang" id="lang-switcher" style="padding:2px 8px; border-radius:4px; border:1px solid #ccc; background:#fff;">
                    <option value="fr" <?= ($_SESSION['lang'] ?? 'fr') === 'fr' ? 'selected' : '' ?>>Français</option>
                    <option value="en" <?= ($_SESSION['lang'] ?? 'fr') === 'en' ? 'selected' : '' ?>>English</option>
                    <option value="es" <?= ($_SESSION['lang'] ?? 'fr') === 'es' ? 'selected' : '' ?>>Español</option>
                </select>
            </form>
            <script>
            document.getElementById('lang-switcher').addEventListener('change', function() {
                document.getElementById('lang-switcher-form').submit();
            });
            </script>
        </div>
        <div class="formulaire-login">
            <?php if (!isset($_SESSION['user'])): ?>
                <form method="post" action="/login.php">
                    <input type="text" name="callsign" placeholder="<?= t('login_callsign'); ?>" required>
                    <input type="password" name="password" placeholder="<?= t('login_password'); ?>" required>
                    <button type="submit"><?= t('login_button'); ?></button>
                </form>
                <div style="margin-top: 5px;">
                    <a href="/pages/forgot_password.php"
                       style="font-size: 0.9em; color: #007bff; text-decoration: underline;">
                        <?= t('forgot_password'); ?>
                    </a>
                </div>
            <?php else: ?>
                <p>
                    <?= t('welcome_user'); ?>,
                    <?= htmlspecialchars($_SESSION['user']['callsign']) ?>
                    |
                    <a href="/logout.php"><?= t('logout'); ?></a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</header>
