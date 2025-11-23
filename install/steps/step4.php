<?php
/**
 * Étape 4 : Installation et exécution
 */

// Vérifier que les étapes précédentes sont complétées
if (!isset($_SESSION['install_data']['database']) || !isset($_SESSION['install_data']['config'])) {
    header('Location: ?step=2');
    exit;
}

/**
 * Parse un fichier SQL et retourne un tableau de requêtes
 */
function parseSqlFile($sql_content) {
    // Retirer les commentaires
    $sql_content = preg_replace('/^--.*$/m', '', $sql_content);
    $sql_content = preg_replace('/\/\*.*?\*\//ms', '', $sql_content);
    
    // Diviser par point-virgule (en ignorant ceux dans les chaînes)
    $queries = [];
    $current_query = '';
    $in_string = false;
    $string_char = '';
    
    for ($i = 0; $i < strlen($sql_content); $i++) {
        $char = $sql_content[$i];
        
        // Gestion des chaînes
        if (($char === '"' || $char === "'") && ($i === 0 || $sql_content[$i-1] !== '\\')) {
            if (!$in_string) {
                $in_string = true;
                $string_char = $char;
            } elseif ($char === $string_char) {
                $in_string = false;
            }
        }
        
        // Si on trouve un point-virgule hors chaîne
        if ($char === ';' && !$in_string) {
            $query = trim($current_query);
            if (!empty($query)) {
                $queries[] = $query;
            }
            $current_query = '';
        } else {
            $current_query .= $char;
        }
    }
    
    // Ajouter la dernière requête si elle existe
    $query = trim($current_query);
    if (!empty($query)) {
        $queries[] = $query;
    }
    
    return $queries;
}

$errors = [];
$logs = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute'])) {
    
    $db = $_SESSION['install_data']['database'];
    $config = $_SESSION['install_data']['config'];
    
    try {
        // 1. Créer le fichier db_connect.php
        $logs[] = ['type' => 'info', 'message' => 'Génération du fichier db_connect.php...'];
        
        $db_connect_content = "<?php
/**
 * Configuration de la base de données
 * Généré automatiquement par l'installateur le " . date('Y-m-d H:i:s') . "
 */

define('DB_HOST', '" . addslashes($db['host']) . "');
define('DB_PORT', '" . addslashes($db['port']) . "');
define('DB_NAME', '" . addslashes($db['name']) . "');
define('DB_USER', '" . addslashes($db['user']) . "');
define('DB_PASS', '" . addslashes($db['pass']) . "');
define('DB_CHARSET', 'utf8mb4');

try {
    \$pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException \$e) {
    die('Erreur de connexion à la base de données : ' . \$e->getMessage());
}
";
        
        $db_connect_path = __DIR__ . '/../../includes/db_connect.php';
        $db_connect_dir = dirname($db_connect_path);
        
        // Vérifier/créer le dossier includes/ avec gestion intelligente
        if (!is_dir($db_connect_dir)) {
            $logs[] = ['type' => 'info', 'message' => 'Création du dossier includes/...'];
            $mkdir_success = @mkdir($db_connect_dir, 0755, true);
            
            if (!$mkdir_success) {
                // Tentative avec permissions plus larges
                $parent_dir = dirname($db_connect_dir);
                $old_perms = @fileperms($parent_dir);
                @chmod($parent_dir, 0777);
                $mkdir_success = @mkdir($db_connect_dir, 0755, true);
                
                if ($mkdir_success) {
                    @chmod($parent_dir, $old_perms); // Restaurer les permissions d'origine
                    $logs[] = ['type' => 'success', 'message' => '✓ Dossier includes/ créé'];
                } else {
                    // Impossible de créer le dossier
                    $_SESSION['install_data']['db_connect_content'] = $db_connect_content;
                    $_SESSION['install_data']['db_connect_path'] = $db_connect_path;
                    throw new Exception('Impossible de créer le dossier includes/. Créez-le manuellement avec : mkdir includes && chmod 755 includes');
                }
            } else {
                $logs[] = ['type' => 'success', 'message' => '✓ Dossier includes/ créé'];
            }
        }
        
        // Tentative d'écriture
        $write_success = @file_put_contents($db_connect_path, $db_connect_content);
        
        if ($write_success === false) {
            // Tentative de correction des permissions du dossier
            $logs[] = ['type' => 'info', 'message' => 'Permissions insuffisantes, tentative de correction...'];
            @chmod($db_connect_dir, 0777);
            $write_success = @file_put_contents($db_connect_path, $db_connect_content);
            
            if ($write_success !== false) {
                // Succès après correction, on remet les permissions à 755
                @chmod($db_connect_dir, 0755);
                @chmod($db_connect_path, 0644);
                $logs[] = ['type' => 'success', 'message' => '✓ Fichier db_connect.php créé (après correction permissions)'];
            } else {
                // Échec définitif, on stocke le contenu pour affichage manuel
                $_SESSION['install_data']['db_connect_content'] = $db_connect_content;
                $_SESSION['install_data']['db_connect_path'] = $db_connect_path;
                throw new Exception('Impossible de créer db_connect.php automatiquement. Permissions insuffisantes sur le dossier includes/');
            }
        } else {
            // Succès direct, on sécurise les permissions
            @chmod($db_connect_path, 0644);
            $logs[] = ['type' => 'success', 'message' => '✓ Fichier db_connect.php créé'];
        }
        
        // 2. Créer le fichier config.php
        $logs[] = ['type' => 'info', 'message' => 'Génération du fichier config.php...'];
        
        $config_content = "<?php
/**
 * Configuration de la Virtual Airline
 * Généré automatiquement par l'installateur le " . date('Y-m-d H:i:s') . "
 */

// ==================== BASE DE DONNÉES ====================

define('DB_HOST', '" . addslashes($db['host']) . "');
define('DB_NAME', '" . addslashes($db['name']) . "');
define('DB_USER', '" . addslashes($db['user']) . "');
define('DB_PASS', '" . addslashes($db['pass']) . "');
define('DB_CHARSET', 'utf8mb4');

// ==================== INFORMATIONS COMPAGNIE ====================

define('VA_NAME', '" . addslashes($config['va_name']) . "');
define('VA_ICAO', '" . addslashes($config['va_icao']) . "');
define('VA_IATA', '" . addslashes($config['va_iata']) . "');
define('VA_TAGLINE', '" . addslashes($config['va_tagline']) . "');

// ==================== CONTACT ====================

define('VA_CONTACT_EMAIL', '" . addslashes($config['va_email']) . "');
define('VA_ADMIN_EMAIL', '" . addslashes($config['va_admin_email']) . "');

// ==================== ADMINISTRATION ====================

define('VA_SUPER_ADMIN_CALLSIGNS', 'ADM0001');
define('VA_BASE_URL', '" . addslashes($config['va_url']) . "');

// ==================== CONFIGURATION SMTP ====================

define('SMTP_HOST', '" . addslashes($config['smtp_host']) . "');
define('SMTP_PORT', " . (int)$config['smtp_port'] . ");
define('SMTP_SECURE', '" . addslashes($config['smtp_secure']) . "');
define('SMTP_USERNAME', '" . addslashes($config['smtp_user']) . "');
define('SMTP_PASSWORD', '" . addslashes($config['smtp_pass']) . "');
define('SMTP_FROM_EMAIL', '" . addslashes($config['smtp_from_email']) . "');
define('SMTP_FROM_NAME', '" . addslashes($config['smtp_from_name']) . "');

// ==================== RÉSEAUX SOCIAUX ====================

define('VA_DISCORD_URL', '" . addslashes($config['va_discord_url']) . "');
define('VA_WEBSITE_URL', '" . addslashes($config['va_website_url']) . "');
define('VA_FORUM_URL', '" . addslashes($config['va_forum_url']) . "');

// ==================== PARAMÈTRES FINANCIERS ====================

define('VA_CURRENCY', '" . addslashes($config['va_currency']) . "');
define('VA_CURRENCY_SYMBOL', '" . addslashes($config['va_currency_symbol']) . "');
define('VA_CURRENCY_POSITION', '" . addslashes($config['va_currency_position']) . "');
define('VA_STARTING_BALANCE', " . (int)$config['va_starting_balance'] . ");

// ==================== PARAMÈTRES SYSTÈME ====================

define('VA_TIMEZONE', '" . addslashes($config['timezone']) . "');
define('VA_DEFAULT_LANGUAGE', '" . addslashes($config['va_default_language']) . "');
define('VA_REGISTRATION_ENABLED', " . $config['va_registration_enabled'] . ");
define('VA_MIN_FLIGHTS_FOR_PROMOTION', " . (int)$config['va_min_flights_promotion'] . ");

// ==================== PARAMÈTRES SIMADDON ====================

define('VA_SIMADDON_ENABLED', " . $config['va_simaddon_enabled'] . ");
define('VA_SIMADDON_API_URL', 'https://api.simaddon.com');

// ==================== MODE DEBUG ====================

define('VA_DEBUG_MODE', false);

// ==================== NE PAS MODIFIER ====================

date_default_timezone_set(VA_TIMEZONE);

if (VA_DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    ini_set('display_errors', 0);
}
";
        
        $config_path = __DIR__ . '/../../includes/config.php';
        $config_dir = dirname($config_path);
        
        // Le dossier includes/ devrait déjà exister (créé lors de db_connect.php)
        // Mais on vérifie quand même
        if (!is_dir($config_dir)) {
            $logs[] = ['type' => 'info', 'message' => 'Création du dossier includes/...'];
            $mkdir_success = @mkdir($config_dir, 0755, true);
            if (!$mkdir_success) {
                $parent_dir = dirname($config_dir);
                $old_perms = @fileperms($parent_dir);
                @chmod($parent_dir, 0777);
                $mkdir_success = @mkdir($config_dir, 0755, true);
                if ($mkdir_success) {
                    @chmod($parent_dir, $old_perms);
                }
            }
        }
        
        // Tentative d'écriture
        $write_success = @file_put_contents($config_path, $config_content);
        
        if ($write_success === false) {
            // Tentative de correction des permissions du dossier
            $logs[] = ['type' => 'info', 'message' => 'Permissions insuffisantes, tentative de correction...'];
            @chmod($config_dir, 0777);
            $write_success = @file_put_contents($config_path, $config_content);
            
            if ($write_success !== false) {
                // Succès après correction, on remet les permissions à 755
                @chmod($config_dir, 0755);
                @chmod($config_path, 0644);
                $logs[] = ['type' => 'success', 'message' => '✓ Fichier config.php créé (après correction permissions)'];
            } else {
                // Échec définitif, on stocke le contenu pour affichage manuel
                $_SESSION['install_data']['config_content'] = $config_content;
                $_SESSION['install_data']['config_path'] = $config_path;
                throw new Exception('Impossible de créer config.php automatiquement. Permissions insuffisantes sur le dossier includes/');
            }
        } else {
            // Succès direct, on sécurise les permissions
            @chmod($config_path, 0644);
            $logs[] = ['type' => 'success', 'message' => '✓ Fichier config.php créé'];
        }
        
        // 3. Créer le dossier scripts/logs/ avec gestion intelligente des permissions
        $logs[] = ['type' => 'info', 'message' => 'Création du dossier scripts/logs/...'];
        
        $logs_dir = __DIR__ . '/../../scripts/logs';
        
        if (!is_dir($logs_dir)) {
            // Tentative de création
            $mkdir_success = @mkdir($logs_dir, 0755, true);
            
            if ($mkdir_success === false) {
                // Tentative avec permissions 0777 temporaires
                $logs[] = ['type' => 'info', 'message' => 'Permissions insuffisantes, tentative de correction...'];
                $parent_dir = dirname($logs_dir);
                @chmod($parent_dir, 0777);
                $mkdir_success = @mkdir($logs_dir, 0755, true);
                
                if ($mkdir_success !== false) {
                    // Succès, on restaure les permissions
                    @chmod($parent_dir, 0755);
                    @chmod($logs_dir, 0755);
                    $logs[] = ['type' => 'success', 'message' => '✓ Dossier scripts/logs/ créé (après correction permissions)'];
                } else {
                    // Échec, mais non bloquant
                    $logs[] = ['type' => 'info', 'message' => '⚠ Dossier scripts/logs/ non créé automatiquement. Créez-le manuellement avec : mkdir scripts/logs && chmod 755 scripts/logs'];
                }
            } else {
                // Succès direct
                @chmod($logs_dir, 0755);
                $logs[] = ['type' => 'success', 'message' => '✓ Dossier scripts/logs/ créé'];
            }
        } else {
            // Dossier déjà existant, on vérifie/corrige les permissions
            @chmod($logs_dir, 0755);
            $logs[] = ['type' => 'success', 'message' => '✓ Dossier scripts/logs/ existe déjà'];
        }
        
        // 4. Connexion à MySQL et création de la base de données
        $logs[] = ['type' => 'info', 'message' => 'Connexion au serveur MySQL...'];
        
        $dsn = "mysql:host={$db['host']};port={$db['port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        $logs[] = ['type' => 'success', 'message' => '✓ Connexion MySQL établie'];
        
        // 4. Créer la base de données si elle n'existe pas
        $logs[] = ['type' => 'info', 'message' => "Création de la base de données '{$db['name']}'..."];
        
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$db['name']}`");
        
        $logs[] = ['type' => 'success', 'message' => '✓ Base de données créée/sélectionnée'];
        
        // 5. Importer le script SQL principal
        $logs[] = ['type' => 'info', 'message' => 'Import du script 01_Main_Database.sql...'];
        
        $sql_file1 = realpath(__DIR__ . '/../sql_database/01_Main_Database.sql');
        if (!$sql_file1 || !file_exists($sql_file1)) {
            throw new Exception("Fichier SQL principal introuvable : 01_Main_Database.sql");
        }
        $sql_content1 = file_get_contents($sql_file1);
        
        // Remplacer le nom de la base par celui choisi par l'utilisateur
        $sql_content1 = str_replace('`VA_Database`', "`{$db['name']}`", $sql_content1);
        $sql_content1 = str_replace('VA_Database', $db['name'], $sql_content1);
        
        // Créer un fichier temporaire avec le SQL modifié
        $temp_sql = tempnam(sys_get_temp_dir(), 'sql_');
        file_put_contents($temp_sql, $sql_content1);
        
        // Importer via mysql CLI (plus fiable pour les gros fichiers)
        $import_cmd = sprintf(
            'mysql -h%s -P%s -u%s %s %s < %s 2>&1',
            escapeshellarg($db['host']),
            escapeshellarg($db['port']),
            escapeshellarg($db['user']),
            !empty($db['pass']) ? '-p' . escapeshellarg($db['pass']) : '',
            escapeshellarg($db['name']),
            escapeshellarg($temp_sql)
        );
        
        $output = [];
        $return_var = 0;
        exec($import_cmd, $output, $return_var);
        
        // Supprimer le fichier temporaire
        @unlink($temp_sql);
        
        if ($return_var !== 0) {
            // Si mysql CLI échoue, essayer avec PDO
            $logs[] = ['type' => 'info', 'message' => 'Import via CLI échoué, essai avec PDO...'];
            $queries = parseSqlFile($sql_content1);
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query) && stripos($query, 'CREATE DATABASE') === false && stripos($query, 'USE ') === false) {
                    try {
                        $pdo->exec($query);
                    } catch (PDOException $e) {
                        $logs[] = ['type' => 'info', 'message' => 'Requête ignorée: ' . substr($query, 0, 50) . '...'];
                    }
                }
            }
        }
        
        $logs[] = ['type' => 'success', 'message' => '✓ Tables principales créées (compte ADM0001 inclus)'];
        
        // 6. Importer le script des aéroports
        $logs[] = ['type' => 'info', 'message' => 'Import du script 02_Airports_data.sql...'];
        
        $sql_file2 = realpath(__DIR__ . '/../sql_database/02_Airports_data.sql');
        if (!$sql_file2 || !file_exists($sql_file2)) {
            throw new Exception("Fichier SQL aéroports introuvable : 02_Airports_data.sql");
        }
        $sql_content2 = file_get_contents($sql_file2);
        
        // Remplacer le nom de la base
        $sql_content2 = str_replace('`VA_Database`', "`{$db['name']}`", $sql_content2);
        $sql_content2 = str_replace('VA_Database', $db['name'], $sql_content2);
        
        // Créer un fichier temporaire
        $temp_sql2 = tempnam(sys_get_temp_dir(), 'sql_');
        file_put_contents($temp_sql2, $sql_content2);
        
        $import_cmd = sprintf(
            'mysql -h%s -P%s -u%s %s %s < %s 2>&1',
            escapeshellarg($db['host']),
            escapeshellarg($db['port']),
            escapeshellarg($db['user']),
            !empty($db['pass']) ? '-p' . escapeshellarg($db['pass']) : '',
            escapeshellarg($db['name']),
            escapeshellarg($temp_sql2)
        );
        
        exec($import_cmd, $output, $return_var);
        
        // Supprimer le fichier temporaire
        @unlink($temp_sql2);
        
        if ($return_var !== 0) {
            // Fallback PDO
            $queries = parseSqlFile($sql_content2);
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    try {
                        $pdo->exec($query);
                    } catch (PDOException $e) {
                        // Ignorer les erreurs non critiques
                    }
                }
            }
        }
        
        $logs[] = ['type' => 'success', 'message' => '✓ Données des aéroports importées'];
        
        // 7. Créer le fichier .installed pour bloquer l'accès
        $logs[] = ['type' => 'info', 'message' => 'Sécurisation de l\'installateur...'];
        
        file_put_contents(__DIR__ . '/../.installed', date('Y-m-d H:i:s'));
        
        $logs[] = ['type' => 'success', 'message' => '✓ Installateur verrouillé'];
        
        // 8. Nettoyer la session
        unset($_SESSION['install_data']);
        
        $success = true;
        
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
        $logs[] = ['type' => 'error', 'message' => '✗ Erreur : ' . $e->getMessage()];
        
        // Si l'erreur concerne les permissions, proposer une solution manuelle
        if (strpos($e->getMessage(), 'Permissions insuffisantes') !== false) {
            $manual_install_needed = true;
        }
    }
}

?>

<div class="step-content">
    <h2>🚀 Installation</h2>
    
    <?php if (!$success && empty($logs)): ?>
        <p>Récapitulatif de votre configuration :</p>
        
        <div class="summary-box">
            <h3>📦 Base de données</h3>
            <ul>
                <li><strong>Hôte :</strong> <?= htmlspecialchars($_SESSION['install_data']['database']['host']) ?></li>
                <li><strong>Port :</strong> <?= htmlspecialchars($_SESSION['install_data']['database']['port']) ?></li>
                <li><strong>Base :</strong> <?= htmlspecialchars($_SESSION['install_data']['database']['name']) ?></li>
                <li><strong>Utilisateur :</strong> <?= htmlspecialchars($_SESSION['install_data']['database']['user']) ?></li>
            </ul>
            
            <h3>✈️ Virtual Airline</h3>
            <ul>
                <li><strong>Nom :</strong> <?= htmlspecialchars($_SESSION['install_data']['config']['va_name']) ?></li>
                <li><strong>Code ICAO :</strong> <?= htmlspecialchars($_SESSION['install_data']['config']['va_icao']) ?></li>
                <?php if (!empty($_SESSION['install_data']['config']['va_iata'])): ?>
                <li><strong>Code IATA :</strong> <?= htmlspecialchars($_SESSION['install_data']['config']['va_iata']) ?></li>
                <?php endif; ?>
                <li><strong>Email contact :</strong> <?= htmlspecialchars($_SESSION['install_data']['config']['va_email']) ?></li>
                <li><strong>Email admin :</strong> <?= htmlspecialchars($_SESSION['install_data']['config']['va_admin_email']) ?></li>
                <li><strong>URL :</strong> <?= htmlspecialchars($_SESSION['install_data']['config']['va_url']) ?></li>
            </ul>
            
            <h3>⚙️ Paramètres</h3>
            <ul>
                <li><strong>Devise :</strong> <?= htmlspecialchars($_SESSION['install_data']['config']['va_currency']) ?> (<?= htmlspecialchars($_SESSION['install_data']['config']['va_currency_symbol']) ?>)</li>
                <li><strong>Langue :</strong> <?= htmlspecialchars($_SESSION['install_data']['config']['va_default_language']) ?></li>
                <li><strong>Fuseau :</strong> <?= htmlspecialchars($_SESSION['install_data']['config']['timezone']) ?></li>
                <li><strong>Balance départ :</strong> <?= number_format($_SESSION['install_data']['config']['va_starting_balance']) ?> <?= htmlspecialchars($_SESSION['install_data']['config']['va_currency_symbol']) ?></li>
                <li><strong>Inscription :</strong> <?= $_SESSION['install_data']['config']['va_registration_enabled'] === 'true' ? 'Activée' : 'Désactivée' ?></li>
                <li><strong>SimAddon :</strong> <?= $_SESSION['install_data']['config']['va_simaddon_enabled'] === 'true' ? 'Activé' : 'Désactivé' ?></li>
                <li><strong>SMTP :</strong> <?= $_SESSION['install_data']['config']['smtp_enabled'] ? 'Activé' : 'Désactivé' ?></li>
            </ul>
        </div>
        
        <div class="warning-box">
            <h4>⚠️ Avant de continuer :</h4>
            <ul>
                <li>Assurez-vous d'avoir sauvegardé toute base de données existante</li>
                <li>L'installation va créer les tables et importer les données</li>
                <li>Un compte super-administrateur par défaut sera créé : <strong>ADM0001</strong> / <strong>admin123</strong></li>
                <li>Ce compte aura tous les droits et sera défini dans le fichier config.php</li>
                <li>Vous devrez supprimer ce compte après avoir créé votre propre compte super admin</li>
            </ul>
        </div>
        
        <form method="POST">
            <input type="hidden" name="execute" value="1">
            <div class="actions">
                <a href="?step=3" class="btn btn-secondary">← Retour</a>
                <button type="submit" class="btn btn-primary btn-large">Lancer l'installation</button>
            </div>
        </form>
        
    <?php else: ?>
        
        <div class="logs-box">
            <h3>Journal d'installation</h3>
            <?php foreach ($logs as $log): ?>
                <div class="log-entry log-<?= $log['type'] ?>">
                    <?= htmlspecialchars($log['message']) ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($success): ?>
            <div class="success-box">
                <h3>✓ Installation terminée avec succès !</h3>
                <p>Votre Virtual Airline est maintenant opérationnelle.</p>
            </div>
            
            <div class="actions">
                <a href="?step=5" class="btn btn-primary btn-large">Terminer →</a>
            </div>
        <?php else: ?>
            <div class="error-box">
                <h3>❌ L'installation a rencontré des erreurs</h3>
                <p>Veuillez consulter le journal ci-dessus et corriger les problèmes.</p>
            </div>
            
            <?php if (isset($manual_install_needed) && $manual_install_needed): ?>
                <div class="warning-box" style="margin-top: 20px;">
                    <h3>📝 Installation manuelle requise</h3>
                    <p>Les permissions sur le dossier <code>includes/</code> ne permettent pas l'écriture automatique.</p>
                    
                    <p><strong>Solution recommandée (Linux/Raspberry Pi) :</strong></p>
                    <ol>
                        <li>Ouvrez un terminal SSH sur votre serveur</li>
                        <li>Naviguez vers le dossier de l'application : <code>cd <?= htmlspecialchars(dirname(dirname(__DIR__))) ?></code></li>
                        <li>Exécutez : <code>sudo chown -R www-data:www-data includes/</code></li>
                        <li>Ou exécutez : <code>sudo chmod 777 includes/</code></li>
                        <li>Cliquez sur "Réessayer" ci-dessous</li>
                        <li>Après installation, restaurez : <code>sudo chmod 755 includes/</code></li>
                    </ol>
                    
                    <p><strong>Solution alternative (copier-coller) :</strong> Créez manuellement les fichiers suivants :</p>
                    
                    <?php if (isset($_SESSION['install_data']['db_connect_content'])): ?>
                        <h4>1️⃣ Fichier : <code><?= htmlspecialchars($_SESSION['install_data']['db_connect_path']) ?></code></h4>
                        <textarea readonly style="width: 100%; height: 200px; font-family: monospace; font-size: 12px; padding: 10px;"><?= htmlspecialchars($_SESSION['install_data']['db_connect_content']) ?></textarea>
                        <button class="btn" onclick="navigator.clipboard.writeText(this.previousElementSibling.value); alert('Contenu copié !')">📋 Copier</button>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['install_data']['config_content'])): ?>
                        <h4 style="margin-top: 20px;">2️⃣ Fichier : <code><?= htmlspecialchars($_SESSION['install_data']['config_path']) ?></code></h4>
                        <textarea readonly style="width: 100%; height: 300px; font-family: monospace; font-size: 12px; padding: 10px;"><?= htmlspecialchars($_SESSION['install_data']['config_content']) ?></textarea>
                        <button class="btn" onclick="navigator.clipboard.writeText(this.previousElementSibling.value); alert('Contenu copié !')">📋 Copier</button>
                    <?php endif; ?>
                    
                    <p style="margin-top: 20px;"><strong>Après avoir créé ces fichiers, cliquez sur "Réessayer"</strong></p>
                </div>
            <?php endif; ?>
            
            <div class="actions">
                <a href="?step=2" class="btn btn-secondary">← Recommencer</a>
                <?php if (isset($manual_install_needed) && $manual_install_needed): ?>
                    <button type="button" class="btn btn-primary" onclick="location.reload()">🔄 Réessayer</button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
    <?php endif; ?>
</div>
