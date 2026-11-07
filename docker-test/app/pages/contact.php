<?php
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_guest.php';
?>

<main>
    <h2><?= t('contact_title') ?></h2>

    <p><?= t('contact_intro') ?></p>

    <?php if (VA_DISCORD_URL): ?>
    <p>
        <a href="<?= VA_DISCORD_URL ?>" target="_blank" style="font-weight: bold; color: #5865F2;">
            👉 <?= t('contact_discord_button') ?>
        </a>
    </p>
    <?php endif; ?>

    <p><?= t('contact_reply') ?></p>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
