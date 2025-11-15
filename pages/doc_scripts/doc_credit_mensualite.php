<?php
require_once __DIR__ . '/../../includes/require_login.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/menu_logged.php';
?>
<div class="container grades-container">
    <h1><?= t('doc_credit_title') ?></h1>
    <section>
        <h2><?= t('doc_credit_fonction_title') ?></h2>
        <p><?= t('doc_credit_fonction_text') ?></p>
    </section>
    <section>
        <h2><?= t('doc_credit_fonctionnement_title') ?></h2>
        <ol class="fonctionnement-list">
          <li><?= t('doc_credit_etape1') ?> <span class="details-text"><?= t('doc_credit_etape1_details') ?></span>.</li>
          <li><?= t('doc_credit_etape2') ?>
            <ul class="sub-list">
              <li><?= t('doc_credit_etape2_sub1') ?></li>
              <li><?= t('doc_credit_etape2_sub2') ?></li>
              <li><?= t('doc_credit_etape2_sub3') ?> <b><?= t('doc_credit_etape2_sub3_suite') ?></b>.</li>
            </ul>
          </li>
          <li><?= t('doc_credit_etape3') ?></li>
          <li><?= t('doc_credit_etape4') ?></li>
        </ol>
    </section>
    <section>
        <h2><?= t('doc_credit_automatisation_title') ?></h2>
        <ul>
            <li><?= t('doc_credit_automatisation1') ?></li>
            <li><?= t('doc_credit_automatisation2') ?></li>
            <li><?= t('doc_credit_automatisation3') ?></li>
        </ul>
    </section>
    <div class="text-center" style="margin-top: 38px;">
        <a href="/pages/documentation.php" class="btn"><?= t('doc_back_link') ?></a>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
