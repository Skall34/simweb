<?php
/**
 * Étape 1 : Vérifications préalables
 */

// Vérification bloquante : db_connect.php ou .installed
$installedFileExists = file_exists(__DIR__ . '/../.installed');
$dbConnectExists = file_exists(__DIR__ . '/../../includes/db_connect.php');
if ($dbConnectExists || $installedFileExists) {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Installation bloquée</title>
        <link rel="stylesheet" href="../style.css">
    </head>
    <body>
        <div class="warning-box">
            <div class="warning-icon">⚠️</div>
            <div class="warning-title">
                <?php if ($installedFileExists): ?>
                    Installation déjà effectuée
                <?php else: ?>
                    Fichier de configuration détecté
                <?php endif; ?>
            </div>
            <div class="warning-content">
                <?php if ($installedFileExists): ?>
                    <p><strong>L'installateur ne peut pas être exécuté car votre site semble déjà installé.</strong></p>
                <?php else: ?>
                    <p><strong>L'installateur ne peut pas être exécuté car le fichier <code>includes/db_connect.php</code> est présent.</strong></p>
                <?php endif; ?>
                <ul class="file-list">
                    <?php if ($installedFileExists): ?>
                        <li class="file-found">✗ <code>install/.installed</code> - Fichier de verrouillage présent</li>
                    <?php endif; ?>
                    <?php if ($dbConnectExists): ?>
                        <li class="file-found">✗ <code>includes/db_connect.php</code> - Configuration DB présente</li>
                    <?php endif; ?>
                </ul>
                <p><strong>Que faire ?</strong></p>
                <ul>
                    <?php if ($dbConnectExists): ?>
                        <li>Supprimez le fichier <code>includes/db_connect.php</code> puis <strong>rechargez cette page</strong></li>
                    <?php endif; ?>
                    <?php if ($installedFileExists): ?>
                        <li>Si votre site fonctionne, <strong>accédez à la page d'accueil</strong></li>
                        <li>Si vous devez <strong>réinstaller</strong>, supprimez les fichiers détectés ci-dessus</li>
                    <?php endif; ?>
                </ul>
                <p style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-left: 4px solid #0066cc;">
                    <strong>💡 Conseil :</strong> Si vous avez copié <code>db_connect.php</code> par erreur lors d'un transfert FTP, supprimez-le simplement via votre client FTP. Ce fichier ne doit jamais être versionné ou transféré manuellement.
                </p>
            </div>
            <div class="actions">
                <?php if ($dbConnectExists): ?>
                    <a href="/install/index.php" class="btn btn-primary">Recharger la page</a>
                <?php endif; ?>
                <?php if ($installedFileExists): ?>
                    <a href="../../index.php" class="btn btn-primary">Accéder au site</a>
                    <a href="../../INSTALLATION.md" class="btn btn-secondary" target="_blank">Voir la documentation</a>
                <?php endif; ?>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

$checks = [];
$all_ok = true;

// Vérification version PHP
$php_version = phpversion();
$php_ok = version_compare($php_version, '7.4.0', '>=');
$checks[] = [
    'name' => 'Version PHP',
    'status' => $php_ok,
    'message' => $php_ok ? "PHP $php_version ✓" : "PHP $php_version (minimum 7.4 requis)",
    'critical' => true
];
if (!$php_ok) $all_ok = false;

// ...existing code...
$sql_files = [
    '../sql_database/01_Main_Database.sql' => 'Script base de données principale',
    '../sql_database/02_Airports_data.sql' => 'Script données aéroports'
];

foreach ($sql_files as $path => $name) {
    $full_path = realpath(__DIR__ . '/' . $path);
    if (!$full_path) {
        $full_path = __DIR__ . '/' . $path;
    }
    $exists = file_exists($full_path);
    $checks[] = [
        'name' => $name,
        'status' => $exists,
        'message' => $exists ? "$name trouvé ✓" : "$name introuvable",
        'critical' => true
    ];
    if (!$exists) $all_ok = false;
}

?>

<div class="step-content">
    <h2>🔍 Vérifications préalables</h2>
    <p>Contrôle de l'environnement d'installation...</p>

    <div class="checks-list">
        <?php foreach ($checks as $check): ?>
            <?php 
                $class = $check['status'] ? 'success' : (isset($check['warning']) && $check['warning'] ? 'warning' : 'error');
            ?>
            <div class="check-item <?= $class ?>">
                <span class="check-icon"><?= $check['status'] ? '✓' : (isset($check['warning']) && $check['warning'] ? '⚠' : '✗') ?></span>
                <div class="check-content">
                    <strong><?= htmlspecialchars($check['name']) ?></strong>
                    <p><?= htmlspecialchars($check['message']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($all_ok): ?>
        <div class="success-box">
            <h3>✓ Toutes les vérifications sont passées avec succès !</h3>
            <p>Votre environnement est prêt pour l'installation.</p>
        </div>
        
        <div class="actions">
            <a href="?step=2" class="btn btn-primary">Continuer →</a>
        </div>
    <?php else: ?>
        <div class="error-box">
            <h3>⚠ Certaines vérifications ont échoué</h3>
            <p>Veuillez corriger les erreurs ci-dessus avant de continuer.</p>
            <p><strong>Actions recommandées :</strong></p>
            <ul>
                <li>Vérifiez votre version de PHP (minimum 7.4, recommandé 8.1+)</li>
                <li>Activez les extensions PHP manquantes dans php.ini</li>
                <li>Donnez les permissions d'écriture : <code>chmod 755 includes/</code></li>
                <li>Vérifiez la présence des fichiers SQL dans sql_database/</li>
            </ul>
        </div>
        
        <div class="actions">
            <a href="?step=1" class="btn btn-secondary">Revérifier</a>
        </div>
    <?php endif; ?>
</div>
