<?php
require_once __DIR__ . '/../../includes/require_login.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/menu_logged.php';
?>
<main class="cargo-mission">
    <?php afficherCoefficientMission(); ?>
    <header class="cargo-mission__header">
        <p class="cargo-mission__eyebrow"><?= t('cargo_mission_eyebrow') ?></p>
        <h1><?= t('cargo_mission_title') ?></h1>
        <p><?= t('cargo_mission_intro') ?></p>
    </header>

    <section class="cargo-mission__section cargo-mission__section--highlight">
        <h2><?= t('cargo_mission_dispatch_title') ?></h2>
        <p><?= t('cargo_mission_dispatch_text') ?></p>
        <a class="cargo-mission__link" href="https://freightline.freightline-ops.workers.dev/" target="_blank" rel="noopener noreferrer"><?= t('cargo_mission_dispatch_link') ?></a>
    </section>

    <section class="cargo-mission__section">
        <h2><?= t('cargo_mission_steps_title') ?></h2>
        <ol class="cargo-mission__steps">
            <li><?= t('cargo_mission_step_1') ?></li>
            <li><?= t('cargo_mission_step_2') ?></li>
            <li><?= t('cargo_mission_step_3') ?></li>
            <li><?= t('cargo_mission_step_4') ?></li>
        </ol>
    </section>

    <section class="cargo-mission__section">
        <h2><?= t('cargo_mission_operations_title') ?></h2>
        <ul class="cargo-mission__list">
            <li><?= t('cargo_mission_operation_1') ?></li>
            <li><?= t('cargo_mission_operation_2') ?></li>
            <li><?= t('cargo_mission_operation_3') ?></li>
        </ul>
    </section>

    <section class="cargo-mission__section">
        <h2><?= t('cargo_mission_aircraft_title') ?></h2>
        <ul class="cargo-mission__list">
            <li><?= t('cargo_mission_aircraft_1') ?></li>
            <li><?= t('cargo_mission_aircraft_2') ?></li>
            <li><?= t('cargo_mission_aircraft_3') ?></li>
        </ul>
    </section>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
