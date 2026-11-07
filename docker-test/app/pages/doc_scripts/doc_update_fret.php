<?php
require_once __DIR__ . '/../../includes/require_login.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/menu_logged.php';
?>
<div class="container grades-container">
    <h1><?= t('doc_fret_title') ?></h1>
    <section>
        <h2><?= t('doc_fret_objectif_title') ?></h2>
        <p>
            <?= t('doc_fret_objectif_text') ?>
        </p>
    </section>
    <section>
        <h2><?= t('doc_fret_etapes_title') ?></h2>
        <ol>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_fret_etape1_title') ?></h4>
                <ul>
                    <li><?= t('doc_fret_etape1_text') ?> <code>AEROPORTS</code>.</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_fret_etape2_title') ?></h4>
                <ul>
                    <li><?= t('doc_fret_etape2_text1') ?></li>
                    <li><?= t('doc_fret_etape2_text2') ?></li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_fret_etape3_title') ?></h4>
                <ul>
                    <li><?= t('doc_fret_etape3_text1') ?> <code>scripts/logs/update_fret.log</code>.</li>
                    <li><?= t('doc_fret_etape3_text2') ?></li>
                </ul>
            </li>
        </ol>
    </section>
    <section>
        <h2><?= t('doc_fret_automatisation_title') ?></h2>
        <ul>
            <li><?= t('doc_fret_automatisation1') ?></li>
            <li><?= t('doc_fret_automatisation2') ?> <code>update_fret.log</code> <?= t('doc_fret_automatisation2_suite') ?></li>
        </ul>
    </section>
    <section>
        <h2><?= t('doc_fret_exemple_title') ?></h2>
        <pre class="code-example">
<?= t('doc_fret_exemple_log') ?>
        </pre>
    </section>
    <div class="text-center" style="margin-top: 38px;">
        <a href="/pages/documentation.php" class="btn"><?= t('doc_back_link') ?></a>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

