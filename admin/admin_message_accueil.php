<?php
require_once __DIR__ . '/../includes/require_admin.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/menu_logged.php';

// Traitement du formulaire
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message_accueil'] ?? '');
    if (mb_strlen($message) > 300) {
        $error = t('admin_welcome_error_length');
    } else {
        // Stocker dans VARIABLES_CONFIG
        $stmt = $pdo->prepare("REPLACE INTO VARIABLES_CONFIG (nom, valeur) VALUES ('message_accueil', :valeur)");
        $stmt->execute(['valeur' => $message]);
        $success = t('admin_welcome_success');
    }
}
// Charger le message actuel
$stmt = $pdo->prepare("SELECT valeur FROM VARIABLES_CONFIG WHERE nom = 'message_accueil'");
$stmt->execute();
$message_actuel = $stmt->fetchColumn() ?: '';
?>
<main style="max-width:600px;margin:32px auto;">
    <h2><?= t('admin_welcome_title') ?></h2>
    <?php if ($success): ?><p style="color:green;font-weight:bold;"><?= $success ?></p><?php endif; ?>
    <?php if ($error): ?><p style="color:red;font-weight:bold;"><?= $error ?></p><?php endif; ?>
    <form method="post" action="">
        <label for="message_accueil"><?= t('admin_welcome_label') ?></label><br>
        <textarea id="message_accueil" name="message_accueil" rows="3" maxlength="300" style="width:100%;resize:none;"><?= htmlspecialchars($message_actuel) ?></textarea>
        <div style="text-align:center;margin-top:12px;">
            <button type="submit" class="btn"><?= t('admin_welcome_btn_save') ?></button>
        </div>
    </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
