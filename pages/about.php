<?php
// Chemin correct depuis le dossier "pages"
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_guest.php';
?>

<main class="main-container">
    <h2><?= t('about_title') ?></h2>

    <section class="styled-section">
        <p><?= t('about_intro') ?></p>
    </section>

    <section class="styled-section">
        <h3><?= t('about_vision_title') ?></h3>
        <p><?= t('about_vision_text') ?></p>
    </section>

    <section class="styled-section">
        <h3><?= t('about_offers_title') ?></h3>
        <ul class="styled-list">
            <li>✈️ <?= t('about_offers_flightlog') ?></li>
            <li>🗺️ <?= t('about_offers_missions') ?></li>
            <li>🛩️ <?= t('about_offers_fleet') ?></li>
            <li>📊 <?= t('about_offers_stats') ?></li>
            <li>🤝 <?= t('about_offers_community') ?></li>
        </ul>
    </section>

    <section class="styled-section">
        <h3><?= t('about_join_title') ?></h3>
        <p><?= t('about_join_text') ?></p>
    </section>

    <section class="styled-section">
        <h3><?= t('about_contact_title') ?></h3>
        <p><?= t('about_contact_text') ?></p>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>