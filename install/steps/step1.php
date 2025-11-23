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
        <title><?php echo t('install_blocked_title'); ?></title>
        <link rel="stylesheet" href="../style.css">
    </head>
    <body>
        <div class="warning-box">
            <div class="warning-icon">⚠️</div>
            <div class="warning-title">
                <?php if ($installedFileExists): ?>
                    <?php echo t('install_blocked_subtitle_installed'); ?>
                <?php else: ?>
                    <?php echo t('install_blocked_subtitle_config'); ?>
                <?php endif; ?>
            </div>
            <div class="warning-content">
                <?php if ($installedFileExists): ?>
                    <p><strong><?php echo t('install_blocked_text_installed'); ?></strong></p>
                <?php else: ?>
                    <p><strong><?php echo t('install_blocked_text_config'); ?></strong></p>
                <?php endif; ?>
                <ul class="file-list">
                    <?php if ($installedFileExists): ?>
                        <li class="file-found"><?php echo t('install_blocked_file_installed'); ?></li>
                    <?php endif; ?>
                    <?php if ($dbConnectExists): ?>
                        <li class="file-found"><?php echo t('install_blocked_file_config'); ?></li>
                    <?php endif; ?>
                </ul>
                <p><strong><?php echo t('install_blocked_what_to_do'); ?></strong></p>
                <ul>
                    <?php if ($dbConnectExists): ?>
                        <li><?php echo t('install_blocked_action_delete_config'); ?></li>
                    <?php endif; ?>
                    <?php if ($installedFileExists): ?>
                        <li><?php echo t('install_blocked_action_go_home'); ?></li>
                        <li><?php echo t('install_blocked_action_reinstall'); ?></li>
                    <?php endif; ?>
                </ul>
                <p style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-left: 4px solid #0066cc;">
                    <strong><?php echo t('install_blocked_tip'); ?></strong>
                </p>
            </div>
            <div class="actions">
                <?php if ($dbConnectExists): ?>
                    <a href="/install/index.php" class="btn btn-primary"><?php echo t('install_blocked_btn_reload'); ?></a>
                <?php endif; ?>
                <?php if ($installedFileExists): ?>
                    <a href="../../index.php" class="btn btn-primary"><?php echo t('install_blocked_btn_go_home'); ?></a>
                    <a href="../../INSTALLATION.md" class="btn btn-secondary" target="_blank"><?php echo t('install_blocked_btn_documentation'); ?></a>
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
    'name' => t('install_check_php_version'),
    'status' => $php_ok,
    'message' => $php_ok ? t('install_check_php_ok', ['version' => $php_version]) : t('install_check_php_min', ['version' => $php_version]),
    'critical' => true
];
if (!$php_ok) $all_ok = false;

// ...existing code...
$sql_files = [
    '../sql_database/01_Main_Database.sql' => 'install_check_sql_main',
    '../sql_database/02_Airports_data.sql' => 'install_check_sql_airports'
];

foreach ($sql_files as $path => $name_key) {
    $full_path = realpath(__DIR__ . '/' . $path);
    if (!$full_path) {
        $full_path = __DIR__ . '/' . $path;
    }
    $exists = file_exists($full_path);
    $name = t($name_key);
    $checks[] = [
        'name' => $name,
        'status' => $exists,
        'message' => $exists ? t('install_check_sql_found', ['name' => $name]) : t('install_check_sql_missing', ['name' => $name]),
        'critical' => true
    ];
    if (!$exists) $all_ok = false;
}

?>

<div class="step-content">
    <h2><?php echo t('install_step1_title'); ?></h2>
    <p><?php echo t('install_step1_subtitle'); ?></p>

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
            <h3><?php echo t('install_step1_success_title'); ?></h3>
            <p><?php echo t('install_step1_success_text'); ?></p>
        </div>
        
        <div class="actions">
            <a href="?step=2" class="btn btn-primary"><?php echo t('install_step1_btn_continue'); ?></a>
        </div>
    <?php else: ?>
        <div class="error-box">
            <h3><?php echo t('install_step1_error_title'); ?></h3>
            <p><?php echo t('install_step1_error_text'); ?></p>
            <p><strong><?php echo t('install_step1_error_recommendations'); ?></strong></p>
            <ul>
                <li><?php echo t('install_step1_error_php'); ?></li>
                <li><?php echo t('install_step1_error_extensions'); ?></li>
                <li><?php echo t('install_step1_error_permissions'); ?></li>
                <li><?php echo t('install_step1_error_sql'); ?></li>
            </ul>
        </div>
        
        <div class="actions">
            <a href="?step=1" class="btn btn-secondary"><?php echo t('install_step1_btn_recheck'); ?></a>
        </div>
    <?php endif; ?>
</div>
