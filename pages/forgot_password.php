<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../includes/PHPMailer/SMTP.php';
require_once __DIR__ . '/../includes/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Vérifier si l'email existe dans la table PILOTES
    $stmt = $pdo->prepare("SELECT id FROM PILOTES WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);

        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires)");
        $stmt->execute(['email' => $email, 'token' => $token, 'expires' => $expires]);

        $resetLink = "https://www.skywings.ovh/pages/reset_password.php?token=$token";
        $subject = "Réinitialisation de votre mot de passe Skywings";
        $body = "Bonjour cher pilote Skywings,\n\nPour réinitialiser votre mot de passe, cliquez sur le lien suivant:\n $resetLink\n\nCe lien expire dans 1 heure.";

        $mailer = new PHPMailer(true);
        try {
            $mailer->isSMTP();
            $mailer->Host = 'ssl0.ovh.net';
            $mailer->SMTPAuth = true;
            $mailer->Username = 'admin@skywings.ovh';
            $mailer->Password = 'La6mulationCestCool!';
            $mailer->SMTPSecure = 'tls';
            $mailer->Port = 587;
            $mailer->setFrom('admin@skywings.ovh', 'Skywings');
            $mailer->addAddress($email);
            $mailer->Subject = $subject;
            $mailer->CharSet = 'UTF-8';
            $mailer->Body = $body;
            $mailer->send();
            $msg = "Un email de réinitialisation vient de vous être envoyé.";
        } catch (Exception $e) {
            $msg = "Erreur d'envoi de mail : " . $mailer->ErrorInfo;
        }
    } else {
        $msg = "Adresse email inconnue.";
    }
}
include __DIR__ . '/../includes/header.php';
?>

<main>
    <h2>Mot de passe oublié</h2>
    <form method="post" class="form-inscription" autocomplete="off" style="max-width:400px;">
        <div class="form-group">
            <label for="email">Email :</label>
            <input type="email" name="email" id="email" required>
        </div>
        <button type="submit" class="btn">Réinitialiser</button>
    </form>
    <?php if ($msg): ?>
        <div class="alert <?= strpos($msg, 'Erreur') === false ? 'success' : 'error' ?>">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>