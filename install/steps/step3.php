<?php
/**
 * Étape 3 : Configuration de la Virtual Airline
 */

// Vérifier que l'étape 2 est complétée
if (!isset($_SESSION['install_data']['database'])) {
    header('Location: ?step=2');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $va_name = trim($_POST['va_name'] ?? '');
    $va_email = trim($_POST['va_email'] ?? '');
    $va_url = trim($_POST['va_url'] ?? '');
    $va_timezone = trim($_POST['va_timezone'] ?? 'Europe/Paris');
    
    // SMTP optionnel
    $smtp_enabled = isset($_POST['smtp_enabled']);
    $smtp_host = trim($_POST['smtp_host'] ?? '');
    $smtp_port = trim($_POST['smtp_port'] ?? '587');
    $smtp_user = trim($_POST['smtp_user'] ?? '');
    $smtp_pass = $_POST['smtp_pass'] ?? '';
    $smtp_secure = $_POST['smtp_secure'] ?? 'tls';
    
    // Validation
    if (empty($va_name)) $errors[] = 'Le nom de la Virtual Airline est requis';
    if (empty($va_email) || !filter_var($va_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Une adresse email valide est requise';
    }
    if (empty($va_url) || !filter_var($va_url, FILTER_VALIDATE_URL)) {
        $errors[] = 'Une URL valide est requise';
    }
    
    if (empty($errors)) {
        // Sauvegarder en session
        $_SESSION['install_data']['config'] = [
            'va_name' => $va_name,
            'va_email' => $va_email,
            'va_url' => rtrim($va_url, '/'),
            'timezone' => $va_timezone,
            'smtp_enabled' => $smtp_enabled,
            'smtp_host' => $smtp_host,
            'smtp_port' => $smtp_port,
            'smtp_user' => $smtp_user,
            'smtp_pass' => $smtp_pass,
            'smtp_secure' => $smtp_secure
        ];
        
        header('Location: ?step=4');
        exit;
    }
} else {
    // Préremplir avec les valeurs de session si disponibles
    $va_name = $_SESSION['install_data']['config']['va_name'] ?? '';
    $va_email = $_SESSION['install_data']['config']['va_email'] ?? '';
    $va_url = $_SESSION['install_data']['config']['va_url'] ?? 'http://';
    $va_timezone = $_SESSION['install_data']['config']['timezone'] ?? 'Europe/Paris';
    $smtp_enabled = $_SESSION['install_data']['config']['smtp_enabled'] ?? false;
    $smtp_host = $_SESSION['install_data']['config']['smtp_host'] ?? '';
    $smtp_port = $_SESSION['install_data']['config']['smtp_port'] ?? '587';
    $smtp_user = $_SESSION['install_data']['config']['smtp_user'] ?? '';
    $smtp_pass = $_SESSION['install_data']['config']['smtp_pass'] ?? '';
    $smtp_secure = $_SESSION['install_data']['config']['smtp_secure'] ?? 'tls';
}

?>

<div class="step-content">
    <h2>⚙️ Configuration de votre Virtual Airline</h2>
    <p>Personnalisez les paramètres de votre VA.</p>

    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <h4>Erreurs de validation :</h4>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="install-form">
        <h3>Informations générales</h3>
        
        <div class="form-group">
            <label for="va_name">Nom de votre Virtual Airline *</label>
            <input type="text" id="va_name" name="va_name" value="<?= htmlspecialchars($va_name) ?>" required>
            <small>Exemple : Air France Virtual, Lufthansa VA, etc.</small>
        </div>

        <div class="form-group">
            <label for="va_email">Email de contact *</label>
            <input type="email" id="va_email" name="va_email" value="<?= htmlspecialchars($va_email) ?>" required>
            <small>Email utilisé pour les notifications système</small>
        </div>

        <div class="form-group">
            <label for="va_url">URL de votre site *</label>
            <input type="url" id="va_url" name="va_url" value="<?= htmlspecialchars($va_url) ?>" required>
            <small>URL complète avec http:// ou https:// (sans slash à la fin)</small>
        </div>

        <div class="form-group">
            <label for="va_timezone">Fuseau horaire</label>
            <select id="va_timezone" name="va_timezone">
                <option value="Europe/Paris" <?= $va_timezone === 'Europe/Paris' ? 'selected' : '' ?>>Europe/Paris (UTC+1)</option>
                <option value="Europe/London" <?= $va_timezone === 'Europe/London' ? 'selected' : '' ?>>Europe/London (UTC+0)</option>
                <option value="America/New_York" <?= $va_timezone === 'America/New_York' ? 'selected' : '' ?>>America/New_York (UTC-5)</option>
                <option value="America/Los_Angeles" <?= $va_timezone === 'America/Los_Angeles' ? 'selected' : '' ?>>America/Los_Angeles (UTC-8)</option>
                <option value="Asia/Tokyo" <?= $va_timezone === 'Asia/Tokyo' ? 'selected' : '' ?>>Asia/Tokyo (UTC+9)</option>
                <option value="Australia/Sydney" <?= $va_timezone === 'Australia/Sydney' ? 'selected' : '' ?>>Australia/Sydney (UTC+11)</option>
            </select>
        </div>

        <hr>

        <h3>Configuration SMTP (optionnel)</h3>
        <p class="text-muted">Pour l'envoi d'emails (récupération de mot de passe, notifications). Vous pourrez configurer cela plus tard dans config.php.</p>

        <div class="form-group">
            <label>
                <input type="checkbox" id="smtp_enabled" name="smtp_enabled" <?= $smtp_enabled ? 'checked' : '' ?>>
                Activer l'envoi d'emails par SMTP
            </label>
        </div>

        <div id="smtp-config" style="<?= $smtp_enabled ? '' : 'display:none;' ?>">
            <div class="form-group">
                <label for="smtp_host">Serveur SMTP</label>
                <input type="text" id="smtp_host" name="smtp_host" value="<?= htmlspecialchars($smtp_host) ?>">
                <small>Exemple : smtp.gmail.com, smtp.office365.com</small>
            </div>

            <div class="form-group">
                <label for="smtp_port">Port SMTP</label>
                <input type="number" id="smtp_port" name="smtp_port" value="<?= htmlspecialchars($smtp_port) ?>">
                <small>587 (TLS) ou 465 (SSL)</small>
            </div>

            <div class="form-group">
                <label for="smtp_secure">Sécurité</label>
                <select id="smtp_secure" name="smtp_secure">
                    <option value="tls" <?= $smtp_secure === 'tls' ? 'selected' : '' ?>>TLS (recommandé)</option>
                    <option value="ssl" <?= $smtp_secure === 'ssl' ? 'selected' : '' ?>>SSL</option>
                </select>
            </div>

            <div class="form-group">
                <label for="smtp_user">Utilisateur SMTP</label>
                <input type="text" id="smtp_user" name="smtp_user" value="<?= htmlspecialchars($smtp_user) ?>">
            </div>

            <div class="form-group">
                <label for="smtp_pass">Mot de passe SMTP</label>
                <input type="password" id="smtp_pass" name="smtp_pass" value="<?= htmlspecialchars($smtp_pass) ?>">
            </div>
        </div>

        <div class="actions">
            <a href="?step=2" class="btn btn-secondary">← Retour</a>
            <button type="submit" class="btn btn-primary">Continuer →</button>
        </div>
    </form>
</div>

<script>
document.getElementById('smtp_enabled').addEventListener('change', function() {
    document.getElementById('smtp-config').style.display = this.checked ? 'block' : 'none';
});
</script>
