<?php
/**
 * ✅ Script de vérification pré-installation - v2.0
 * 
 * Ce script vérifie que votre environnement est compatible avec votre VA.
 * Placez-le à la racine de votre installation et accédez-y via navigateur.
 * 
 * Exemple : http://votre-domaine.com/check_installation.php
 * 
 * ⚠️ SUPPRIMEZ CE FICHIER APRÈS L'INSTALLATION !
 */

// Configuration
$required_php_version = '7.4.0';
$recommended_php_version = '8.1.0';

// Tableau des résultats
$checks = [];
$warnings = [];
$errors = [];
$success = [];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification Pré-Installation - SkyWings</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: #004080;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 2em; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .content { padding: 30px; }
        .section {
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        .section-header {
            background: #f5f5f5;
            padding: 15px 20px;
            font-weight: bold;
            font-size: 1.1em;
            border-bottom: 1px solid #e0e0e0;
        }
        .section-content { padding: 20px; }
        .check-item {
            display: flex;
            align-items: center;
            padding: 12px;
            margin-bottom: 8px;
            border-radius: 6px;
            background: #f9f9f9;
        }
        .check-item:last-child { margin-bottom: 0; }
        .icon {
            width: 24px;
            height: 24px;
            margin-right: 15px;
            flex-shrink: 0;
            font-size: 20px;
        }
        .success { background: #e8f5e9; border-left: 4px solid #4caf50; }
        .success .icon { color: #4caf50; }
        .warning { background: #fff3e0; border-left: 4px solid #ff9800; }
        .warning .icon { color: #ff9800; }
        .error { background: #ffebee; border-left: 4px solid #f44336; }
        .error .icon { color: #f44336; }
        .check-label { flex: 1; font-weight: 500; }
        .check-value {
            color: #666;
            font-size: 0.9em;
            margin-left: 10px;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .summary-card {
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .summary-card h3 {
            font-size: 2em;
            margin-bottom: 5px;
        }
        .summary-card p {
            opacity: 0.8;
            font-size: 0.9em;
        }
        .summary-success { background: #e8f5e9; color: #2e7d32; }
        .summary-warning { background: #fff3e0; color: #e65100; }
        .summary-error { background: #ffebee; color: #c62828; }
        .next-steps {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #2196f3;
        }
        .next-steps h3 {
            color: #1565c0;
            margin-bottom: 15px;
        }
        .next-steps ol {
            margin-left: 20px;
        }
        .next-steps li {
            margin-bottom: 10px;
            line-height: 1.6;
        }
        .footer {
            text-align: center;
            padding: 20px;
            background: #f5f5f5;
            color: #666;
            font-size: 0.9em;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
            color: #d32f2f;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✈️ SkyWings - Vérification Pré-Installation</h1>
            <p>Vérification de la compatibilité de votre environnement</p>
        </div>

        <div class="content">
            <?php
            // ==================== VERSION PHP ====================
            $current_php = phpversion();
            $php_ok = version_compare($current_php, $required_php_version, '>=');
            $php_recommended = version_compare($current_php, $recommended_php_version, '>=');
            
            if ($php_ok && $php_recommended) {
                $success[] = ['PHP Version', $current_php . ' (Excellent ✅)'];
            } elseif ($php_ok) {
                $warnings[] = ['PHP Version', $current_php . ' (Fonctionne, mais ' . $recommended_php_version . '+ recommandé)'];
            } else {
                $errors[] = ['PHP Version', $current_php . ' (Minimum requis: ' . $required_php_version . ')'];
            }

            // ==================== EXTENSIONS PHP ====================
            $required_extensions = [
                'pdo_mysql' => 'PDO MySQL',
                'mbstring' => 'Multi-Byte String',
                'json' => 'JSON',
                'curl' => 'cURL',
                'openssl' => 'OpenSSL',
                'session' => 'Session'
            ];

            foreach ($required_extensions as $ext => $name) {
                if (extension_loaded($ext)) {
                    $success[] = ['Extension ' . $name, 'Installée ✅'];
                } else {
                    $errors[] = ['Extension ' . $name, 'MANQUANTE ❌'];
                }
            }

            // ==================== FICHIERS & DOSSIERS ====================
            $required_files = [
                'includes/db_connect_exemple.php' => 'Fichier exemple de configuration DB',
                'includes/config_exemple.php' => 'Fichier exemple de configuration générale',
                'sql_database/01_Main_Database.sql' => 'Script principal de création de la base',
                'sql_database/02_Airports_data.sql' => 'Script des données aéroports',
                'lang/fr.php' => 'Fichier de traduction français',
                'lang/en.php' => 'Fichier de traduction anglais',
                'lang/es.php' => 'Fichier de traduction espagnol',
            ];

            foreach ($required_files as $file => $desc) {
                if (file_exists($file)) {
                    $success[] = [$desc, 'Présent ✅'];
                } else {
                    $errors[] = [$desc, 'MANQUANT ❌'];
                }
            }

            // Vérifier si db_connect.php existe (doit être créé)
            if (!file_exists('includes/db_connect.php')) {
                $warnings[] = ['Configuration DB', 'Vous devez créer includes/db_connect.php'];
            } else {
                $success[] = ['Configuration DB', 'Fichier db_connect.php créé ✅'];
            }

            // Vérifier si config.php existe (doit être créé)
            if (!file_exists('includes/config.php')) {
                $warnings[] = ['Configuration générale', 'Vous devez créer includes/config.php'];
            } else {
                $success[] = ['Configuration générale', 'Fichier config.php créé ✅'];
                
                // Vérifier si le nom de la VA a été personnalisé
                require_once 'includes/config.php';
                if (defined('VA_NAME') && VA_NAME === 'Nom de votre VA') {
                    $warnings[] = ['Nom de la VA', 'Pensez à personnaliser VA_NAME dans config.php'];
                } elseif (defined('VA_NAME')) {
                    $checks[] = ['Nom de la VA', VA_NAME];
                }
            }

            // ==================== PERMISSIONS ====================
            $writable_dirs = [
                'scripts/logs' => 'Logs des scripts automatiques'
            ];

            foreach ($writable_dirs as $dir => $desc) {
                if (is_dir($dir)) {
                    if (is_writable($dir)) {
                        $success[] = [$desc, 'Écriture autorisée ✅'];
                    } else {
                        $errors[] = [$desc, 'Écriture interdite ❌ (chmod 755 requis)'];
                    }
                } else {
                    $warnings[] = [$desc, 'Dossier manquant (sera créé automatiquement)'];
                }
            }

            // ==================== CONFIGURATION PHP ====================
            $memory_limit = ini_get('memory_limit');
            $upload_max = ini_get('upload_max_filesize');
            $post_max = ini_get('post_max_size');
            
            $checks[] = ['Mémoire PHP', $memory_limit];
            $checks[] = ['Upload Max', $upload_max];
            $checks[] = ['POST Max', $post_max];

            // ==================== BASE DE DONNÉES ====================
            if (file_exists('includes/db_connect.php')) {
                try {
                    require_once 'includes/db_connect.php';
                    $success[] = ['Connexion MySQL', 'Connexion réussie ✅'];
                    
                    // Vérifier version MySQL
                    $stmt = $pdo->query('SELECT VERSION()');
                    $mysql_version = $stmt->fetchColumn();
                    $checks[] = ['Version MySQL', $mysql_version];
                    
                } catch (PDOException $e) {
                    $errors[] = ['Connexion MySQL', 'ÉCHEC : ' . $e->getMessage()];
                }
            }

            // ==================== AFFICHAGE DES RÉSULTATS ====================
            $total_checks = count($success) + count($warnings) + count($errors);
            ?>

            <!-- Résumé -->
            <div class="summary">
                <div class="summary-card summary-success">
                    <h3><?= count($success) ?></h3>
                    <p>Vérifications OK</p>
                </div>
                <div class="summary-card summary-warning">
                    <h3><?= count($warnings) ?></h3>
                    <p>Avertissements</p>
                </div>
                <div class="summary-card summary-error">
                    <h3><?= count($errors) ?></h3>
                    <p>Erreurs</p>
                </div>
            </div>

            <!-- Erreurs critiques -->
            <?php if (!empty($errors)): ?>
            <div class="section">
                <div class="section-header">❌ Erreurs Critiques (<?= count($errors) ?>)</div>
                <div class="section-content">
                    <?php foreach ($errors as $error): ?>
                    <div class="check-item error">
                        <div class="icon">❌</div>
                        <div class="check-label"><?= $error[0] ?></div>
                        <div class="check-value"><?= $error[1] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Avertissements -->
            <?php if (!empty($warnings)): ?>
            <div class="section">
                <div class="section-header">⚠️ Avertissements (<?= count($warnings) ?>)</div>
                <div class="section-content">
                    <?php foreach ($warnings as $warning): ?>
                    <div class="check-item warning">
                        <div class="icon">⚠️</div>
                        <div class="check-label"><?= $warning[0] ?></div>
                        <div class="check-value"><?= $warning[1] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Succès -->
            <?php if (!empty($success)): ?>
            <div class="section">
                <div class="section-header">✅ Vérifications Réussies (<?= count($success) ?>)</div>
                <div class="section-content">
                    <?php foreach ($success as $item): ?>
                    <div class="check-item success">
                        <div class="icon">✅</div>
                        <div class="check-label"><?= $item[0] ?></div>
                        <div class="check-value"><?= $item[1] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Informations système -->
            <div class="section">
                <div class="section-header">ℹ️ Informations Système</div>
                <div class="section-content">
                    <?php foreach ($checks as $check): ?>
                    <div class="check-item">
                        <div class="icon">ℹ️</div>
                        <div class="check-label"><?= $check[0] ?></div>
                        <div class="check-value"><?= $check[1] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Prochaines étapes -->
            <div class="next-steps">
                <h3>📋 Prochaines Étapes</h3>
                <?php if (count($errors) > 0): ?>
                <p><strong>🛑 Votre environnement n'est pas encore prêt.</strong></p>
                <ol>
                    <li>Corrigez les erreurs critiques affichées ci-dessus</li>
                    <li>Rechargez cette page pour vérifier à nouveau</li>
                    <li>Consultez <code>INSTALLATION.md</code> pour plus de détails</li>
                </ol>
                <?php elseif (!file_exists('includes/db_connect.php') || !file_exists('includes/config.php')): ?>
                <p><strong>⚙️ Configuration requise :</strong></p>
                <ol>
                    <li>Renommez <code>includes/config_exemple.php</code> en <code>includes/config.php</code></li>
                    <li>Éditez <code>includes/config.php</code> et personnalisez le nom de votre VA, emails, etc.</li>
                    <li>Renommez <code>includes/db_connect_exemple.php</code> en <code>includes/db_connect.php</code></li>
                    <li>Éditez <code>includes/db_connect.php</code> avec vos identifiants MySQL</li>
                    <li>Importez <code>sql_database/01_Main_Database.sql</code> (crée la base + compte admin ADM0001)</li>
                    <li>Importez <code>sql_database/02_Airports_data.sql</code> (données aéroports)</li>
                    <li>Rechargez cette page pour vérifier la connexion</li>
                </ol>
                <?php else: ?>
                <p><strong>🎉 Votre environnement est prêt !</strong></p>
                <ol>
                    <li>Accédez à votre site : <code><?= 'http://' . $_SERVER['HTTP_HOST'] . '/' ?></code></li>
                    <li>Connectez-vous avec le compte par défaut : <code>ADM0001</code> / <code>admin123</code></li>
                    <li>Créez votre propre compte admin et supprimez ADM0001</li>
                    <li><strong>⚠️ SUPPRIMEZ ce fichier check_installation.php</strong></li>
                    <li>Consultez <code>INSTALLATION.md</code> pour la configuration complète</li>
                </ol>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer">
            <p>SkyWings Virtual Airline v2.0 - Novembre 2025</p>
            <p>⚠️ <strong>IMPORTANT :</strong> Supprimez ce fichier après l'installation pour des raisons de sécurité</p>
        </div>
    </div>
</body>
</html>
