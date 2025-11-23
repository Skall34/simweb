<?php
/**
 * Installation Wizard - Virtual Airline Management System
 * Version: 2.0
 * 
 * Installation automatisée avec interface guidée
 */

// Initialiser la session
session_start();

// Détection et gestion de la langue

// Détecter la langue du navigateur
function detect_browser_language() {
    $accept_lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'fr';
    $langs = explode(',', $accept_lang);
    foreach ($langs as $lang) {
        $lang = trim(explode(';', $lang)[0]);
        if (strpos($lang, 'fr') === 0) return 'fr';
        if (strpos($lang, 'es') === 0) return 'es';
        if (strpos($lang, 'en') === 0) return 'en';
    }
    return 'fr'; // Défaut
}

// Langue sélectionnée ou détectée
$lang = $_POST['lang'] ?? $_SESSION['install_lang'] ?? detect_browser_language();
$lang = in_array($lang, ['fr', 'en', 'es']) ? $lang : 'fr';
$_SESSION['install_lang'] = $lang;
// Synchroniser avec la clé utilisée par le chargeur principal de langue
$_SESSION['lang'] = $lang;
// Charger les traductions après avoir défini la langue souhaitée
require_once __DIR__ . '/../lang.php';

// Initialiser l'étape (préserver POST si le formulaire de langue a été soumis)
$step = isset($_POST['step']) ? (int)$_POST['step'] : (isset($_GET['step']) ? (int)$_GET['step'] : 1);
$step = max(1, min(5, $step)); // Entre 1 et 5

// Récupérer les données de session
$data = isset($_SESSION['install_data']) ? $_SESSION['install_data'] : [];

?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('install_title') ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <!-- Sélecteur de langue (visible et fixe en haut à droite) -->
        <div class="language-selector" style="position:fixed;top:12px;right:12px;z-index:1000;background:#ffffff;padding:8px 10px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.12);">
            <form method="POST" style="display: inline;">
                <label for="lang-select" style="font-weight:600;margin-right:6px;"><?= t('install_language_label') ?>:</label>
                <input type="hidden" name="step" value="<?= htmlspecialchars($step) ?>">
                <select id="lang-select" name="lang" onchange="this.form.submit()" style="padding:4px 6px;border-radius:6px;border:1px solid #ddd;">
                    <option value="fr" <?= $lang === 'fr' ? 'selected' : '' ?>>🇫🇷 Français</option>
                    <option value="en" <?= $lang === 'en' ? 'selected' : '' ?>>🇬🇧 English</option>
                    <option value="es" <?= $lang === 'es' ? 'selected' : '' ?>>🇪🇸 Español</option>
                </select>
            </form>
        </div>

        <header>
            <h1>🛫 <?= t('install_title_short') ?></h1>
            <p class="subtitle"><?= t('install_subtitle') ?></p>
        </header>

        <!-- Barre de progression -->
        <div class="progress-bar">
            <div class="progress-step <?= $step >= 1 ? 'active' : '' ?> <?= $step > 1 ? 'completed' : '' ?>">
                <div class="step-number">1</div>
                <div class="step-label"><?= t('install_step1_label') ?></div>
            </div>
            <div class="progress-step <?= $step >= 2 ? 'active' : '' ?> <?= $step > 2 ? 'completed' : '' ?>">
                <div class="step-number">2</div>
                <div class="step-label"><?= t('install_step2_label') ?></div>
            </div>
            <div class="progress-step <?= $step >= 3 ? 'active' : '' ?> <?= $step > 3 ? 'completed' : '' ?>">
                <div class="step-number">3</div>
                <div class="step-label"><?= t('install_step3_label') ?></div>
            </div>
            <div class="progress-step <?= $step >= 4 ? 'active' : '' ?> <?= $step > 4 ? 'completed' : '' ?>">
                <div class="step-number">4</div>
                <div class="step-label"><?= t('install_step4_label') ?></div>
            </div>
            <div class="progress-step <?= $step >= 5 ? 'active' : '' ?>">
                <div class="step-number">5</div>
                <div class="step-label"><?= t('install_step5_label') ?></div>
            </div>
        </div>

        <main>
            <?php
            // Inclure l'étape appropriée
            $step_file = __DIR__ . "/steps/step{$step}.php";
            if (file_exists($step_file)) {
                include $step_file;
            } else {
                echo '<div class="error">' . t('install_error_step_not_found') . '</div>';
            }
            ?>
        </main>

        <footer>
            <p>Virtual Airline Management System v2.0</p>
        </footer>
    </div>

    <script src="script.js"></script>
</body>
</html>
