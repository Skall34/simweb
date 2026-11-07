<?php
require_once __DIR__ . '/../../includes/require_login.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/menu_logged.php';
?>
<main class="cargo-mission">
    <?php afficherCoefficientMission(); ?>
    <header class="cargo-mission__header">
        <p class="cargo-mission__eyebrow"><?= t('convoyage_mission_eyebrow') ?></p>
        <h1><?= t('convoyage_mission_title') ?></h1>
        <p><?= t('convoyage_mission_intro') ?></p>
    </header>

    <section class="cargo-mission__section cargo-mission__section--highlight">
        <h2><?= t('convoyage_mission_difference_title') ?></h2>
        <p><?= t('convoyage_mission_difference_text') ?></p>
    </section>

    <section class="cargo-mission__section">
        <h2><?= t('convoyage_mission_steps_title') ?></h2>
        <ol class="cargo-mission__steps">
            <li><?= t('convoyage_mission_step_1') ?></li>
            <li><?= t('convoyage_mission_step_2') ?></li>
            <li><?= t('convoyage_mission_step_3') ?></li>
            <li><?= t('convoyage_mission_step_4') ?></li>
        </ol>
    </section>

    <section class="cargo-mission__section">
        <h2><?= t('convoyage_mission_operations_title') ?></h2>
        <ul class="cargo-mission__list">
            <li><?= t('convoyage_mission_operation_1') ?></li>
            <li><?= t('convoyage_mission_operation_2') ?></li>
            <li><?= t('convoyage_mission_operation_3') ?></li>
        </ul>
    </section>

    <section class="cargo-mission__section">
        <h2><?= t('convoyage_mission_examples_title') ?></h2>
        <ul class="cargo-mission__list">
            <li><?= t('convoyage_mission_example_1') ?></li>
            <li><?= t('convoyage_mission_example_2') ?></li>
            <li><?= t('convoyage_mission_example_3') ?></li>
            <li><?= t('convoyage_mission_example_4') ?></li>
        </ul>
    </section>

    <section class="cargo-mission__section">
        <h2><?= t('convoyage_mission_aircraft_title') ?></h2>
        <p><?= t('convoyage_mission_aircraft_text') ?></p>
    </section>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
