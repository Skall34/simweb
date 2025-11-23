<?php
/**
 * Installation Wizard - Virtual Airline Management System
 * Version: 2.0
 * 
 * Installation automatisée avec interface guidée
 */

// Vérifier si l'installation est déjà faite
// UNIQUEMENT avec le fichier .installed créé à la toute fin




session_start();

// Initialiser l'étape
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$step = max(1, min(5, $step)); // Entre 1 et 5

// Récupérer les données de session
$data = isset($_SESSION['install_data']) ? $_SESSION['install_data'] : [];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Virtual Airline System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🛫 Installation Virtual Airline</h1>
            <p class="subtitle">Configuration automatisée en 5 étapes</p>
        </header>

        <!-- Barre de progression -->
        <div class="progress-bar">
            <div class="progress-step <?= $step >= 1 ? 'active' : '' ?> <?= $step > 1 ? 'completed' : '' ?>">
                <div class="step-number">1</div>
                <div class="step-label">Vérifications</div>
            </div>
            <div class="progress-step <?= $step >= 2 ? 'active' : '' ?> <?= $step > 2 ? 'completed' : '' ?>">
                <div class="step-number">2</div>
                <div class="step-label">Base de données</div>
            </div>
            <div class="progress-step <?= $step >= 3 ? 'active' : '' ?> <?= $step > 3 ? 'completed' : '' ?>">
                <div class="step-number">3</div>
                <div class="step-label">Configuration VA</div>
            </div>
            <div class="progress-step <?= $step >= 4 ? 'active' : '' ?> <?= $step > 4 ? 'completed' : '' ?>">
                <div class="step-number">4</div>
                <div class="step-label">Installation</div>
            </div>
            <div class="progress-step <?= $step >= 5 ? 'active' : '' ?>">
                <div class="step-number">5</div>
                <div class="step-label">Terminé</div>
            </div>
        </div>

        <main>
            <?php
            // Inclure l'étape appropriée
            $step_file = __DIR__ . "/steps/step{$step}.php";
            if (file_exists($step_file)) {
                include $step_file;
            } else {
                echo '<div class="error">Erreur : Étape introuvable</div>';
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
