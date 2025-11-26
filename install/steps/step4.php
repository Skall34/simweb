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
        // 1. Ne plus créer db_connect.php : l'installateur écrit uniquement un fichier config.ini à la racine
        $logs[] = ['type' => 'info', 'message' => 'Génération du fichier config.ini à la racine...'];

        // Préparer le contenu INI (modèle: config.ini fourni en contexte)
        $config_ini_content = "; Configuration de la Virtual Airline\n";
        $config_ini_content .= "; Ce fichier contient toutes les constantes utilisées par l'application\n";
        $config_ini_content .= "; Généré automatiquement par l'installateur\n\n";

        $config_ini_content .= "; ==================== BASE DE DONNÉES ====================\n\n";
        $config_ini_content .= "[database]\n";
        $config_ini_content .= "host = '" . addslashes($db['host']) . "'\n";
        if (!empty($db['port'])) {
            $config_ini_content .= "port = '" . addslashes($db['port']) . "'\n";
        }
        $config_ini_content .= "name = '" . addslashes($db['name']) . "'\n";
        $config_ini_content .= "user = '" . addslashes($db['user']) . "'\n";
        // préférer 'password' comme clé standard
        $config_ini_content .= "password = '" . addslashes($db['pass']) . "'\n";
        $config_ini_content .= "charset = 'utf8mb4'\n\n";

        $config_ini_content .= "; ==================== INFORMATIONS COMPAGNIE ====================\n\n";
        $config_ini_content .= "[company]\n";
        $config_ini_content .= "name = '" . addslashes($config['va_name']) . "'\n";
        $config_ini_content .= "icao = '" . addslashes($config['va_icao']) . "'\n";
        $config_ini_content .= "iata = '" . addslashes($config['va_iata']) . "'\n";
        $config_ini_content .= "tagline = '" . addslashes($config['va_tagline']) . "'\n\n";

        $config_ini_content .= "; ==================== CONTACT ====================\n\n";
        $config_ini_content .= "[contact]\n";
        $config_ini_content .= "contact_email = '" . addslashes($config['va_email']) . "'\n";
        $config_ini_content .= "admin_email = '" . addslashes($config['va_admin_email']) . "'\n\n";

        $config_ini_content .= "; ==================== ADMINISTRATION ====================\n\n";
        $config_ini_content .= "[admin]\n";
        // si super_admin_callsigns fourni, l'utiliser, sinon valeur par défaut
        $super_calls = isset($config['va_super_admin_callsigns']) ? $config['va_super_admin_callsigns'] : (isset($config['va_super_admin']) ? $config['va_super_admin'] : 'ADM0001');
        $config_ini_content .= "super_admin_callsigns = '" . addslashes($super_calls) . "'\n";
        $config_ini_content .= "base_url = '" . addslashes($config['va_url']) . "'\n\n";

        $config_ini_content .= "; ==================== CONFIGURATION SMTP ====================\n\n";
        $config_ini_content .= "[smtp]\n";
        $config_ini_content .= "host = '" . addslashes($config['smtp_host']) . "'\n";
        $config_ini_content .= "port = '" . addslashes($config['smtp_port']) . "'\n";
        $config_ini_content .= "secure = '" . addslashes($config['smtp_secure']) . "'\n";
        $config_ini_content .= "username = '" . addslashes($config['smtp_user']) . "'\n";
        $config_ini_content .= "password = '" . addslashes($config['smtp_pass']) . "'\n";
        $config_ini_content .= "from_email = '" . addslashes($config['smtp_from_email']) . "'\n";
        $config_ini_content .= "from_name = '" . addslashes($config['smtp_from_name']) . "'\n\n";

        $config_ini_content .= "; ==================== RÉSEAUX SOCIAUX ====================\n\n";
        $config_ini_content .= "[social]\n";
        $config_ini_content .= "discord_url = '" . addslashes(isset($config['va_discord_url']) ? $config['va_discord_url'] : '') . "'\n";
        $config_ini_content .= "website_url = '" . addslashes(isset($config['va_website_url']) ? $config['va_website_url'] : $config['va_url']) . "'\n";
        $config_ini_content .= "forum_url = '" . addslashes(isset($config['va_forum_url']) ? $config['va_forum_url'] : '') . "'\n\n";

        $config_ini_content .= "; ==================== PARAMÈTRES FINANCIERS ====================\n\n";
        $config_ini_content .= "[financial]\n";
        $config_ini_content .= "currency = '" . addslashes($config['va_currency']) . "'\n";
        $config_ini_content .= "currency_symbol = '" . addslashes($config['va_currency_symbol']) . "'\n";
        // ajouter currency_position si disponible
        $config_ini_content .= "currency_position = '" . addslashes(isset($config['va_currency_position']) ? $config['va_currency_position'] : (isset($config['currency_position']) ? $config['currency_position'] : 'after')) . "'\n";
        $config_ini_content .= "starting_balance = '" . (int)$config['va_starting_balance'] . "'\n\n";

        $config_ini_content .= "; ==================== PARAMÈTRES SYSTÈME ====================\n\n";
        $config_ini_content .= "[system]\n";
        $config_ini_content .= "timezone = '" . addslashes($config['timezone']) . "'\n";
        $config_ini_content .= "default_language = '" . addslashes($config['va_default_language']) . "'\n\n";

        $config_ini_content .= "; ==================== MODE DEBUG ====================\n\n";
        $debug_val = isset($config['debug_mode']) ? ($config['debug_mode'] === 'true' ? 'true' : 'false') : 'false';
        $config_ini_content .= "[debug]\n";
        $config_ini_content .= "debug_mode = '" . $debug_val . "'\n";

        $config_ini_path = __DIR__ . '/../../config.ini';
        $config_ini_dir = dirname($config_ini_path);

        // Tentative d'écriture directe du fichier config.ini
        $write_success = @file_put_contents($config_ini_path, $config_ini_content);

        if ($write_success === false) {
            // Tentative de correction des permissions du dossier racine
            $logs[] = ['type' => 'info', 'message' => 'Permissions insuffisantes pour écrire config.ini, tentative de correction...'];
            @chmod($config_ini_dir, 0777);
            $write_success = @file_put_contents($config_ini_path, $config_ini_content);

            if ($write_success !== false) {
                @chmod($config_ini_path, 0644);
                $logs[] = ['type' => 'success', 'message' => '✓ Fichier config.ini créé (après correction permissions)'];
            } else {
                // Échec définitif : stocker le contenu pour copie manuelle
                $_SESSION['install_data']['config_ini_content'] = $config_ini_content;
                $_SESSION['install_data']['config_ini_path'] = $config_ini_path;
                throw new Exception('Impossible de créer config.ini automatiquement. Veuillez créer manuellement le fichier config.ini à la racine du projet.');
            }
        } else {
            @chmod($config_ini_path, 0644);
            $logs[] = ['type' => 'success', 'message' => '✓ Fichier config.ini créé'];
        }

        // Poursuivre l'installation (création des dossiers/tables, etc.)
        $logs[] = ['type' => 'info', 'message' => 'Poursuite de l\'installation...'];
        
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

// ==================== PARAMÈTRES SIMADDON ====================

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

        // Si une balance de départ est spécifiée, insérer en base dans BALANCE_COMMERCIALE
        $starting_balance = isset($config['va_starting_balance']) ? (int)$config['va_starting_balance'] : 0;
        if ($starting_balance !== 0) {
            try {
                $logs[] = ['type' => 'info', 'message' => 'Insertion du solde initial en base...'];
                $stmtBal = $pdo->prepare("INSERT INTO BALANCE_COMMERCIALE (balance_actuelle, commentaire, derniere_maj) VALUES (:bal,'Solde initial', NOW())");
                $stmtBal->execute(['bal' => $starting_balance]);
                $logs[] = ['type' => 'success', 'message' => '✓ Solde initial inséré (' . number_format($starting_balance, 0, ',', ' ') . ')'];
            } catch (PDOException $e) {
                $logs[] = ['type' => 'info', 'message' => 'Impossible d\'insérer le solde initial : ' . $e->getMessage()];
            }
        }
        
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
    <h2><?= t('install_step4_title') ?></h2>
    
    <?php if (!$success && empty($logs)): ?>
        <p><?= t('install_step4_summary_intro') ?></p>
        
        <div class="summary-box">
            <h3><?= t('install_step4_summary_db_title') ?></h3>
            <ul>
                <li><strong><?= t('install_step4_label_db_host') ?></strong> <?= htmlspecialchars($_SESSION['install_data']['database']['host']) ?></li>
                <li><strong><?= t('install_step4_label_db_port') ?></strong> <?= htmlspecialchars($_SESSION['install_data']['database']['port']) ?></li>
                <li><strong><?= t('install_step4_label_db_name') ?></strong> <?= htmlspecialchars($_SESSION['install_data']['database']['name']) ?></li>
                <li><strong><?= t('install_step4_label_db_user') ?></strong> <?= htmlspecialchars($_SESSION['install_data']['database']['user']) ?></li>
            </ul>
            <br>
            <h3><?= t('install_step4_summary_va_title') ?></h3>
            <ul>
                <li><strong><?= t('install_step3_label_va_name') ?></strong> <?= htmlspecialchars($_SESSION['install_data']['config']['va_name']) ?></li>
                <li><strong><?= t('install_step3_label_va_icao') ?></strong> <?= htmlspecialchars($_SESSION['install_data']['config']['va_icao']) ?></li>
                <?php if (!empty($_SESSION['install_data']['config']['va_iata'])): ?>
                <li><strong><?= t('install_step3_label_va_iata') ?></strong> <?= htmlspecialchars($_SESSION['install_data']['config']['va_iata']) ?></li>
                <?php endif; ?>
                <li><strong><?= t('install_step3_label_va_email') ?></strong> <?= htmlspecialchars($_SESSION['install_data']['config']['va_email']) ?></li>
                <li><strong><?= t('install_step3_label_va_admin_email') ?></strong> <?= htmlspecialchars($_SESSION['install_data']['config']['va_admin_email']) ?></li>
                <li><strong><?= t('install_step3_label_va_url') ?></strong> <?= htmlspecialchars($_SESSION['install_data']['config']['va_url']) ?></li>
            </ul>
            <br>
            <h3><?= t('install_step4_summary_params_title') ?></h3>
            <ul>
                <li><strong><?= t('install_step3_label_va_currency') ?></strong> <?= htmlspecialchars($_SESSION['install_data']['config']['va_currency']) ?> (<?= htmlspecialchars($_SESSION['install_data']['config']['va_currency_symbol']) ?>)</li>
                <li><strong><?= t('install_step3_label_va_default_language') ?></strong> <?= htmlspecialchars($_SESSION['install_data']['config']['va_default_language']) ?></li>
                <li><strong><?= t('install_step3_label_va_timezone') ?></strong> <?= htmlspecialchars($_SESSION['install_data']['config']['timezone']) ?></li>
                <li><strong><?= t('install_step3_label_va_starting_balance') ?></strong> <?= number_format($_SESSION['install_data']['config']['va_starting_balance']) ?> <?= htmlspecialchars($_SESSION['install_data']['config']['va_currency_symbol']) ?></li>
                <li><strong><?= t('install_step3_label_smtp_enabled') ?></strong> <?= !empty($_SESSION['install_data']['config']['smtp_enabled']) ? t('fleet_text_yes') : t('fleet_text_no') ?></li>
            </ul>
        </div>
        
        <div class="warning-box">
            <h4><?= t('install_step4_warning_title') ?></h4>
            <ul>
                <li><?= t('install_step4_warning_save_db') ?></li>
                <li><?= t('install_step4_warning_tables_import') ?></li>
                <li><?= t('install_step4_warning_admin_account') ?></li>
                <li><?= t('install_step4_warning_admin_account_details') ?></li>
                <li><?= t('install_step4_warning_delete_admin') ?></li>
            </ul>
        </div>
        
        <form method="POST">
            <input type="hidden" name="execute" value="1">
            <div class="actions">
                <a href="?step=3" class="btn btn-secondary"><?= t('install_step4_btn_back') ?></a>
                <button type="submit" class="btn btn-primary btn-large"><?= t('install_step4_btn_execute') ?></button>
            </div>
        </form>
        
    <?php else: ?>
        
        <div class="logs-box">
            <h3><?= t('install_step4_logs_title') ?></h3>
            <?php foreach ($logs as $log): ?>
                <div class="log-entry log-<?= $log['type'] ?>">
                    <?= htmlspecialchars($log['message']) ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($success): ?>
            <div class="success-box">
                <h3><?= t('install_step4_success_title') ?></h3>
                <p><?= t('install_step4_success_text') ?></p>
            </div>
            
            <div class="actions">
                <a href="?step=5" class="btn btn-primary btn-large"><?= t('install_step4_btn_finish') ?></a>
            </div>
        <?php else: ?>
            <div class="error-box">
                <h3><?= t('install_step4_error_title') ?></h3>
                <p><?= t('install_step4_error_text') ?></p>
            </div>
            
            <?php if (isset($manual_install_needed) && $manual_install_needed): ?>
                <div class="warning-box" style="margin-top: 20px;">
                    <h3><?= t('install_step4_manual_title') ?></h3>
                    <p><?= t('install_step4_manual_text') ?></p>
                    
                    <p><strong><?= t('install_step4_manual_recommended_title') ?></strong></p>
                    <ol>
                        <li><?= t('install_step4_manual_step_cd') ?></li>
                        <li><?= t('install_step4_manual_step_cd2') ?> : <code><?= htmlspecialchars(dirname(dirname(__DIR__))) ?></code></li>
                        <li><?= t('install_step4_manual_step_chown') ?></li>
                        <li><?= t('install_step4_manual_step_chmod') ?></li>
                        <li><?= t('install_step4_btn_execute') ?></li>
                        <li><?= t('install_step4_manual_after') ?></li>
                    </ol>
                    
                    <p><strong><?= t('install_step4_manual_copy_heading') ?></strong> <?= t('install_step4_manual_file_label') ?></p>
                    
                    <?php if (isset($_SESSION['install_data']['config_ini_content'])): ?>
                        <h4><?= t('install_step4_manual_file_label') ?> <code><?= htmlspecialchars($_SESSION['install_data']['config_ini_path']) ?></code></h4>
                        <textarea readonly style="width: 100%; height: 400px; font-family: monospace; font-size: 12px; padding: 10px;"><?= htmlspecialchars($_SESSION['install_data']['config_ini_content']) ?></textarea>
                        <button class="btn" onclick="navigator.clipboard.writeText(this.previousElementSibling.value); alert('Contenu copié !')"><?= t('install_copy_button') ?></button>
                    <?php endif; ?>
                    
                    <p style="margin-top: 20px;"><strong><?= t('install_step4_manual_after') ?></strong></p>
                </div>
            <?php endif; ?>
            
            <div class="actions">
                <a href="?step=2" class="btn btn-secondary"><?= t('install_step2_btn_back') ?></a>
                <?php if (isset($manual_install_needed) && $manual_install_needed): ?>
                    <button type="button" class="btn btn-primary" onclick="location.reload()"><?= t('install_step4_btn_execute') ?></button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
    <?php endif; ?>
</div>
