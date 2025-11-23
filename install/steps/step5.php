<?php
/**
 * Étape 5 : Installation terminée + Checklist de démarrage
 */


?>

<div class="step-content final-step">
    <div class="success-icon">🎉</div>
    
    <div class="next-steps-box">
        <h3><?= t('install_step5_next_steps_title') ?></h3>
        <ol>
            <li>
                <strong><?= t('install_step5_login') ?></strong><br>
                <small><?= t('install_step5_login_info') ?></small>
            </li>
            <li>
                <strong><?= t('install_step5_configure') ?></strong><br>
                <small><?= t('install_step5_configure_info') ?></small>
            </li>
            <li>
                <strong><?= t('install_step5_manage_fleet') ?></strong><br>
                <small><?= t('install_step5_manage_fleet_info') ?></small>
            </li>
            <li>
                <strong><?= t('install_step5_create_missions') ?></strong><br>
                <small><?= t('install_step5_create_missions_info') ?></small>
            </li>
            <li>
                <strong><?= t('install_step5_register_pilots') ?></strong><br>
                <small><?= t('install_step5_register_pilots_info') ?></small>
            </li>
        </ol>
    </div>

    <div class="resources-box">
        <h3><?= t('install_step5_resources_title') ?></h3>
        <ul>
            <li><?= t('install_step5_resources_docs') ?></li>
            <li><?= t('install_step5_resources_acars') ?></li>
            <li><?= t('install_step5_resources_support') ?></li>
        </ul>
    </div>

    <div class="security-note">
        <h4><?= t('install_step5_security_title') ?></h4>
        <p><?= t('install_step5_security_text') ?></p>
        <p><strong><?= t('install_step5_reinstall_info') ?></strong></p>
        <ol>
            <li><?= t('install_step5_remove_installed') ?></li>
            <li><?= t('install_step5_remove_db_files') ?></li>
            <li><?= t('install_step5_remove_db') ?></li>
            <li><?= t('install_step5_restart_installer') ?></li>
        </ol>
    </div>

    <div class="actions center">
        <a href="/index.php" class="btn btn-primary btn-large"><?= t('install_step5_btn_go_home') ?></a>
    </div>

</div>

<style>
.final-step {
    text-align: center;
}

.success-icon {
    font-size: 80px;
    margin: 20px 0;
}

.lead {
    font-size: 1.2em;
    color: #666;
    margin-bottom: 30px;
}

.credentials-box {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin: 15px 0;
    font-size: 1.1em;
}

.credentials-box code {
    background: #e9ecef;
    padding: 5px 10px;
    border-radius: 3px;
    font-size: 1.1em;
    color: #d63384;
}

.next-steps-box, .resources-box {
    text-align: left;
    margin: 20px 0;
}

.next-steps-box ol, .resources-box ul {
    line-height: 1.8;
}

.next-steps-box li {
    margin-bottom: 15px;
}

.next-steps-box small {
    color: #666;
    display: block;
    margin-top: 5px;
}

.security-note {
    background: #fff3cd;
    border: 1px solid #ffc107;
    padding: 15px;
    border-radius: 5px;
    margin: 20px 0;
    text-align: left;
}

.security-note h4 {
    margin-top: 0;
    color: #856404;
}

.security-note ol {
    margin-bottom: 0;
}

.center {
    justify-content: center;
}

.btn-large {
    font-size: 1.2em;
    padding: 15px 30px;
}

.footer-note {
    margin-top: 40px;
    color: #666;
    font-size: 0.95em;
}

/* Checklist styles */
.checklist-box {
    background: #f8f9fa;
    border: 2px solid #1a3552;
    border-radius: 8px;
    padding: 25px;
    margin: 30px 0;
    text-align: left;
}

.checklist-box h3 {
    color: #1a3552;
    margin-top: 0;
    text-align: center;
}

.checklist-intro {
    text-align: center;
    color: #666;
    margin-bottom: 20px;
}

.checklist {
    list-style: none;
    padding: 0;
    margin: 20px 0;
}

.checklist li {
    display: flex;
    align-items: flex-start;
    padding: 15px;
    margin-bottom: 15px;
    background: #fff;
    border-radius: 6px;
    border-left: 4px solid #ddd;
    transition: all 0.3s ease;
}

.checklist li.done {
    border-left-color: #28a745;
    background: #f1f9f4;
}

.checklist li.todo {
    border-left-color: #ffc107;
    background: #fffbf0;
}

.checklist .checkbox {
    font-size: 24px;
    font-weight: bold;
    margin-right: 15px;
    min-width: 30px;
}

.checklist li.done .checkbox {
    color: #28a745;
}

.checklist li.todo .checkbox {
    color: #ffc107;
}

.task-content {
    flex: 1;
}

.task-content strong {
    color: #1a3552;
    display: block;
    margin-bottom: 5px;
}

.task-content small {
    color: #666;
    display: block;
    margin-bottom: 8px;
}

.task-link {
    color: #007bff;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.95em;
}

.task-link:hover {
    text-decoration: underline;
}

.all-done-message {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
    padding: 15px;
    border-radius: 6px;
    text-align: center;
    margin-top: 20px;
}

.pending-message {
    background: #fff3cd;
    border: 1px solid #ffc107;
    color: #856404;
    padding: 15px;
    border-radius: 6px;
    text-align: center;
    margin-top: 20px;
}

/* Slight horizontal offset so text isn't glued to the left edge */
.next-steps-box,
.resources-box,
.security-note {
    padding-left: 18px;
    padding-right: 18px;
}
</style>
