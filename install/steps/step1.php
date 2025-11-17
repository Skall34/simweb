<?php
/**
 * Étape 1 : Vérifications préalables
 */

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

// Extensions PHP requises
$required_extensions = [
    'pdo' => 'PDO',
    'pdo_mysql' => 'PDO MySQL',
    'mbstring' => 'Multibyte String',
    'json' => 'JSON',
    'session' => 'Session'
];

foreach ($required_extensions as $ext => $name) {
    $loaded = extension_loaded($ext);
    $checks[] = [
        'name' => "Extension $name",
        'status' => $loaded,
        'message' => $loaded ? "$name activée ✓" : "$name manquante",
        'critical' => true
    ];
    if (!$loaded) $all_ok = false;
}

// Permissions d'écriture (et création si nécessaire)
$writable_paths = [
    '../../includes' => 'Dossier includes/',
    '../../scripts/logs' => 'Dossier scripts/logs/',
    '../..' => 'Racine du site'
];

foreach ($writable_paths as $path => $name) {
    $full_path = realpath(__DIR__ . '/' . $path);
    if (!$full_path) {
        $full_path = __DIR__ . '/' . $path;
    }
    
    // Créer le dossier s'il n'existe pas
    if (!file_exists($full_path) && $path !== '../..') {
        @mkdir($full_path, 0755, true);
    }
    
    $writable = is_writable($full_path);
    
    // Message avec aide
    if (!$writable && $name === 'Dossier includes/') {
        $message = "$name non accessible en écriture. Exécutez : chmod -R 755 includes/ && chown -R www-data:www-data includes/";
    } elseif (!$writable) {
        $message = "$name non accessible en écriture. Exécutez : chmod -R 755 " . basename($path) . "/";
    } else {
        $message = "$name accessible en écriture ✓";
    }
    
    $checks[] = [
        'name' => "Permissions $name",
        'status' => $writable,
        'message' => $message,
        'critical' => $name === 'Dossier includes/'
    ];
    if (!$writable && $name === 'Dossier includes/') $all_ok = false;
}

// Fichiers SQL présents
$sql_files = [
    '../../sql_database/01_Main_Database.sql' => 'Script base de données principale',
    '../../sql_database/02_Airports_data.sql' => 'Script données aéroports'
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
            <div class="check-item <?= $check['status'] ? 'success' : 'error' ?>">
                <span class="check-icon"><?= $check['status'] ? '✓' : '✗' ?></span>
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
