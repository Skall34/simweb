<?php
require_once __DIR__ . '/../includes/require_admin.php';
require_once __DIR__ . '/../lang.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/menu_logged.php';

// Traitement du formulaire
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message_accueil'] ?? '');
    $lien_acars = trim($_POST['lien_acars'] ?? '');
    
    if (mb_strlen($message) > 300) {
        $error = t('admin_welcome_error_length');
    } else {
        // Stocker le message d'accueil dans VARIABLES_CONFIG
        $stmt = $pdo->prepare("REPLACE INTO VARIABLES_CONFIG (nom, valeur) VALUES ('message_accueil', :valeur)");
        $stmt->execute(['valeur' => $message]);
        
        // Stocker le lien ACARS dans VARIABLES_CONFIG
        $stmt = $pdo->prepare("REPLACE INTO VARIABLES_CONFIG (nom, valeur) VALUES ('lien_acars', :valeur)");
        $stmt->execute(['valeur' => $lien_acars]);
        
        $success = t('admin_site_success');
    }
}

// Charger les valeurs actuelles
$stmt = $pdo->prepare("SELECT valeur FROM VARIABLES_CONFIG WHERE nom = 'message_accueil'");
$stmt->execute();
$message_actuel = $stmt->fetchColumn() ?: '';

$stmt = $pdo->prepare("SELECT valeur FROM VARIABLES_CONFIG WHERE nom = 'lien_acars'");
$stmt->execute();
$lien_acars_actuel = $stmt->fetchColumn() ?: 'assets/acars/simaddon_setup.zip';
?>
<main style="max-width:600px;margin:32px auto;">
    <h2><?= t('admin_site_title') ?></h2>
    <?php if ($success): ?><p style="color:green;font-weight:bold;"><?= $success ?></p><?php endif; ?>
    <?php if ($error): ?><p style="color:red;font-weight:bold;"><?= $error ?></p><?php endif; ?>
    <form method="post" action="">
        <!-- Section Message d'accueil -->
        <fieldset style="margin-bottom:20px;padding:15px;border:1px solid #ccc;border-radius:8px;">
            <legend style="font-weight:bold;padding:0 10px;"><?= t('admin_site_section_welcome') ?></legend>
            <label for="message_accueil"><?= t('admin_welcome_label') ?></label><br>
            <textarea id="message_accueil" name="message_accueil" rows="3" maxlength="300" style="width:100%;resize:none;margin-top:5px;"><?= htmlspecialchars($message_actuel) ?></textarea>
        </fieldset>
        
        <!-- Section Lien ACARS -->
        <fieldset style="margin-bottom:20px;padding:15px;border:1px solid #ccc;border-radius:8px;">
            <legend style="font-weight:bold;padding:0 10px;"><?= t('admin_site_section_acars') ?></legend>
            <div style="background-color:#fff3cd;border:1px solid #ffc107;border-radius:5px;padding:10px;margin-bottom:12px;color:#856404;">
                ⚠️ <?= t('admin_site_acars_warning') ?>
            </div>
            <label for="lien_acars"><?= t('admin_site_acars_label') ?></label><br>
            <input type="text" id="lien_acars" name="lien_acars" value="<?= htmlspecialchars($lien_acars_actuel) ?>" style="width:100%;padding:8px;margin-top:5px;box-sizing:border-box;">
            <small style="color:#666;"><?= t('admin_site_acars_help') ?></small>
        </fieldset>
        
        <div style="text-align:center;margin-top:12px;">
            <button type="submit" class="btn"><?= t('admin_welcome_btn_save') ?></button>
        </div>
    </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
