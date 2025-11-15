<?php
require_once __DIR__ . '/../../includes/require_login.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/menu_logged.php';
?>
<div class="container grades-container">
    <h1><?= t('doc_paiement_title') ?></h1>
    <section>
        <h2><?= t('doc_paiement_objectif_title') ?></h2>
        <p>
            <?= t('doc_paiement_objectif_text') ?>
        </p>
    </section>
    <section>
        <h2><?= t('doc_paiement_principe_title') ?></h2>
        <ol>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_paiement_etape1_title') ?></h4>
                <ul>
                    <li><?= t('doc_paiement_etape1_text1') ?></li>
                    <li><?= t('doc_paiement_etape1_text2') ?> (<code>GRADES.taux_horaire</code>).</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_paiement_etape2_title') ?></h4>
                <ul>
                    <?php
                    // Connexion DB pour récupérer le bonus fret
                    require_once __DIR__ . '/../../includes/db_connect.php';
                    $bonusFret = 2;
                    try {
                        $stmt = $pdo->prepare("SELECT valeur FROM VARIABLES_CONFIG WHERE nom = 'bonus_fret_kg'");
                        $stmt->execute();
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($row && is_numeric($row['valeur'])) {
                            $bonusFret = $row['valeur'];
                        }
                    } catch (Exception $e) {}
                    ?>
                    <li><?= t('doc_paiement_etape2_text') ?> <strong><?= htmlspecialchars($bonusFret) ?>&nbsp;<?= t('doc_paiement_etape2_kg') ?></strong> <?= t('doc_paiement_etape2_suite') ?> <code>payload</code> <?= t('doc_paiement_etape2_suite2') ?> <code>CARNET_DE_VOL_GENERAL</code>).</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_paiement_etape3_title') ?></h4>
                <ul>
                    <li><?= t('doc_paiement_etape3_text1') ?> <code>SALAIRES</code>.</li>
                    <li><?= t('doc_paiement_etape3_text2') ?> (<code>PILOTES.revenus</code>) <?= t('doc_paiement_etape3_text2_suite') ?></li>
                    <li><?= t('doc_paiement_etape3_text3') ?> <code>finances_depenses</code> <?= t('doc_paiement_etape3_text3_suite') ?></li>
                </ul>
            </li>
        </ol>
    </section>
    <section>
        <h2><?= t('doc_paiement_deroulement_title') ?></h2>
        <ol>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_paiement_deroul1_title') ?></h4>
                <ul>
                    <li><?= t('doc_paiement_deroul1_text') ?> <code>PILOTES</code>.</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_paiement_deroul2_title') ?></h4>
                <ul>
                    <li><?= t('doc_paiement_deroul2_text1') ?></li>
                    <li><?= t('doc_paiement_deroul2_text2') ?></li>
                    <li><?= t('doc_paiement_deroul2_text3') ?></li>
                    <li><?= t('doc_paiement_deroul2_text4') ?></li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_paiement_deroul3_title') ?></h4>
                <ul>
                    <li><?= t('doc_paiement_deroul3_text1') ?> <code>SALAIRES</code>.</li>
                    <li><?= t('doc_paiement_deroul3_text2') ?></li>
                    <li><?= t('doc_paiement_deroul3_text3') ?></li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_paiement_deroul4_title') ?></h4>
                <ul>
                    <li><?= t('doc_paiement_deroul4_text1') ?> <code>finances_depenses</code> <?= t('doc_paiement_deroul4_text1_suite') ?> <code>salaire</code><?= t('doc_paiement_deroul4_text1_suite2') ?></li>
                    <li><?= t('doc_paiement_deroul4_text2') ?> (<code>ADMIN_EMAIL</code>) <?= t('doc_paiement_deroul4_text2_suite') ?></li>
                    <li><?= t('doc_paiement_deroul4_text3') ?> <code>scripts/logs/paiement_salaires.log</code> <?= t('doc_paiement_deroul4_text3_suite') ?></li>
                </ul>
            </li>
        </ol>
    </section>
    <section>
        <h2><?= t('doc_paiement_automatisation_title') ?></h2>
        <ul>
            <li><?= t('doc_paiement_automatisation1') ?></li>
            <li><?= t('doc_paiement_automatisation2') ?> <code>$test_mode</code>).</li>
            <li><?= t('doc_paiement_automatisation3') ?> <code>paiement_salaires.log</code> <?= t('doc_paiement_automatisation3_suite') ?></li>
        </ul>
    </section>
    <section>
        <h2><?= t('doc_paiement_exemple_title') ?></h2>
        <pre class="code-example">
<?= t('doc_paiement_exemple_log') ?>
        </pre>
    </section>
    <div class="text-center" style="margin-top: 38px;">
        <a href="/pages/documentation.php" class="btn"><?= t('doc_back_link') ?></a>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
