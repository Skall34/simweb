<?php
session_start();
require 'includes/db_connect.php'; // à créer: connexion à la base

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $callsign = $_POST['callsign'] ?? '';
    $password = $_POST['password'] ?? '';

    // Prépare et execute requête
    $stmt = $pdo->prepare('SELECT * FROM PILOTES WHERE actif=1 AND callsign = ?');
    $stmt->execute([$callsign]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Auth OK
        $_SESSION['user'] = [
            'id' => $user['id'],
            'callsign' => $user['callsign']
        ];
        // Ajout : instanciation explicite du callsign pour la session
        $_SESSION['callsign'] = $user['callsign'];
        // Mise à jour de la date de dernière connexion
        $update = $pdo->prepare("UPDATE PILOTES SET derniere_connexion = NOW() WHERE id = :id");
        $update->execute(['id' => $user['id']]);
        header('Location: index.php');
        exit;
    } else {
        $error = "Login ou mot de passe incorrect.";
    }
}
include 'includes/header.php';
?>

<main>
    <div class="login-container">
        <h2 class="text-center">Connexion</h2>
        <?php if (!empty($error)) echo "<p class='flash-error' style='text-align:center;margin-bottom:1em;'>" . htmlspecialchars($error) . "</p>"; ?>
        <form method="post" action="login.php" class="form-column">
            <label class="login-label">Callsign<br>
                <input type="text" name="callsign" required class="form-input">
            </label>
            <label class="login-label">Mot de passe<br>
                <input type="password" name="password" required class="form-input">
            </label>
            <button type="submit" class="btn btn-full">Se connecter</button>
        </form>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
