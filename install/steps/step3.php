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
    // ========== INFORMATIONS OBLIGATOIRES ==========
    $va_name = trim($_POST['va_name'] ?? '');
    $va_icao = strtoupper(trim($_POST['va_icao'] ?? ''));
    $va_email = trim($_POST['va_email'] ?? '');
    $va_admin_email = trim($_POST['va_admin_email'] ?? '');
    $va_url = trim($_POST['va_url'] ?? '');
    $va_currency = trim($_POST['va_currency'] ?? 'EUR');
    $va_default_language = trim($_POST['va_default_language'] ?? 'fr');
    $va_timezone = trim($_POST['va_timezone'] ?? 'Europe/Paris');
    
    // ========== INFORMATIONS OPTIONNELLES ==========
    $va_iata = strtoupper(trim($_POST['va_iata'] ?? ''));
    $va_tagline = trim($_POST['va_tagline'] ?? '');
    $va_currency_symbol = trim($_POST['va_currency_symbol'] ?? '€');
    $va_currency_position = trim($_POST['va_currency_position'] ?? 'after');
    $va_starting_balance = (int)($_POST['va_starting_balance'] ?? 10000);
    
    // Réseaux sociaux (optionnels)
    $va_discord_url = trim($_POST['va_discord_url'] ?? '');
    $va_website_url = trim($_POST['va_website_url'] ?? '');
    $va_forum_url = trim($_POST['va_forum_url'] ?? '');
    
    // SMTP optionnel
    $smtp_enabled = isset($_POST['smtp_enabled']);
    $smtp_host = trim($_POST['smtp_host'] ?? '');
    $smtp_port = trim($_POST['smtp_port'] ?? '587');
    $smtp_user = trim($_POST['smtp_user'] ?? '');
    $smtp_pass = $_POST['smtp_pass'] ?? '';
    $smtp_secure = $_POST['smtp_secure'] ?? 'tls';
    $smtp_from_email = trim($_POST['smtp_from_email'] ?? '');
    $smtp_from_name = trim($_POST['smtp_from_name'] ?? '');
    
    // ========== VALIDATION DES CHAMPS OBLIGATOIRES ==========
    if (empty($va_name)) $errors[] = t('install_step3_error_va_name_required');
    
    if (empty($va_icao) || strlen($va_icao) < 3 || strlen($va_icao) > 4) {
        $errors[] = t('install_step3_error_va_icao_invalid');
    }
    
    if (empty($va_email) || !filter_var($va_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = t('install_step3_error_va_email_invalid');
    }
    
    if (empty($va_admin_email) || !filter_var($va_admin_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = t('install_step3_error_va_admin_email_invalid');
    }
    
    if (empty($va_url) || !filter_var($va_url, FILTER_VALIDATE_URL)) {
        $errors[] = t('install_step3_error_va_url_invalid');
    }
    
    // Validation du code IATA (optionnel mais si rempli, doit être valide)
    if (!empty($va_iata) && strlen($va_iata) !== 2) {
        $errors[] = t('install_step3_error_va_iata_invalid');
    }
    
    // Validation SMTP si activé
    if ($smtp_enabled) {
        if (empty($smtp_host)) $errors[] = t('install_step3_error_smtp_host_required');
        if (empty($smtp_user)) $errors[] = t('install_step3_error_smtp_user_required');
        if (empty($smtp_pass)) $errors[] = t('install_step3_error_smtp_pass_required');
    }
    
    if (empty($errors)) {
        // Sauvegarder en session
        $_SESSION['install_data']['config'] = [
            // Obligatoires
            'va_name' => $va_name,
            'va_icao' => $va_icao,
            'va_email' => $va_email,
            'va_admin_email' => $va_admin_email,
            'va_url' => rtrim($va_url, '/'),
            'va_currency' => $va_currency,
            'va_default_language' => $va_default_language,
            'timezone' => $va_timezone,
            
            // Optionnels
            'va_iata' => $va_iata,
            'va_tagline' => $va_tagline,
            'va_currency_symbol' => $va_currency_symbol,
            'va_currency_position' => $va_currency_position,
            'va_starting_balance' => $va_starting_balance,
            'va_discord_url' => $va_discord_url,
            'va_website_url' => $va_website_url,
            'va_forum_url' => $va_forum_url,
            
            // SMTP
            'smtp_enabled' => $smtp_enabled,
            'smtp_host' => $smtp_host,
            'smtp_port' => $smtp_port,
            'smtp_user' => $smtp_user,
            'smtp_pass' => $smtp_pass,
            'smtp_secure' => $smtp_secure,
            'smtp_from_email' => $smtp_from_email ?: $va_email,
            'smtp_from_name' => $smtp_from_name ?: $va_name
        ];
        
        header('Location: ?step=4');
        exit;
    }
} else {
    // Préremplir avec les valeurs de session si disponibles
    $config = $_SESSION['install_data']['config'] ?? [];
    
    // Obligatoires
    $va_name = $config['va_name'] ?? '';
    $va_icao = $config['va_icao'] ?? '';
    $va_email = $config['va_email'] ?? '';
    $va_admin_email = $config['va_admin_email'] ?? '';
    $va_url = $config['va_url'] ?? 'http://';
    $va_currency = $config['va_currency'] ?? 'EUR';
    $va_default_language = $config['va_default_language'] ?? 'fr';
    $va_timezone = $config['timezone'] ?? 'Europe/Paris';
    
    // Optionnels
    $va_iata = $config['va_iata'] ?? '';
    $va_tagline = $config['va_tagline'] ?? '';
    $va_currency_symbol = $config['va_currency_symbol'] ?? '€';
    $va_currency_position = $config['va_currency_position'] ?? 'after';
    $va_starting_balance = $config['va_starting_balance'] ?? 10000;
    $va_discord_url = $config['va_discord_url'] ?? '';
    $va_website_url = $config['va_website_url'] ?? '';
    $va_forum_url = $config['va_forum_url'] ?? '';
    
    // SMTP
    $smtp_enabled = $config['smtp_enabled'] ?? false;
    $smtp_host = $config['smtp_host'] ?? '';
    $smtp_port = $config['smtp_port'] ?? '587';
    $smtp_user = $config['smtp_user'] ?? '';
    $smtp_pass = $config['smtp_pass'] ?? '';
    $smtp_secure = $config['smtp_secure'] ?? 'tls';
    $smtp_from_email = $config['smtp_from_email'] ?? '';
    $smtp_from_name = $config['smtp_from_name'] ?? '';
}

?>

<div class="step-content">
    <h2><?= t('install_step3_title') ?></h2>
    <p><?= t('install_step3_subtitle') ?></p>

    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <h4><?= t('install_step3_error_validation') ?>:</h4>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="install-form">
        
        <!-- ========== INFORMATIONS OBLIGATOIRES ========== -->
        <div class="config-section">
            <h3><?= t('install_step3_section_required') ?></h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="va_name"><?= t('install_step3_label_va_name') ?></label>
                    <input type="text" id="va_name" name="va_name" value="<?= htmlspecialchars($va_name) ?>" required>
                    <small><?= t('install_step3_help_va_name') ?></small>
                </div>

                <div class="form-group">
                    <label for="va_icao"><?= t('install_step3_label_va_icao') ?></label>
                    <input type="text" id="va_icao" name="va_icao" value="<?= htmlspecialchars($va_icao) ?>" required maxlength="4" pattern="[A-Z]{3,4}" style="text-transform: uppercase;">
                    <small><?= t('install_step3_help_va_icao') ?></small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="va_email"><?= t('install_step3_label_va_email') ?></label>
                    <input type="email" id="va_email" name="va_email" value="<?= htmlspecialchars($va_email) ?>" required>
                    <small><?= t('install_step3_help_va_email') ?></small>
                </div>

                <div class="form-group">
                    <label for="va_admin_email"><?= t('install_step3_label_va_admin_email') ?></label>
                    <input type="email" id="va_admin_email" name="va_admin_email" value="<?= htmlspecialchars($va_admin_email) ?>" required>
                    <small><?= t('install_step3_help_va_admin_email') ?></small>
                </div>
            </div>

            <div class="form-group">
                <label for="va_url"><?= t('install_step3_label_va_url') ?></label>
                <input type="url" id="va_url" name="va_url" value="<?= htmlspecialchars($va_url) ?>" required>
                <small><?= t('install_step3_help_va_url') ?></small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="va_currency"><?= t('install_step3_label_va_currency') ?></label>
                    <select id="va_currency" name="va_currency" required>
                        <option value="EUR" <?= $va_currency === 'EUR' ? 'selected' : '' ?>><?= t('install_step3_currency_eur') ?></option>
                        <option value="USD" <?= $va_currency === 'USD' ? 'selected' : '' ?>><?= t('install_step3_currency_usd') ?></option>
                        <option value="GBP" <?= $va_currency === 'GBP' ? 'selected' : '' ?>><?= t('install_step3_currency_gbp') ?></option>
                        <option value="CHF" <?= $va_currency === 'CHF' ? 'selected' : '' ?>><?= t('install_step3_currency_chf') ?></option>
                        <option value="CAD" <?= $va_currency === 'CAD' ? 'selected' : '' ?>><?= t('install_step3_currency_cad') ?></option>
                        <option value="JPY" <?= $va_currency === 'JPY' ? 'selected' : '' ?>><?= t('install_step3_currency_jpy') ?></option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="va_default_language"><?= t('install_step3_label_va_default_language') ?></label>
                    <select id="va_default_language" name="va_default_language" required>
                        <option value="fr" <?= $va_default_language === 'fr' ? 'selected' : '' ?>><?= t('install_step3_language_fr') ?></option>
                        <option value="en" <?= $va_default_language === 'en' ? 'selected' : '' ?>><?= t('install_step3_language_en') ?></option>
                        <option value="es" <?= $va_default_language === 'es' ? 'selected' : '' ?>><?= t('install_step3_language_es') ?></option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="va_timezone"><?= t('install_step3_label_va_timezone') ?></label>
                <select id="va_timezone" name="va_timezone" required>
                    <option value="Europe/Paris" <?= $va_timezone === 'Europe/Paris' ? 'selected' : '' ?>><?= t('install_step3_timezone_paris') ?></option>
                    <option value="Europe/London" <?= $va_timezone === 'Europe/London' ? 'selected' : '' ?>><?= t('install_step3_timezone_london') ?></option>
                    <option value="Europe/Berlin" <?= $va_timezone === 'Europe/Berlin' ? 'selected' : '' ?>><?= t('install_step3_timezone_berlin') ?></option>
                    <option value="Europe/Madrid" <?= $va_timezone === 'Europe/Madrid' ? 'selected' : '' ?>><?= t('install_step3_timezone_madrid') ?></option>
                    <option value="America/New_York" <?= $va_timezone === 'America/New_York' ? 'selected' : '' ?>><?= t('install_step3_timezone_ny') ?></option>
                    <option value="America/Chicago" <?= $va_timezone === 'America/Chicago' ? 'selected' : '' ?>><?= t('install_step3_timezone_chicago') ?></option>
                    <option value="America/Los_Angeles" <?= $va_timezone === 'America/Los_Angeles' ? 'selected' : '' ?>><?= t('install_step3_timezone_la') ?></option>
                    <option value="America/Toronto" <?= $va_timezone === 'America/Toronto' ? 'selected' : '' ?>><?= t('install_step3_timezone_toronto') ?></option>
                    <option value="Asia/Tokyo" <?= $va_timezone === 'Asia/Tokyo' ? 'selected' : '' ?>><?= t('install_step3_timezone_tokyo') ?></option>
                    <option value="Asia/Shanghai" <?= $va_timezone === 'Asia/Shanghai' ? 'selected' : '' ?>><?= t('install_step3_timezone_shanghai') ?></option>
                    <option value="Australia/Sydney" <?= $va_timezone === 'Australia/Sydney' ? 'selected' : '' ?>><?= t('install_step3_timezone_sydney') ?></option>
                </select>
            </div>
        </div>

        <!-- ========== IDENTITÉ & BRANDING (OPTIONNEL) ========== -->
        <div class="config-section collapsible">
            <h3 onclick="toggleSection('identity')"><?= t('install_step3_section_identity') ?> <span class="toggle-icon">▼</span></h3>
            <div id="section-identity" class="section-content">
                <div class="form-group">
                    <label for="va_iata"><?= t('install_step3_label_va_iata') ?></label>
                    <input type="text" id="va_iata" name="va_iata" value="<?= htmlspecialchars($va_iata) ?>" maxlength="2" pattern="[A-Z]{2}" style="text-transform: uppercase;">
                    <small><?= t('install_step3_help_va_iata') ?></small>
                </div>

                <div class="form-group">
                    <label for="va_tagline"><?= t('install_step3_label_va_tagline') ?></label>
                    <input type="text" id="va_tagline" name="va_tagline" value="<?= htmlspecialchars($va_tagline) ?>" maxlength="100">
                    <small><?= t('install_step3_help_va_tagline') ?></small>
                </div>
            </div>
        </div>

        <!-- ========== PARAMÈTRES FINANCIERS (OPTIONNEL) ========== -->
        <div class="config-section collapsible">
            <h3 onclick="toggleSection('finances')"><?= t('install_step3_section_finances') ?> <span class="toggle-icon">▼</span></h3>
            <div id="section-finances" class="section-content">
                <div class="form-row">
                    <div class="form-group">
                        <label for="va_currency_symbol"><?= t('install_step3_label_va_currency_symbol') ?></label>
                        <input type="text" id="va_currency_symbol" name="va_currency_symbol" value="<?= htmlspecialchars($va_currency_symbol) ?>" maxlength="5">
                        <small><?= t('install_step3_help_va_currency_symbol') ?></small>
                    </div>

                    <div class="form-group">
                        <label for="va_currency_position"><?= t('install_step3_label_va_currency_position') ?></label>
                        <select id="va_currency_position" name="va_currency_position">
                            <option value="before" <?= $va_currency_position === 'before' ? 'selected' : '' ?>><?= t('install_step3_currency_position_before') ?></option>
                            <option value="after" <?= $va_currency_position === 'after' ? 'selected' : '' ?>><?= t('install_step3_currency_position_after') ?></option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="va_starting_balance"><?= t('install_step3_label_va_starting_balance') ?></label>
                    <input type="number" id="va_starting_balance" name="va_starting_balance" value="<?= htmlspecialchars($va_starting_balance) ?>" min="0" step="1000">
                    <small><?= t('install_step3_help_va_starting_balance') ?></small>
                </div>
            </div>
        </div>

        <!-- ========== RÉSEAUX SOCIAUX (OPTIONNEL) ========== -->
        <div class="config-section collapsible">
            <h3 onclick="toggleSection('social')"><?= t('install_step3_section_social') ?> <span class="toggle-icon">▼</span></h3>
            <div id="section-social" class="section-content">
                <div class="form-group">
                    <label for="va_discord_url"><?= t('install_step3_label_va_discord_url') ?></label>
                    <input type="url" id="va_discord_url" name="va_discord_url" value="<?= htmlspecialchars($va_discord_url) ?>">
                    <small><?= t('install_step3_help_va_discord_url') ?></small>
                </div>

                <div class="form-group">
                    <label for="va_website_url"><?= t('install_step3_label_va_website_url') ?></label>
                    <input type="url" id="va_website_url" name="va_website_url" value="<?= htmlspecialchars($va_website_url) ?>">
                    <small><?= t('install_step3_help_va_website_url') ?></small>
                </div>

                <div class="form-group">
                    <label for="va_forum_url"><?= t('install_step3_label_va_forum_url') ?></label>
                    <input type="url" id="va_forum_url" name="va_forum_url" value="<?= htmlspecialchars($va_forum_url) ?>">
                    <small><?= t('install_step3_help_va_forum_url') ?></small>
                </div>
            </div>
        </div>

        <!-- ========== SMTP (OPTIONNEL) ========== -->
        <div class="config-section collapsible">
            <h3 onclick="toggleSection('smtp')"><?= t('install_step3_section_smtp') ?> <span class="toggle-icon">▼</span></h3>
            <div id="section-smtp" class="section-content">
                <p class="text-muted"><?= t('install_step3_smtp_info') ?></p>

                <div class="form-group">
                    <label>
                        <input type="checkbox" id="smtp_enabled" name="smtp_enabled" <?= $smtp_enabled ? 'checked' : '' ?>>
                        <?= t('install_step3_label_smtp_enabled') ?>
                    </label>
                </div>

                <div id="smtp-config" style="<?= $smtp_enabled ? '' : 'display:none;' ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="smtp_host"><?= t('install_step3_label_smtp_host') ?></label>
                            <input type="text" id="smtp_host" name="smtp_host" value="<?= htmlspecialchars($smtp_host) ?>">
                            <small><?= t('install_step3_help_smtp_host') ?></small>
                        </div>

                        <div class="form-group">
                            <label for="smtp_port"><?= t('install_step3_label_smtp_port') ?></label>
                            <input type="number" id="smtp_port" name="smtp_port" value="<?= htmlspecialchars($smtp_port) ?>">
                            <small><?= t('install_step3_help_smtp_port') ?></small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="smtp_secure"><?= t('install_step3_label_smtp_secure') ?></label>
                        <select id="smtp_secure" name="smtp_secure">
                            <option value="tls" <?= $smtp_secure === 'tls' ? 'selected' : '' ?>><?= t('install_step3_smtp_secure_tls') ?></option>
                            <option value="ssl" <?= $smtp_secure === 'ssl' ? 'selected' : '' ?>><?= t('install_step3_smtp_secure_ssl') ?></option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="smtp_user"><?= t('install_step3_label_smtp_user') ?></label>
                            <input type="text" id="smtp_user" name="smtp_user" value="<?= htmlspecialchars($smtp_user) ?>">
                        </div>

                        <div class="form-group">
                            <label for="smtp_pass"><?= t('install_step3_label_smtp_pass') ?></label>
                            <input type="password" id="smtp_pass" name="smtp_pass" value="<?= htmlspecialchars($smtp_pass) ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="smtp_from_email"><?= t('install_step3_label_smtp_from_email') ?></label>
                            <input type="email" id="smtp_from_email" name="smtp_from_email" value="<?= htmlspecialchars($smtp_from_email) ?>">
                            <small><?= t('install_step3_help_smtp_from_email') ?></small>
                        </div>

                        <div class="form-group">
                            <label for="smtp_from_name"><?= t('install_step3_label_smtp_from_name') ?></label>
                            <input type="text" id="smtp_from_name" name="smtp_from_name" value="<?= htmlspecialchars($smtp_from_name) ?>">
                            <small><?= t('install_step3_help_smtp_from_name') ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="actions">
            <a href="?step=2" class="btn btn-secondary"><?= t('install_step3_btn_back') ?></a>
            <button type="submit" class="btn btn-primary"><?= t('install_step3_btn_continue') ?></button>
        </div>
    </form>
</div>

<style>
.config-section {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.config-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #495057;
    font-size: 1.2em;
}

.config-section.collapsible h3 {
    cursor: pointer;
    user-select: none;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.config-section.collapsible h3:hover {
    color: #007bff;
}

.toggle-icon {
    transition: transform 0.3s ease;
    font-size: 0.8em;
}

.toggle-icon.rotated {
    transform: rotate(-90deg);
}

.section-content {
    margin-top: 15px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}

.text-muted {
    color: #6c757d;
    font-size: 0.9em;
    margin-bottom: 15px;
}
</style>

<script>
// Gestion SMTP toggle
document.getElementById('smtp_enabled').addEventListener('change', function() {
    document.getElementById('smtp-config').style.display = this.checked ? 'block' : 'none';
});

// Gestion des sections pliables
function toggleSection(sectionId) {
    const content = document.getElementById('section-' + sectionId);
    const icon = content.previousElementSibling.querySelector('.toggle-icon');
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.classList.remove('rotated');
    } else {
        content.style.display = 'none';
        icon.classList.add('rotated');
    }
}

// Auto-remplissage du symbole de devise
document.getElementById('va_currency').addEventListener('change', function() {
    const symbols = {
        'EUR': '€',
        'USD': '$',
        'GBP': '£',
        'CHF': 'CHF',
        'CAD': '$',
        'JPY': '¥'
    };
    document.getElementById('va_currency_symbol').value = symbols[this.value] || '';
});
</script>
