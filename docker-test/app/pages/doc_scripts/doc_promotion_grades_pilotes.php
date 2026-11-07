<?php
require_once __DIR__ . '/../../includes/require_login.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/menu_logged.php';
require_once __DIR__ . '/../../includes/db_connect.php';
// Récupérer tous les grades
$stmt = $pdo->query('SELECT nom, description, taux_horaire, niveau FROM GRADES ORDER BY niveau ASC');
$grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container grades-container">
    <h1><?= t('doc_promotion_title') ?></h1>
    <section>
        <h2><?= t('doc_promotion_objectif_title') ?></h2>
        <p>
            <?= t('doc_promotion_objectif_text') ?>
        </p>
    </section>
    <section>
        <h2><?= t('doc_promotion_etapes_title') ?></h2>
        <ol>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_promotion_etape1_title') ?></h4>
                <ul>
                    <li><?= t('doc_promotion_etape1_text') ?></li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_promotion_etape2_title') ?></h4>
                <ul>
                    <li><?= t('doc_promotion_etape2_text') ?></li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_promotion_etape3_title') ?></h4>
                <ul>
                    <li><?= t('doc_promotion_etape3_text1') ?></li>
                    <li><?= t('doc_promotion_etape3_text2') ?></li>
                    <li><?= t('doc_promotion_etape3_text3') ?> <code>scripts/logs/promotion_grades.log</code>.</li>
                </ul>
            </li>
            <li>
                <h4 class="sous-chapitre"><?= t('doc_promotion_etape4_title') ?></h4>
                <ul>
                    <li><?= t('doc_promotion_etape4_text') ?></li>
                </ul>
            </li>
        </ol>
    </section>
    <section>
        <h2><?= t('doc_promotion_automatisation_title') ?></h2>
        <ul>
            <li><?= t('doc_promotion_automatisation1') ?></li>
            <li><?= t('doc_promotion_automatisation2') ?> <code>promotion_grades.log</code> <?= t('doc_promotion_automatisation2_suite') ?></li>
        </ul>
    </section>
    <section>
        <h2><?= t('doc_promotion_grades_title') ?></h2>
        <div class="compte-section grades-table-container">
            <table class="grades-table-promotion">
                <thead>
                    <tr>
                        <th><?= t('doc_promotion_grades_grade') ?></th>
                        <th><?= t('doc_promotion_grades_taux') ?></th>
                        <th><?= t('doc_promotion_grades_condition') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($grades as $grade): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($grade['nom']) ?></strong></td>
                        <td><?= htmlspecialchars($grade['taux_horaire']) ?>&nbsp;€</td>
                        <td><?= htmlspecialchars($grade['description']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <section>
        <h2><?= t('doc_promotion_exemple_title') ?></h2>
        <pre class="code-example">
<?= t('doc_promotion_exemple_log') ?>
        </pre>
    </section>
   
    <div class="text-center" style="margin-top: 38px;">
        <a href="/pages/documentation.php" class="btn"><?= t('doc_back_link') ?></a>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
