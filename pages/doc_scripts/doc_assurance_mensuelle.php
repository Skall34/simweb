<?php
require_once __DIR__ . '/../../includes/require_login.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/menu_logged.php';
// Récupérer le taux d'assurance depuis la base (VARIABLES_CONFIG.nom = 'taux_assurance')
$taux_assurance_pct = '0,20%'; // valeur par défaut affichée (formatée)
try {
    $stmtT = $pdo->prepare("SELECT valeur FROM VARIABLES_CONFIG WHERE nom = 'taux_assurance'");
    if ($stmtT->execute()) {
        $val = $stmtT->fetchColumn();
        if ($val !== false && is_numeric($val)) {
            // En base c'est stocké sous forme décimale (ex: 0.002 pour 0.2%)
            $pct = floatval($val) * 100;
            // Si entier, afficher sans décimales, sinon afficher avec 2 décimales et virgule
            if (floor($pct) == $pct) {
                $taux_assurance_pct = number_format($pct, 0, ',', ' ') . '%';
            } else {
                $taux_assurance_pct = number_format($pct, 2, ',', ' ') . '%';
            }
        }
    }
} catch (Exception $e) {
    // Ne pas planter la page de documentation si problème BDD
}
?>
<div class="container grades-container">
    <h1><?= t('doc_assurance_title') ?></h1>
    <section>
        <h2><?= t('doc_assurance_objectif_title') ?></h2>
        <p>
            <?= t('doc_assurance_objectif_text') ?>
        </p>
    </section>
    <section>
        <h2><?= t('doc_assurance_principe_title') ?></h2>
        <ul>
            <li><b><?= t('doc_assurance_principe_assiette') ?></b> <strong><?= htmlspecialchars($taux_assurance_pct) ?> <?= t('doc_assurance_principe_assiette_text') ?></strong> (<?= t('doc_assurance_principe_field') ?> <code>balance_actuelle</code> <?= t('doc_assurance_principe_table') ?> <code>BALANCE_COMMERCIALE</code>).</li>
            <li><?= t('doc_assurance_principe_negative') ?></li>
            <li><?= t('doc_assurance_principe_enregistrement') ?> <code>finances_depenses</code> <?= t('doc_assurance_principe_commentaire') ?></li>
        </ul>
    </section>
    <section>
        <h2><?= t('doc_assurance_deroulement_title') ?></h2>
        <ol>
            <li><?= t('doc_assurance_etape1') ?> <code>balance_actuelle</code> <?= t('doc_assurance_etape1_suite') ?> <code>BALANCE_COMMERCIALE</code>.</li>
            <li><?= t('doc_assurance_etape2') ?> <code>assurance = abs(balance_actuelle) × <?= htmlspecialchars($taux_assurance_pct) ?></code>.</li>
            <li><?= t('doc_assurance_etape3') ?> <code>finances_depenses</code> (<?= t('doc_assurance_etape3_suite') ?> <code>assurance</code>, <?= t('doc_assurance_etape3_suite2') ?></li>
            <li><?= t('doc_assurance_etape4') ?></li>
            <li><?= t('doc_assurance_etape5') ?> <code>scripts/logs/assurance_mensuelle.log</code> <?= t('doc_assurance_etape5_suite') ?></li>
            <li><?= t('doc_assurance_etape6') ?> (<code>ADMIN_EMAIL</code>), <?= t('doc_assurance_etape6_suite') ?></li>
        </ol>
    </section>
    <section>
        <h2><?= t('doc_assurance_automatisation_title') ?></h2>
        <ul>
            <li><?= t('doc_assurance_automatisation1') ?></li>
            <li><?= t('doc_assurance_automatisation2') ?> <code>$pourcentage</code> <?= t('doc_assurance_automatisation2_suite') ?></li>
            <li><?= t('doc_assurance_automatisation3') ?> <code>assurance_mensuelle.log</code> <?= t('doc_assurance_automatisation3_suite') ?></li>
        </ul>
    </section>
    <section>
        <h2><?= t('doc_assurance_exemple_title') ?></h2>
        <pre class="code-example">
<?= t('doc_assurance_exemple_log') ?>
        </pre>
    </section>
    <div class="text-center" style="margin-top: 38px;">
        <a href="/pages/documentation.php" class="btn"><?= t('doc_back_link') ?></a>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
