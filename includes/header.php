<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>SkyWings VA</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/images/favicon-16x16.png">
</head>
<body>
<header>
    <div class="bandeau">
        <div class="logo-nom">
            <img src="/assets/images/logo.png" alt="Logo" class="logo">
            <span class="nom-compagnie">
                <a href="/index.php" style="color: inherit; text-decoration: none;">SkyWings Virtual Airline</a>
            </span>
        </div>

        <div class="formulaire-login">
            <?php if (!isset($_SESSION['user'])): ?>
                <form method="post" action="/login.php">
                    <input type="text" name="callsign" placeholder="Callsign" required>
                    <input type="password" name="password" placeholder="Mot de passe" required>
                    <button type="submit">Connexion</button>
                </form>
                <div style="margin-top: 5px;">
                    <a href="/pages/forgot_password.php" style="font-size: 0.9em; color: #007bff; text-decoration: underline;">Mot de passe oublié ?</a>
                </div>
            <?php else: ?>
                <p>Bienvenue, <?= htmlspecialchars($_SESSION['user']['callsign']) ?> |
                    <a href="/logout.php">Déconnexion</a></p>
            <?php endif; ?>
        </div>
    </div>
</header>
