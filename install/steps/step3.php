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
    $va_registration_enabled = isset($_POST['va_registration_enabled']) ? 'true' : 'false';
    $va_min_flights_promotion = (int)($_POST['va_min_flights_promotion'] ?? 10);
    
    // Réseaux sociaux (optionnels)
    $va_discord_url = trim($_POST['va_discord_url'] ?? '');
    $va_website_url = trim($_POST['va_website_url'] ?? '');
    $va_forum_url = trim($_POST['va_forum_url'] ?? '');
    
    // SimAddon
    $va_simaddon_enabled = isset($_POST['va_simaddon_enabled']) ? 'true' : 'false';
    
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
    if (empty($va_name)) $errors[] = 'Le nom de la Virtual Airline est requis';
    
    if (empty($va_icao) || strlen($va_icao) < 3 || strlen($va_icao) > 4) {
        $errors[] = 'Le code ICAO doit contenir 3 ou 4 lettres (ex: AFR, BAW)';
    }
    
    if (empty($va_email) || !filter_var($va_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Une adresse email de contact valide est requise';
    }
    
    if (empty($va_admin_email) || !filter_var($va_admin_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Une adresse email administrateur valide est requise';
    }
    
    if (empty($va_url) || !filter_var($va_url, FILTER_VALIDATE_URL)) {
        $errors[] = 'Une URL valide est requise';
    }
    
    // Validation du code IATA (optionnel mais si rempli, doit être valide)
    if (!empty($va_iata) && strlen($va_iata) !== 2) {
        $errors[] = 'Le code IATA doit contenir exactement 2 lettres (ex: AF, BA) ou être vide';
    }
    
    // Validation SMTP si activé
    if ($smtp_enabled) {
        if (empty($smtp_host)) $errors[] = 'Le serveur SMTP est requis si SMTP est activé';
        if (empty($smtp_user)) $errors[] = 'L\'utilisateur SMTP est requis si SMTP est activé';
        if (empty($smtp_pass)) $errors[] = 'Le mot de passe SMTP est requis si SMTP est activé';
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
            'va_registration_enabled' => $va_registration_enabled,
            'va_min_flights_promotion' => $va_min_flights_promotion,
            'va_discord_url' => $va_discord_url,
            'va_website_url' => $va_website_url,
            'va_forum_url' => $va_forum_url,
            'va_simaddon_enabled' => $va_simaddon_enabled,
            
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
    $va_registration_enabled = ($config['va_registration_enabled'] ?? 'true') === 'true';
    $va_min_flights_promotion = $config['va_min_flights_promotion'] ?? 10;
    $va_discord_url = $config['va_discord_url'] ?? '';
    $va_website_url = $config['va_website_url'] ?? '';
    $va_forum_url = $config['va_forum_url'] ?? '';
    $va_simaddon_enabled = ($config['va_simaddon_enabled'] ?? 'true') === 'true';
    
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
    <h2>⚙️ Configuration de votre Virtual Airline</h2>
    <p>Personnalisez tous les paramètres de votre VA. Les champs marqués d'une <strong>*</strong> sont obligatoires.</p>

    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <h4>❌ Erreurs de validation :</h4>
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
            <h3>📋 Informations Obligatoires</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="va_name">Nom de votre Virtual Airline *</label>
                    <input type="text" id="va_name" name="va_name" value="<?= htmlspecialchars($va_name) ?>" required>
                    <small>Exemple : Air France Virtual, Lufthansa VA</small>
                </div>

                <div class="form-group">
                    <label for="va_icao">Code ICAO *</label>
                    <input type="text" id="va_icao" name="va_icao" value="<?= htmlspecialchars($va_icao) ?>" required maxlength="4" pattern="[A-Z]{3,4}" style="text-transform: uppercase;">
                    <small>3-4 lettres (ex: AFR, BAW, DAL)</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="va_email">Email de contact *</label>
                    <input type="email" id="va_email" name="va_email" value="<?= htmlspecialchars($va_email) ?>" required>
                    <small>Email public pour le support</small>
                </div>

                <div class="form-group">
                    <label for="va_admin_email">Email administrateur *</label>
                    <input type="email" id="va_admin_email" name="va_admin_email" value="<?= htmlspecialchars($va_admin_email) ?>" required>
                    <small>Email pour les notifications système</small>
                </div>
            </div>

            <div class="form-group">
                <label for="va_url">URL de votre site *</label>
                <input type="url" id="va_url" name="va_url" value="<?= htmlspecialchars($va_url) ?>" required>
                <small>URL complète avec http:// ou https:// (sans slash à la fin)</small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="va_currency">Devise *</label>
                    <select id="va_currency" name="va_currency" required>
                        <option value="EUR" <?= $va_currency === 'EUR' ? 'selected' : '' ?>>EUR (€) - Euro</option>
                        <option value="USD" <?= $va_currency === 'USD' ? 'selected' : '' ?>>USD ($) - Dollar américain</option>
                        <option value="GBP" <?= $va_currency === 'GBP' ? 'selected' : '' ?>>GBP (£) - Livre sterling</option>
                        <option value="CHF" <?= $va_currency === 'CHF' ? 'selected' : '' ?>>CHF (CHF) - Franc suisse</option>
                        <option value="CAD" <?= $va_currency === 'CAD' ? 'selected' : '' ?>>CAD ($) - Dollar canadien</option>
                        <option value="JPY" <?= $va_currency === 'JPY' ? 'selected' : '' ?>>JPY (¥) - Yen japonais</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="va_default_language">Langue par défaut *</label>
                    <select id="va_default_language" name="va_default_language" required>
                        <option value="fr" <?= $va_default_language === 'fr' ? 'selected' : '' ?>>🇫🇷 Français</option>
                        <option value="en" <?= $va_default_language === 'en' ? 'selected' : '' ?>>🇬🇧 English</option>
                        <option value="es" <?= $va_default_language === 'es' ? 'selected' : '' ?>>🇪🇸 Español</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="va_timezone">Fuseau horaire *</label>
                <select id="va_timezone" name="va_timezone" required>
                    <option value="Europe/Paris" <?= $va_timezone === 'Europe/Paris' ? 'selected' : '' ?>>Europe/Paris (UTC+1)</option>
                    <option value="Europe/London" <?= $va_timezone === 'Europe/London' ? 'selected' : '' ?>>Europe/London (UTC+0)</option>
                    <option value="Europe/Berlin" <?= $va_timezone === 'Europe/Berlin' ? 'selected' : '' ?>>Europe/Berlin (UTC+1)</option>
                    <option value="Europe/Madrid" <?= $va_timezone === 'Europe/Madrid' ? 'selected' : '' ?>>Europe/Madrid (UTC+1)</option>
                    <option value="America/New_York" <?= $va_timezone === 'America/New_York' ? 'selected' : '' ?>>America/New_York (UTC-5)</option>
                    <option value="America/Chicago" <?= $va_timezone === 'America/Chicago' ? 'selected' : '' ?>>America/Chicago (UTC-6)</option>
                    <option value="America/Los_Angeles" <?= $va_timezone === 'America/Los_Angeles' ? 'selected' : '' ?>>America/Los_Angeles (UTC-8)</option>
                    <option value="America/Toronto" <?= $va_timezone === 'America/Toronto' ? 'selected' : '' ?>>America/Toronto (UTC-5)</option>
                    <option value="Asia/Tokyo" <?= $va_timezone === 'Asia/Tokyo' ? 'selected' : '' ?>>Asia/Tokyo (UTC+9)</option>
                    <option value="Asia/Shanghai" <?= $va_timezone === 'Asia/Shanghai' ? 'selected' : '' ?>>Asia/Shanghai (UTC+8)</option>
                    <option value="Australia/Sydney" <?= $va_timezone === 'Australia/Sydney' ? 'selected' : '' ?>>Australia/Sydney (UTC+11)</option>
                </select>
            </div>
        </div>

        <!-- ========== IDENTITÉ & BRANDING (OPTIONNEL) ========== -->
        <div class="config-section collapsible">
            <h3 onclick="toggleSection('identity')">🎨 Identité & Branding (optionnel) <span class="toggle-icon">▼</span></h3>
            <div id="section-identity" class="section-content">
                <div class="form-group">
                    <label for="va_iata">Code IATA (optionnel)</label>
                    <input type="text" id="va_iata" name="va_iata" value="<?= htmlspecialchars($va_iata) ?>" maxlength="2" pattern="[A-Z]{2}" style="text-transform: uppercase;">
                    <small>2 lettres (ex: AF, BA, LH) - Laisser vide si vous n'en avez pas</small>
                </div>

                <div class="form-group">
                    <label for="va_tagline">Slogan de la compagnie</label>
                    <input type="text" id="va_tagline" name="va_tagline" value="<?= htmlspecialchars($va_tagline) ?>" maxlength="100">
                    <small>Phrase d'accroche affichée sur la page d'accueil</small>
                </div>
            </div>
        </div>

        <!-- ========== PARAMÈTRES FINANCIERS (OPTIONNEL) ========== -->
        <div class="config-section collapsible">
            <h3 onclick="toggleSection('finances')">💰 Paramètres Financiers (optionnel) <span class="toggle-icon">▼</span></h3>
            <div id="section-finances" class="section-content">
                <div class="form-row">
                    <div class="form-group">
                        <label for="va_currency_symbol">Symbole de devise</label>
                        <input type="text" id="va_currency_symbol" name="va_currency_symbol" value="<?= htmlspecialchars($va_currency_symbol) ?>" maxlength="5">
                        <small>Symbole affiché (€, $, £, etc.)</small>
                    </div>

                    <div class="form-group">
                        <label for="va_currency_position">Position du symbole</label>
                        <select id="va_currency_position" name="va_currency_position">
                            <option value="before" <?= $va_currency_position === 'before' ? 'selected' : '' ?>>Avant (€100)</option>
                            <option value="after" <?= $va_currency_position === 'after' ? 'selected' : '' ?>>Après (100€)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="va_starting_balance">Balance de départ des pilotes</label>
                    <input type="number" id="va_starting_balance" name="va_starting_balance" value="<?= htmlspecialchars($va_starting_balance) ?>" min="0" step="1000">
                    <small>Montant accordé aux nouveaux pilotes à leur inscription</small>
                </div>
            </div>
        </div>

        <!-- ========== SYSTÈME & PILOTES (OPTIONNEL) ========== -->
        <div class="config-section collapsible">
            <h3 onclick="toggleSection('system')">⚙️ Système & Pilotes (optionnel) <span class="toggle-icon">▼</span></h3>
            <div id="section-system" class="section-content">
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="va_registration_enabled" name="va_registration_enabled" <?= $va_registration_enabled ? 'checked' : '' ?>>
                        Autoriser l'inscription de nouveaux pilotes
                    </label>
                    <small>Si décoché, seul un admin pourra créer des comptes pilotes</small>
                </div>

                <div class="form-group">
                    <label for="va_min_flights_promotion">Vols minimum pour promotion automatique</label>
                    <input type="number" id="va_min_flights_promotion" name="va_min_flights_promotion" value="<?= htmlspecialchars($va_min_flights_promotion) ?>" min="1" max="100">
                    <small>Nombre de vols requis avant qu'un pilote puisse être promu</small>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" id="va_simaddon_enabled" name="va_simaddon_enabled" <?= $va_simaddon_enabled ? 'checked' : '' ?>>
                        Activer l'intégration SimAddon (ACARS)
                    </label>
                    <small>Permet l'enregistrement automatique des vols depuis MSFS</small>
                </div>
            </div>
        </div>

        <!-- ========== RÉSEAUX SOCIAUX (OPTIONNEL) ========== -->
        <div class="config-section collapsible">
            <h3 onclick="toggleSection('social')">🌐 Réseaux Sociaux (optionnel) <span class="toggle-icon">▼</span></h3>
            <div id="section-social" class="section-content">
                <div class="form-group">
                    <label for="va_discord_url">URL du serveur Discord</label>
                    <input type="url" id="va_discord_url" name="va_discord_url" value="<?= htmlspecialchars($va_discord_url) ?>">
                    <small>Exemple : https://discord.gg/VotreServeur</small>
                </div>

                <div class="form-group">
                    <label for="va_website_url">URL du site web externe</label>
                    <input type="url" id="va_website_url" name="va_website_url" value="<?= htmlspecialchars($va_website_url) ?>">
                    <small>Si vous avez un site vitrine séparé</small>
                </div>

                <div class="form-group">
                    <label for="va_forum_url">URL du forum</label>
                    <input type="url" id="va_forum_url" name="va_forum_url" value="<?= htmlspecialchars($va_forum_url) ?>">
                    <small>Si vous avez un forum externe</small>
                </div>
            </div>
        </div>

        <!-- ========== SMTP (OPTIONNEL) ========== -->
        <div class="config-section collapsible">
            <h3 onclick="toggleSection('smtp')">📧 Configuration SMTP (optionnel) <span class="toggle-icon">▼</span></h3>
            <div id="section-smtp" class="section-content">
                <p class="text-muted">Pour l'envoi d'emails (récupération de mot de passe, notifications). Vous pourrez configurer cela plus tard dans config.php.</p>

                <div class="form-group">
                    <label>
                        <input type="checkbox" id="smtp_enabled" name="smtp_enabled" <?= $smtp_enabled ? 'checked' : '' ?>>
                        Activer l'envoi d'emails par SMTP
                    </label>
                </div>

                <div id="smtp-config" style="<?= $smtp_enabled ? '' : 'display:none;' ?>">
                    <div class="form-row">
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
                    </div>

                    <div class="form-group">
                        <label for="smtp_secure">Sécurité</label>
                        <select id="smtp_secure" name="smtp_secure">
                            <option value="tls" <?= $smtp_secure === 'tls' ? 'selected' : '' ?>>TLS (recommandé)</option>
                            <option value="ssl" <?= $smtp_secure === 'ssl' ? 'selected' : '' ?>>SSL</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="smtp_user">Utilisateur SMTP</label>
                            <input type="text" id="smtp_user" name="smtp_user" value="<?= htmlspecialchars($smtp_user) ?>">
                        </div>

                        <div class="form-group">
                            <label for="smtp_pass">Mot de passe SMTP</label>
                            <input type="password" id="smtp_pass" name="smtp_pass" value="<?= htmlspecialchars($smtp_pass) ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="smtp_from_email">Email expéditeur (From)</label>
                            <input type="email" id="smtp_from_email" name="smtp_from_email" value="<?= htmlspecialchars($smtp_from_email) ?>">
                            <small>Si différent de l'email de contact (ex: noreply@domain.com)</small>
                        </div>

                        <div class="form-group">
                            <label for="smtp_from_name">Nom expéditeur</label>
                            <input type="text" id="smtp_from_name" name="smtp_from_name" value="<?= htmlspecialchars($smtp_from_name) ?>">
                            <small>Nom affiché comme expéditeur des emails</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="actions">
            <a href="?step=2" class="btn btn-secondary">← Retour</a>
            <button type="submit" class="btn btn-primary">Continuer →</button>
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
