<?php
// Centralized login guard and normalized includes
require_once __DIR__ . '/../includes/require_login.php';
require_once __DIR__ . '/../lang.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/menu_logged.php';
?>
    <div class="container" style="max-width:900px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
        <h1 style="text-align:center;color:#2c3e50;margin-bottom:32px;"><?= t('documentation_title') ?></h1>
        <section>
            <h2><?= t('documentation_section_general_title') ?></h2>
            <p><?= t('documentation_section_general_intro') ?></p>
            <p><?= t('documentation_section_general_simulators') ?></p>
            <p><?= t('documentation_section_general_acars') ?> <a href="https://github.com/Skall34/SimAddon/releases" target="_blank" style="color:#1a3552;font-weight:bold;text-decoration:underline;"><?= t('documentation_link_repo_github') ?></a></p>
            <p><?= t('documentation_section_general_wiki') ?> <a href="https://github.com/Skall34/SimAddon/wiki" target="_blank" style="color:#1a3552;font-weight:bold;text-decoration:underline;"><?= t('documentation_link_wiki') ?></a></p>
            
        </section>
        
        <section>
            <h2><?= t('documentation_section_missions_title') ?></h2>
            <ul>
                <li><strong><?= t('documentation_section_missions_types') ?></strong>
                    <ul>
                        <li><strong><?= t('documentation_section_missions_ponctuelles') ?></strong> <?= t('documentation_section_missions_ponctuelles_desc') ?></li>
                        <li><strong><?= t('documentation_section_missions_permanentes') ?></strong> <?= t('documentation_section_missions_permanentes_desc') ?></li>
                        <?= t('documentation_section_missions_coef') ?>
                        <li><strong><?= t('documentation_section_missions_libre') ?></strong> <?= t('documentation_section_missions_libre_desc') ?></li>
                    </ul>
            </ul>
        </section>
        <section>
        <?php
    // DB already included above by normalized include
        try {
            $stmt = $pdo->query("SELECT libelle, majoration_mission, Active FROM MISSIONS ORDER BY libelle");
            $missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $missions = [];
            echo '<p style="color:red;">Erreur lors de la récupération des missions : ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        if (!empty($missions)) {
            echo '<h3 style="margin-top:30px;">' . t('documentation_section_missions_detail_title') . '</h3>';
            echo '<table style="max-width:480px;font-size:0.92em;border-collapse:collapse;margin-bottom:20px;box-shadow:0 1px 6px rgba(0,0,0,0.07);">';
            echo '<tr style="background:#e6f0fa;color:#1a3552;font-weight:bold;">';
            echo '<th style="padding:4px 8px;">' . t('documentation_section_missions_table_libelle') . '</th><th style="padding:4px 8px;">' . t('documentation_section_missions_table_majoration') . '</th><th style="padding:4px 8px;">' . t('documentation_section_missions_table_active') . '</th>';
            echo '</tr>';
            foreach ($missions as $m) {
                echo '<tr style="background:#fff;">';
                echo '<td style="padding:3px 8px;">' . htmlspecialchars($m['libelle']) . '</td>';
                $maj = $m['majoration_mission'];
                if (is_numeric($maj)) {
                    $maj = rtrim(rtrim(number_format($maj, 2, '.', ''), '0'), '.');
                }
                echo '<td style="padding:3px 8px;text-align:center;">' . htmlspecialchars($maj) . '</td>';
                if (isset($m['Active']) && (int)$m['Active'] != 0) {
                    echo '<td style="padding:3px 8px;text-align:center;">' . t('documentation_section_missions_table_active_yes') . '</td>';
                } else {
                    echo '<td style="padding:3px 8px;text-align:center;color:#c0392b;font-weight:bold;">' . t('documentation_section_missions_table_active_no') . '</td>';
                }
                echo '</tr>';
            }
            echo '</table>';
        }
        ?>
        </section>
        
        <section>
            <h2><?= t('documentation_section_automations_title') ?></h2>
            <ul>
                <li><strong><?= t('documentation_section_automations_assurance_title') ?></strong> <?= t('documentation_section_automations_assurance_desc') ?></li>
                <li><strong><?= t('documentation_section_automations_recette_title') ?></strong> <?= t('documentation_section_automations_recette_desc') ?></li>
                <li><strong><?= t('documentation_section_automations_credit_title') ?></strong> <?= t('documentation_section_automations_credit_desc') ?></li>
                <li><strong><?= t('documentation_section_automations_maintenance_title') ?></strong> <?= t('documentation_section_automations_maintenance_desc') ?></li>
                <li><strong><?= t('documentation_section_automations_salaires_title') ?></strong> <?= t('documentation_section_automations_salaires_desc') ?></li>
                <li><strong><?= t('documentation_section_automations_promotion_title') ?></strong> <?= t('documentation_section_automations_promotion_desc') ?></li>
                <li><strong><?= t('documentation_section_automations_fret_title') ?></strong> <?= t('documentation_section_automations_fret_desc') ?></li>
            </ul>
        </section>
        
        <section>
            <h2><?= t('documentation_section_scripts_title') ?></h2>
            <ul>
                <li><a href="doc_scripts/doc_assurance_mensuelle.php" style="color:#1a3552;font-weight:bold;text-decoration:underline;"><?= t('documentation_section_scripts_assurance') ?></a></li>
                <li><a href="doc_scripts/doc_calcul_cout.php" style="color:#1a3552;font-weight:bold;text-decoration:underline;"><?= t('documentation_section_scripts_calcul_cout') ?></a></li>
                <li><a href="doc_scripts/doc_credit_mensualite.php" style="color:#1a3552;font-weight:bold;text-decoration:underline;"><?= t('documentation_section_scripts_credit') ?></a></li>
                <li><a href="doc_scripts/doc_importer_vol.php" style="color:#1a3552;font-weight:bold;text-decoration:underline;"><?= t('documentation_section_scripts_importer_vol') ?></a></li>
                <li><a href="doc_scripts/doc_maintenance.php" style="color:#1a3552;font-weight:bold;text-decoration:underline;"><?= t('documentation_section_scripts_maintenance') ?></a></li>
                <li><a href="doc_scripts/doc_paiement_salaires_pilotes.php" style="color:#1a3552;font-weight:bold;text-decoration:underline;"><?= t('documentation_section_scripts_salaires') ?></a></li>
                <li><a href="doc_scripts/doc_promotion_grades_pilotes.php" style="color:#1a3552;font-weight:bold;text-decoration:underline;"><?= t('documentation_section_scripts_promotion') ?></a></li>
                <li><a href="doc_scripts/doc_update_fret.php" style="color:#1a3552;font-weight:bold;text-decoration:underline;"><?= t('documentation_section_scripts_update_fret') ?></a></li>
            </ul>
        </section>
    </div>
<!-- Décalage des puces blanches déplacé dans css/styles.css -->
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
