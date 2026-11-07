<?php
require_once __DIR__ . '/../../includes/require_login.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/menu_logged.php';
?>
<div class="container grades-container">
    <h1><?= t('doc_maintenance_title') ?></h1>
    <section>
        <h2><?= t('doc_maintenance_objectif_title') ?></h2>
        <p>
            <?= t('doc_maintenance_objectif_text') ?> <code>scripts/logs/maintenance.log</code> <?= t('doc_maintenance_objectif_text_suite') ?>
        </p>
    </section>
    <section>
        <h2><?= t('doc_maintenance_etapes_title') ?></h2>
        <ol>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_maintenance_etape1_title') ?></h4>
                <ul>
                    <li><?= t('doc_maintenance_etape1_text') ?> <code>FLOTTE</code>.</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_maintenance_etape2_title') ?></h4>
                <ul>
                    <li><?= t('doc_maintenance_etape2_text') ?> <b><?= t('doc_maintenance_etape2_usure') ?></b> <?= t('doc_maintenance_etape2_et') ?> <b><?= t('doc_maintenance_etape2_statut') ?></b> (<code>status=0</code>)<?= t('doc_maintenance_etape2_suite') ?> <code>status=1</code>, <code>etat=0</code>, <code>compteur_immo=1</code><?= t('doc_maintenance_etape2_details') ?> <code>nb_maintenance</code>.</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_maintenance_etape3_title') ?></h4>
                <ul>
                    <li><?= t('doc_maintenance_etape3_si_maint') ?> <b><?= t('doc_maintenance_etape3_en_maint') ?></b> (<code>status=1</code>) :</li>
                    <li><?= t('doc_maintenance_etape3_compteur1') ?> <code>compteur_immo=1</code><?= t('doc_maintenance_etape3_compteur1_suite') ?> <code>etat=100</code>, <code>status=0</code>, <code>compteur_immo=0</code>.</li>
                    <li><?= t('doc_maintenance_etape3_compteur2') ?> <code>compteur_immo>1</code><?= t('doc_maintenance_etape3_compteur2_suite') ?> <code>etat=1</code>, <code>status=0</code>, <code>compteur_immo=0</code>.</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_maintenance_etape4_title') ?></h4>
                <ul>
                    <li><?= t('doc_maintenance_etape4_si_crash') ?> <b><?= t('doc_maintenance_etape4_crash') ?></b> (<code>status=2</code>) :</li>
                    <li><?= t('doc_maintenance_etape4_compteur0') ?> <code>compteur_immo=0</code><?= t('doc_maintenance_etape4_compteur0_suite') ?> <code>compteur_immo=1</code><?= t('doc_maintenance_etape4_compteur0_incr') ?> <code>nb_maintenance</code>.</li>
                    <li><?= t('doc_maintenance_etape4_compteur12') ?> <code>compteur_immo=1</code> <?= t('doc_maintenance_etape4_compteur12_ou') ?> <code>2</code><?= t('doc_maintenance_etape4_compteur12_suite') ?></li>
                    <li><?= t('doc_maintenance_etape4_compteur3') ?> <code>compteur_immo≥3</code><?= t('doc_maintenance_etape4_compteur3_suite') ?> <code>etat=100</code>, <code>status=0</code>, <code>compteur_immo=0</code>.</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_maintenance_etape5_title') ?></h4>
                <ul>
                    <li><?= t('doc_maintenance_etape5_log') ?> <code>scripts/logs/maintenance.log</code>.</li>
                    <li><?= t('doc_maintenance_etape5_mail') ?></li>
                </ul>
            </li>
        </ol>
    </section>
    <section>
        <h2><?= t('doc_maintenance_automatisation_title') ?></h2>
        <ul>
            <li><?= t('doc_maintenance_automatisation1') ?></li>
            <li><?= t('doc_maintenance_automatisation2') ?> <code>maintenance.log</code> <?= t('doc_maintenance_automatisation2_suite') ?></li>
            <li><?= t('doc_maintenance_automatisation3') ?> <code>$mailSummaryEnabled</code>.</li>
        </ul>
    </section>
    <section>
        <h2>💰 <?= t('doc_maintenance_cout_title') ?></h2>
        <p><?= t('doc_maintenance_cout_intro') ?> <code>finances_depenses</code> <?= t('doc_maintenance_cout_intro_suite') ?></p>
        <ul>
            <li>
                <b><?= t('doc_maintenance_cout_usure') ?></b> :
                <code><?= t('doc_maintenance_cout_usure_formule') ?></code>
            </li>
            <li>
                <b><?= t('doc_maintenance_cout_crash') ?></b> :
                <code><?= t('doc_maintenance_cout_crash_formule') ?></code>
            </li>
        </ul>
        <p>
            <?= t('doc_maintenance_cout_config') ?>
            <a href="/admin/admin_fleet_type.php"><?= t('doc_maintenance_cout_config_lien') ?></a>.
        </p>
        <p>
            <?= t('doc_maintenance_cout_mult') ?>
            <a href="/admin/admin_variables.php"><?= t('doc_maintenance_cout_mult_lien') ?></a>.
        </p>
        <p style="font-style:italic;color:#555;">
            💡 <?= t('doc_maintenance_cout_defaut') ?>
        </p>
    </section>
    <section>
        <h2><?= t('doc_maintenance_exemple_title') ?></h2>
        <pre class="code-example">
<?= t('doc_maintenance_exemple_log') ?>
        </pre>
    </section>
    <div class="text-center" style="margin-top: 38px;">
        <a href="/pages/documentation.php" class="btn"><?= t('doc_back_link') ?></a>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
