<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/rate_limit.php';
require_once __DIR__ . '/../includes/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../includes/PHPMailer/SMTP.php';
require_once __DIR__ . '/../includes/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le rate limiting (3 tentatives max par 5 minutes)
    $rateCheck = checkRateLimit($pdo, 'forgot_password', 3, 300);
    if (!$rateCheck['allowed']) {
        $waitMinutes = ceil($rateCheck['wait_seconds'] / 60);
        $msg = t('forgot_error_rate_limit', ['minutes' => $waitMinutes]);
    } else {
    $email = trim($_POST['email']);

    // Vérifier si l'email existe dans la table PILOTES
    $stmt = $pdo->prepare("SELECT id FROM PILOTES WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);

        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires)");
        $stmt->execute(['email' => $email, 'token' => $token, 'expires' => $expires]);

        $resetLink = VA_BASE_URL . "/pages/reset_password.php?token=$token";
        $subject = t('forgot_mail_subject');
        $body = t('forgot_mail_body', ['resetLink' => $resetLink]);

        $mailer = new PHPMailer(true);
        try {
            $mailer->isSMTP();
            $mailer->Host = SMTP_HOST;
            $mailer->SMTPAuth = true;
            $mailer->Username = SMTP_USERNAME;
            $mailer->Password = SMTP_PASSWORD;
            $mailer->SMTPSecure = SMTP_SECURE;
            $mailer->Port = SMTP_PORT;
            $mailer->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mailer->addAddress($email);
            $mailer->Subject = $subject;
            $mailer->CharSet = 'UTF-8';
            $mailer->Body = $body;
            $mailer->send();
            $msg = t('forgot_success_mail');
        } catch (Exception $e) {
            $msg = t('forgot_error_mail') . $mailer->ErrorInfo;
        }
    } else {
        $msg = t('forgot_error_email_unknown');
    }
    } // Fin du if rate limit allowed
}
include __DIR__ . '/../includes/header.php';
?>

<main>
    <h2><?= t('forgot_title') ?></h2>
    <form method="post" class="form-inscription" autocomplete="off" style="max-width:400px;">
        <div class="form-group">
            <label for="email"><?= t('forgot_email_label') ?></label>
            <input type="email" name="email" id="email" required>
        </div>
        <button type="submit" class="btn"><?= t('forgot_submit') ?></button>
    </form>
    <?php if ($msg): ?>
        <div class="alert <?= strpos($msg, 'Erreur') === false ? 'success' : 'error' ?>">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>