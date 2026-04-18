<?php
require_once __DIR__ . '/../../includes/require_login.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/menu_logged.php';
?>
<div class="container grades-container">
    <h1><?= t('doc_cleanup_reservations_title') ?></h1>
    
    <section>
        <h2><?= t('doc_cleanup_reservations_objectif_title') ?></h2>
        <p><?= t('doc_cleanup_reservations_objectif_text') ?></p>
    </section>
    
    <section>
        <h2><?= t('doc_cleanup_reservations_cas_title') ?></h2>
        <ul>
            <li><?= t('doc_cleanup_reservations_cas1') ?></li>
            <li><?= t('doc_cleanup_reservations_cas2') ?></li>
        </ul>
    </section>
    
    <section>
        <h2><?= t('doc_cleanup_reservations_logique_title') ?></h2>
        <p><?= t('doc_cleanup_reservations_logique_text') ?> <code>CARNET_DE_VOL_GENERAL</code>.</p>
        <p><strong><?= t('doc_cleanup_reservations_logique_criteres') ?></strong></p>
        <ul>
            <li><?= t('doc_cleanup_reservations_logique_c1') ?> (<code>pilote_id</code>)</li>
            <li><?= t('doc_cleanup_reservations_logique_c2') ?> (<code>immat</code>)</li>
            <li><?= t('doc_cleanup_reservations_logique_c3') ?></li>
            <li><?= t('doc_cleanup_reservations_logique_c4') ?></li>
        </ul>
    </section>
    
    <section>
        <h2><?= t('doc_cleanup_reservations_actions_title') ?></h2>
        <ul>
            <li><?= t('doc_cleanup_reservations_action1') ?></li>
            <li><?= t('doc_cleanup_reservations_action2') ?> <code>FLOTTE.reservee = 0</code>.</li>
            <li><?= t('doc_cleanup_reservations_action3') ?> <code>scripts/logs/cleanup_orphan_reservations.log</code>.</li>
        </ul>
    </section>
    
    <section>
        <h2><?= t('doc_cleanup_reservations_automatisation_title') ?></h2>
        <ul>
            <li><?= t('doc_cleanup_reservations_auto1') ?></li>
            <li><?= t('doc_cleanup_reservations_auto2') ?></li>
            <li><?= t('doc_cleanup_reservations_auto3') ?> <code>cleanup_orphan_reservations.log</code> <?= t('doc_cleanup_reservations_auto3_suite') ?></li>
        </ul>
        <p><strong>Exemple CRON :</strong></p>
        <pre><code>*/10 * * * * php /path/to/simweb/scripts/cleanup_orphan_reservations.php</code></pre>
    </section>
    
    <section>
        <h2><?= t('doc_cleanup_reservations_exemple_title') ?></h2>
        <pre><code><?= t('doc_cleanup_reservations_exemple_log') ?></code></pre>
    </section>
    
    <p><a href="/pages/documentation.php"><?= t('doc_back_link') ?></a></p>
</div>
<?php include(__DIR__ . '/../../includes/footer.php'); ?>
