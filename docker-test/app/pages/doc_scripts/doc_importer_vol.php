<?php
require_once __DIR__ . '/../../includes/require_login.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/menu_logged.php';
?>
<div class="container grades-container">
    <h1><?= t('doc_importer_title') ?></h1>
    <section>
        <h2><?= t('doc_importer_objectif_title') ?></h2>
        <p>
            <?= t('doc_importer_objectif_text') ?>
        </p>
    </section>
    <section>
        <h2><?= t('doc_importer_etapes_title') ?></h2>
        <ol>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_importer_etape1_title') ?></h4>
                <ul>
                    <li><?= t('doc_importer_etape1_text1') ?></li>
                    <li><?= t('doc_importer_etape1_text2') ?> <code>$_POST</code><?= t('doc_importer_etape1_text2_suite') ?></li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_importer_etape2_title') ?></h4>
                <ul>
                    <li><?= t('doc_importer_etape2_text') ?></li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_importer_etape3_title') ?></h4>
                <ul>
                    <li><?= t('doc_importer_etape3_text1') ?></li>
                    <li><?= t('doc_importer_etape3_text2') ?></li>
                    <li><?= t('doc_importer_etape3_text3') ?></li>
                    <li><?= t('doc_importer_etape3_text4') ?></li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_importer_etape4_title') ?></h4>
                <ul>
                    <li><?= t('doc_importer_etape4_text1') ?> <code>FROM_ACARS</code> <?= t('doc_importer_etape4_text1_suite') ?></li>
                    <li><?= t('doc_importer_etape4_text2') ?></li>
                    <li><?= t('doc_importer_etape4_text3') ?> <a href="doc_calcul_cout.php"><?= t('doc_importer_etape4_text3_link') ?></a>).</li>
                    <li><?= t('doc_importer_etape4_text4') ?></li>
                    <li><?= t('doc_importer_etape4_text5') ?></li>
                    <li><?= t('doc_importer_etape4_text6') ?></li>
                    <li><?= t('doc_importer_etape4_text7') ?> <code>finances_recettes</code>.</li>
                    <li><?= t('doc_importer_etape4_text8') ?></li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_importer_etape5_title') ?></h4>
                <ul>
                    <li><?= t('doc_importer_etape5_text1') ?> <code>scripts/logs/importer_vol_direct.log</code>.</li>
                    <li><?= t('doc_importer_etape5_text2') ?></li>
                </ul>
            </li>
        </ol>
    </section>
    <section>
        <h2><?= t('doc_importer_automatisation_title') ?></h2>
        <ul>
            <li><?= t('doc_importer_automatisation1') ?></li>
            <li><?= t('doc_importer_automatisation2') ?> <code>importer_vol_direct.log</code> <?= t('doc_importer_automatisation2_suite') ?></li>
            <li><?= t('doc_importer_automatisation3') ?> <code>$mailSummaryEnabled</code>.</li>
        </ul>
    </section>
    <section>
        <h2><?= t('doc_importer_exemple_title') ?></h2>
        <pre class="code-example">
<?= t('doc_importer_exemple_log') ?>
        </pre>
    </section>
    <div class="text-center" style="margin-top: 38px;">
        <a href="/pages/documentation.php" class="btn"><?= t('doc_back_link') ?></a>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
