<?php
require_once __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/../includes/header.php';

$msg = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $stmt = $pdo->prepare("SELECT email, expires_at FROM password_resets WHERE token = :token");
    $stmt->execute(['token' => $token]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && strtotime($row['expires_at']) > time()) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newpass = $_POST['newpass'];
            $hash = password_hash($newpass, PASSWORD_DEFAULT);

            // Met à jour le mot de passe dans la table PILOTES
            $stmt = $pdo->prepare("UPDATE PILOTES SET password = :hash WHERE email = :email");
            $stmt->execute(['hash' => $hash, 'email' => $row['email']]);

            // Supprime le token
            $pdo->prepare("DELETE FROM password_resets WHERE token = :token")->execute(['token' => $token]);

            $msg = "Votre mot de passe a bien été réinitialisé !";
        }
        ?>
        <main>
            <h2>Réinitialisation du mot de passe</h2>
            <?php if ($msg): ?>
                <div class="alert success"><?= htmlspecialchars($msg) ?></div>
            <?php else: ?>
                <form method="post" class="form-inscription" style="max-width:400px;">
                    <div class="form-group">
                        <label for="newpass">Nouveau mot de passe :</label>
                        <input type="password" name="newpass" id="newpass" required>
                    </div>
                    <button type="submit" class="btn">Changer le mot de passe</button>
                </form>
            <?php endif; ?>
        </main>
        <?php
    } else {
        ?>
        <main>
            <div class="alert error">Lien invalide ou expiré.</div>
        </main>
        <?php
    }
} else {
    ?>
    <main>
        <div class="alert error">Token manquant.</div>
    </main>
    <?php
}

include __DIR__ . '/../includes/footer.php';
?>