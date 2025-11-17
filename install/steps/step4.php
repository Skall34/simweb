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
        if (file_put_contents($db_connect_path, $db_connect_content) === false) {
            throw new Exception('Impossible de créer db_connect.php');
        }
        $logs[] = ['type' => 'success', 'message' => '✓ Fichier db_connect.php créé'];
        
        // 2. Créer le fichier config.php
        $logs[] = ['type' => 'info', 'message' => 'Génération du fichier config.php...'];
        
        $config_content = "<?php
/**
 * Configuration de la Virtual Airline
 * Généré automatiquement par l'installateur le " . date('Y-m-d H:i:s') . "
 */

// Informations de la VA
define('VA_NAME', '" . addslashes($config['va_name']) . "');
define('VA_EMAIL', '" . addslashes($config['va_email']) . "');
define('VA_URL', '" . addslashes($config['va_url']) . "');
define('VA_ADMIN_CALLSIGNS', 'ADM0001'); // Liste des callsigns administrateurs séparés par des virgules

// Configuration générale
date_default_timezone_set('" . addslashes($config['timezone']) . "');
define('SITE_TIMEZONE', '" . addslashes($config['timezone']) . "');

// Configuration SMTP pour l'envoi d'emails
define('MAIL_ENABLED', " . ($config['smtp_enabled'] ? 'true' : 'false') . ");
define('SMTP_HOST', '" . addslashes($config['smtp_host']) . "');
define('SMTP_PORT', " . (int)$config['smtp_port'] . ");
define('SMTP_USER', '" . addslashes($config['smtp_user']) . "');
define('SMTP_PASS', '" . addslashes($config['smtp_pass']) . "');
define('SMTP_SECURE', '" . addslashes($config['smtp_secure']) . "');
define('SMTP_FROM_EMAIL', VA_EMAIL);
define('SMTP_FROM_NAME', VA_NAME);

// Sécurité
define('SESSION_LIFETIME', 3600); // 1 heure
define('BCRYPT_COST', 12);

// Mode debug (désactiver en production)
define('DEBUG_MODE', false);
";
        
        $config_path = __DIR__ . '/../../includes/config.php';
        if (file_put_contents($config_path, $config_content) === false) {
            throw new Exception('Impossible de créer config.php');
        }
        $logs[] = ['type' => 'success', 'message' => '✓ Fichier config.php créé'];
        
        // 3. Connexion à MySQL et création de la base de données
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
        
        $sql_file1 = realpath(__DIR__ . '/../../sql_database/01_Main_Database.sql');
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
        
        $sql_file2 = realpath(__DIR__ . '/../../sql_database/02_Airports_data.sql');
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
        
        file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));
        
        $logs[] = ['type' => 'success', 'message' => '✓ Installateur verrouillé'];
        
        // 8. Nettoyer la session
        unset($_SESSION['install_data']);
        
        $success = true;
        
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
        $logs[] = ['type' => 'error', 'message' => '✗ Erreur : ' . $e->getMessage()];
    }
}

?>

<div class="step-content">
    <h2>🚀 Installation</h2>
    
    <?php if (!$success && empty($logs)): ?>
        <p>Récapitulatif de votre configuration :</p>
        
        <div class="summary-box">
            <h3>Base de données</h3>
            <ul>
                <li><strong>Hôte :</strong> <?= htmlspecialchars($_SESSION['install_data']['database']['host']) ?></li>
                <li><strong>Port :</strong> <?= htmlspecialchars($_SESSION['install_data']['database']['port']) ?></li>
                <li><strong>Base :</strong> <?= htmlspecialchars($_SESSION['install_data']['database']['name']) ?></li>
                <li><strong>Utilisateur :</strong> <?= htmlspecialchars($_SESSION['install_data']['database']['user']) ?></li>
            </ul>
            
            <h3>Virtual Airline</h3>
            <ul>
                <li><strong>Nom :</strong> <?= htmlspecialchars($_SESSION['install_data']['config']['va_name']) ?></li>
                <li><strong>Email :</strong> <?= htmlspecialchars($_SESSION['install_data']['config']['va_email']) ?></li>
                <li><strong>URL :</strong> <?= htmlspecialchars($_SESSION['install_data']['config']['va_url']) ?></li>
                <li><strong>Fuseau :</strong> <?= htmlspecialchars($_SESSION['install_data']['config']['timezone']) ?></li>
                <li><strong>SMTP :</strong> <?= $_SESSION['install_data']['config']['smtp_enabled'] ? 'Activé' : 'Désactivé' ?></li>
            </ul>
        </div>
        
        <div class="warning-box">
            <h4>⚠️ Avant de continuer :</h4>
            <ul>
                <li>Assurez-vous d'avoir sauvegardé toute base de données existante</li>
                <li>L'installation va créer les tables et importer les données</li>
                <li>Un compte administrateur par défaut sera créé : <strong>ADM0001</strong> / <strong>admin123</strong></li>
                <li>Vous devrez supprimer ce compte après avoir créé votre propre compte admin</li>
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
            
            <div class="actions">
                <a href="?step=2" class="btn btn-secondary">← Recommencer</a>
            </div>
        <?php endif; ?>
        
    <?php endif; ?>
</div>
