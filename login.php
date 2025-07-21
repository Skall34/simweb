<?php
session_start();
require 'includes/db_connect.php'; // à créer: connexion à la base

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $callsign = $_POST['callsign'] ?? '';
    $password = $_POST['password'] ?? '';

    // Prépare et execute requête
    $stmt = $pdo->prepare('SELECT * FROM PILOTES WHERE callsign = ?');
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
    <div class="login-container" style="max-width:340px;margin:40px auto 0 auto;padding:32px 28px;background:#f7fbff;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.07);">
        <h2 style="text-align:center;margin-bottom:1.2em;">Connexion</h2>
        <?php if (!empty($error)) echo "<p style='color:#d32f2f;text-align:center;font-weight:bold;margin-bottom:1em;'>$error</p>"; ?>
        <form method="post" action="login.php" style="display:flex;flex-direction:column;gap:18px;">
            <label style="font-weight:600;color:#0d47a1;">Callsign<br>
                <input type="text" name="callsign" required style="width:100%;padding:8px 10px;border-radius:6px;border:1px solid #b0bec5;font-size:1em;">
            </label>
            <label style="font-weight:600;color:#0d47a1;">Mot de passe<br>
                <input type="password" name="password" required style="width:100%;padding:8px 10px;border-radius:6px;border:1px solid #b0bec5;font-size:1em;">
            </label>
            <button type="submit" class="btn" style="width:100%;background:#1976d2;color:#fff;font-weight:bold;padding:10px 0;border:none;border-radius:6px;font-size:1.1em;cursor:pointer;">Se connecter</button>
        </form>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
