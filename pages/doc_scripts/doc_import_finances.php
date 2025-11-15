<?php
require_once __DIR__ . '/../../includes/require_login.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/menu_logged.php';
?>
<div class="container grades-container">
    <h1><?= t('doc_import_finances_title') ?></h1>
    <section>
        <h2><?= t('doc_import_finances_fonction_title') ?></h2>
        <p><?= t('doc_import_finances_fonction_text') ?></p>
    </section>
    <section>
        <h2><?= t('doc_import_finances_automatisation_title') ?></h2>
        <ul>
            <li><?= t('doc_import_finances_automatisation1') ?></li>
            <li><?= t('doc_import_finances_automatisation2') ?></li>
            <li><?= t('doc_import_finances_automatisation3') ?></li>
        </ul>
    </section>
    <div class="text-center" style="margin-top: 38px;">
        <a href="/pages/documentation.php" class="btn"><?= t('doc_back_link') ?></a>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
