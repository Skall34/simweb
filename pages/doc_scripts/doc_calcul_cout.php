<?php
require_once __DIR__ . '/../../includes/require_login.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/menu_logged.php';
?>
<div class="container grades-container">
    <h1><?= t('doc_calcul_title') ?></h1>
    <section>
        <h2><?= t('doc_calcul_section_title') ?></h2>
        <p><?= t('doc_calcul_intro') ?></p>
        <h3><?= t('doc_calcul_params_title') ?></h3>?></h3>
        <ul>
            <li><?= t('doc_calcul_param1') ?></li>
            <li><?= t('doc_calcul_param2') ?></li>
            <li><?= t('doc_calcul_param3') ?></li>
            <li><?= t('doc_calcul_param4') ?></li>
            <li><?= t('doc_calcul_param5') ?></li>
            <li><?= t('doc_calcul_param6') ?></li>
            <li><?= t('doc_calcul_param7') ?></li>
        </ul>
        <h3><?= t('doc_calcul_etapes_title') ?></h3>
        <ol>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_calcul_etape1_title') ?></h4>
                <ul>
                    <li><?= t('doc_calcul_etape1_coef1') ?></li>
                    <li><?= t('doc_calcul_etape1_coef2') ?></li>
                    <li><?= t('doc_calcul_etape1_coef3') ?></li>
                    <li><?= t('doc_calcul_etape1_objectif') ?></li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_calcul_etape2_title') ?></h4>
                <ul>
                    <li><?= t('doc_calcul_etape2_text1') ?></li>
                    <li><?= t('doc_calcul_etape2_text2') ?></li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_calcul_etape3_title') ?></h4>
                <ul>
                    <li><?= t('doc_calcul_etape3_text1') ?></li>
                    <li><?= t('doc_calcul_etape3_text2') ?></li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_calcul_etape4_title') ?></h4>
                <ul>                   
                    <li><?= t('doc_calcul_etape4_revenu') ?></li>
                    <li><?= t('doc_calcul_etape4_carburant') ?></li>
                    <li><?= t('doc_calcul_etape4_appareil') ?></li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_calcul_etape5_title') ?></h4>
                <ul>                   
                    <li><?= t('doc_calcul_etape5_revenu') ?></li>
                    <li><?= t('doc_calcul_etape5_carburant') ?></li>
                    <li><?= t('doc_calcul_etape5_appareil') ?></li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_calcul_etape6_title') ?></h4>
                <ul>
                    <li><?= t('doc_calcul_etape6_formule') ?><br>
                        <code><?= t('doc_calcul_etape6_formule_code') ?></code>
                    </li>
                    <li><?= t('doc_calcul_etape6_log') ?></li>
                </ul>
            </li>
        </ol>
        <h3><?= t('doc_calcul_exemple_title') ?></h3>
        <ul>
            <li><?= t('doc_calcul_exemple_fret') ?></li>
            <li><?= t('doc_calcul_exemple_duree') ?></li>
            <li><?= t('doc_calcul_exemple_distance') ?></li>
            <li><?= t('doc_calcul_exemple_mission') ?></li>
            <li><?= t('doc_calcul_exemple_carburant') ?></li>
            <li><?= t('doc_calcul_exemple_note') ?></li>
            <li><?= t('doc_calcul_exemple_cout') ?></li>
        </ul>
        <ul class="exemple-calculs">
            <li><?= t('doc_calcul_exemple_calc1') ?></li>
            <li><?= t('doc_calcul_exemple_calc2') ?></li>
            <li><?= t('doc_calcul_exemple_calc3') ?></li>
            <li><?= t('doc_calcul_exemple_calc4') ?></li>
        </ul>
        <h3><?= t('doc_calcul_fonctions_title') ?></h3>
        <ul>
            <li><code>coef_note($note)</code>.</li>
            <li><code>getMajorationMission($mission_libelle)</code> : <?= t('doc_calcul_fonction2') ?></li>
            <li><code>getCoutHoraire($immat)</code> : <?= t('doc_calcul_fonction3') ?></li>
            <li><code>calculerRevenuNetVol($payload, $temps_vol, $majoration_mission, $carburant, $note, $cout_horaire)</code> : <?= t('doc_calcul_fonction4') ?></li>
        </ul>
    </section>
    <div class="text-center" style="margin-top: 38px;">
        <a href="/pages/documentation.php" class="btn"><?= t('doc_back_link') ?></a>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
