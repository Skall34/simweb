<?php
/*
-------------------------------------------------------------
 Page : tresorier.php — Les conseils du Trésorier
 Emplacement : pages/

 Page humoristique et utile qui analyse la santé financière
 de la compagnie et donne des conseils personnalisés.
-------------------------------------------------------------
*/
require_once __DIR__ . '/../includes/require_login.php';
require_once __DIR__ . '/../lang.php';
require_once __DIR__ . '/../includes/db_connect.php';

// =============================================
// REQUÊTES DE DONNÉES
// =============================================

// 1. Avions qui n'ont pas volé depuis longtemps (ou jamais)
$sql_dormeurs = "
    SELECT f.id, f.immat, ft.fleet_type AS type_nom, ft.type AS categorie, ft.cout_appareil,
           f.date_achat, f.recettes, f.etat, f.compteur_immo, f.mode_achat, f.reste_a_payer,
           MAX(c.date_vol) AS dernier_vol,
           DATEDIFF(CURDATE(), COALESCE(MAX(c.date_vol), f.date_achat)) AS jours_sans_vol
    FROM FLOTTE f
    LEFT JOIN FLEET_TYPE ft ON f.fleet_type = ft.id
    LEFT JOIN CARNET_DE_VOL_GENERAL c ON c.appareil_id = f.id
    WHERE f.Actif = 1
    GROUP BY f.id
    ORDER BY jours_sans_vol DESC
";
$dormeurs = $pdo->query($sql_dormeurs)->fetchAll(PDO::FETCH_ASSOC);

// 2. Avions les plus rentables (recettes / prix d'achat)
$sql_rentables = "
    SELECT f.immat, ft.fleet_type AS type_nom, ft.cout_appareil, f.recettes,
           ROUND(f.recettes / NULLIF(ft.cout_appareil, 0) * 100, 1) AS roi_pct,
           COUNT(c.id) AS nb_vols
    FROM FLOTTE f
    LEFT JOIN FLEET_TYPE ft ON f.fleet_type = ft.id
    LEFT JOIN CARNET_DE_VOL_GENERAL c ON c.appareil_id = f.id
    WHERE f.Actif = 1
    GROUP BY f.id
    ORDER BY roi_pct DESC
";
$rentables = $pdo->query($sql_rentables)->fetchAll(PDO::FETCH_ASSOC);

// 3. Avions "gouffre" : beaucoup de maintenance, peu de recettes
$sql_gouffres = "
    SELECT f.immat, ft.fleet_type AS type_nom, f.etat, f.nb_maintenance, f.recettes,
           ft.cout_appareil, f.compteur_immo,
           COALESCE((SELECT SUM(fd.montant) FROM finances_depenses fd WHERE fd.reference_id = f.id AND fd.type IN ('maintenance', 'maintenance_crash', 'maintenance_retro')), 0) AS cout_maintenance_cumule
    FROM FLOTTE f
    LEFT JOIN FLEET_TYPE ft ON f.fleet_type = ft.id
    WHERE f.Actif = 1
    ORDER BY f.nb_maintenance DESC, f.recettes ASC
    LIMIT 5
";
$gouffres = $pdo->query($sql_gouffres)->fetchAll(PDO::FETCH_ASSOC);

// 4. Stats globales rigolottes
$stats = [];
// Total heures de vol
$stats['total_heures'] = $pdo->query("SELECT SEC_TO_TIME(SUM(TIME_TO_SEC(temps_vol))) FROM CARNET_DE_VOL_GENERAL")->fetchColumn() ?: '00:00:00';
// Nombre total de vols
$stats['total_vols'] = intval($pdo->query("SELECT COUNT(*) FROM CARNET_DE_VOL_GENERAL")->fetchColumn());
// Fuel total consommé
$stats['fuel_total'] = floatval($pdo->query("SELECT SUM(fuel_depart - fuel_arrivee) FROM CARNET_DE_VOL_GENERAL WHERE fuel_depart > fuel_arrivee")->fetchColumn());
// Pilote le plus actif
$pilote_actif = $pdo->query("
    SELECT p.callsign, COUNT(c.id) AS nb_vols
    FROM CARNET_DE_VOL_GENERAL c
    JOIN PILOTES p ON c.pilote_id = p.id
    GROUP BY c.pilote_id
    ORDER BY nb_vols DESC LIMIT 1
")->fetch(PDO::FETCH_ASSOC);
$stats['pilote_star'] = $pilote_actif ? $pilote_actif['callsign'] : '?';
$stats['pilote_star_vols'] = $pilote_actif ? $pilote_actif['nb_vols'] : 0;

// Avion le plus voyageur
$avion_voyageur = $pdo->query("
    SELECT f.immat, COUNT(c.id) AS nb_vols
    FROM CARNET_DE_VOL_GENERAL c
    JOIN FLOTTE f ON c.appareil_id = f.id
    GROUP BY c.appareil_id
    ORDER BY nb_vols DESC LIMIT 1
")->fetch(PDO::FETCH_ASSOC);
$stats['avion_star'] = $avion_voyageur ? $avion_voyageur['immat'] : '?';
$stats['avion_star_vols'] = $avion_voyageur ? $avion_voyageur['nb_vols'] : 0;

// Aéroport le plus visité
$aeroport_top = $pdo->query("
    SELECT destination, COUNT(*) AS nb FROM CARNET_DE_VOL_GENERAL GROUP BY destination ORDER BY nb DESC LIMIT 1
")->fetch(PDO::FETCH_ASSOC);
$stats['aeroport_star'] = $aeroport_top ? $aeroport_top['destination'] : '?';
$stats['aeroport_star_nb'] = $aeroport_top ? $aeroport_top['nb'] : 0;

// Balance commerciale
$stats['balance'] = floatval($pdo->query("SELECT balance_actuelle FROM BALANCE_COMMERCIALE WHERE id = 1")->fetchColumn());

// Nombre d'appareils actifs
$stats['nb_avions'] = intval($pdo->query("SELECT COUNT(*) FROM FLOTTE WHERE Actif = 1")->fetchColumn());

// Valeur totale de la flotte
$stats['valeur_flotte'] = floatval($pdo->query("SELECT COALESCE(SUM(ft.cout_appareil), 0) FROM FLOTTE f JOIN FLEET_TYPE ft ON f.fleet_type = ft.id WHERE f.Actif = 1")->fetchColumn());

// Avions à crédit
$stats['nb_credit'] = intval($pdo->query("SELECT COUNT(*) FROM FLOTTE WHERE mode_achat = 'credit' AND reste_a_payer > 0 AND Actif = 1")->fetchColumn());
$stats['dette_totale'] = floatval($pdo->query("SELECT COALESCE(SUM(reste_a_payer), 0) FROM FLOTTE WHERE mode_achat = 'credit' AND reste_a_payer > 0 AND Actif = 1")->fetchColumn());

// Pire note de vol
$pire_vol = $pdo->query("
    SELECT c.note_du_vol, c.date_vol, p.callsign, f.immat, c.depart, c.destination
    FROM CARNET_DE_VOL_GENERAL c
    JOIN PILOTES p ON c.pilote_id = p.id
    JOIN FLOTTE f ON c.appareil_id = f.id
    WHERE c.note_du_vol IS NOT NULL
    ORDER BY c.note_du_vol ASC LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

// Meilleure note de vol
$best_vol = $pdo->query("
    SELECT c.note_du_vol, c.date_vol, p.callsign, f.immat, c.depart, c.destination
    FROM CARNET_DE_VOL_GENERAL c
    JOIN PILOTES p ON c.pilote_id = p.id
    JOIN FLOTTE f ON c.appareil_id = f.id
    WHERE c.note_du_vol IS NOT NULL
    ORDER BY c.note_du_vol DESC LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

// =============================================
// LOGIQUE DU "MOOD" DU TRÉSORIER
// =============================================
$mood = 'neutral';
$mood_icon = '🧐';
if ($stats['balance'] > $stats['valeur_flotte'] * 0.5) {
    $mood = 'happy'; $mood_icon = '😎';
} elseif ($stats['balance'] > 0) {
    $mood = 'ok'; $mood_icon = '🙂';
} elseif ($stats['balance'] > -$stats['valeur_flotte'] * 0.2) {
    $mood = 'worried'; $mood_icon = '😰';
} else {
    $mood = 'panic'; $mood_icon = '🚨';
}

// Helper format
function fmt($v, $d = 0) { return number_format(floatval($v), $d, ',', ' '); }

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>

<style>
.tresorier-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
.tresorier-header { text-align: center; margin-bottom: 30px; }
.tresorier-header h1 { font-size: 2em; }
.tresorier-mood { font-size: 4em; display: block; margin: 10px 0; }
.tresorier-quote { font-style: italic; color: #555; font-size: 1.1em; margin: 10px 0 20px; }
.tresorier-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(480px, 1fr)); gap: 20px; margin-bottom: 30px; }
.tresorier-card { background: #f7fbff; border-radius: 12px; padding: 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.tresorier-card h3 { margin-top: 0; color: #0066cc; font-size: 1.1em; border-bottom: 2px solid #e0e0e0; padding-bottom: 8px; margin-bottom: 12px; }
.tresorier-card.alert { background: #fff5f5; border-left: 4px solid #dc3545; }
.tresorier-card.success { background: #f0fff0; border-left: 4px solid #28a745; }
.tresorier-card.info { background: #f0f8ff; border-left: 4px solid #17a2b8; }
.tresorier-card.fun { background: #fffef0; border-left: 4px solid #ffc107; }
.tresorier-table { width: 100%; border-collapse: collapse; font-size: 0.9em; }
.tresorier-table th, .tresorier-table td { padding: 6px 8px; text-align: left; border-bottom: 1px solid #eee; }
.tresorier-table th { color: #555; font-weight: 600; }
.tresorier-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 0.8em; font-weight: 600; }
.badge-danger { background: #ffe0e0; color: #c0392b; }
.badge-warning { background: #fff3cd; color: #856404; }
.badge-ok { background: #d4edda; color: #155724; }
.stat-big { font-size: 1.8em; font-weight: 700; color: #0066cc; }
.stat-label { color: #777; font-size: 0.85em; }
.stats-flex { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; text-align: center; }
.stats-flex > div { min-width: 120px; }
</style>

<div class="tresorier-container">

    <!-- EN-TÊTE AVEC MOOD -->
    <div class="tresorier-header">
        <h1>💰 <?= t('tresorier_title') ?></h1>
        <span class="tresorier-mood"><?= $mood_icon ?></span>
        <p class="tresorier-quote">
        <?php
            $quotes_key = 'tresorier_quote_' . $mood;
            echo t($quotes_key);
        ?>
        </p>
    </div>

    <!-- TABLEAU DE BORD RAPIDE -->
    <div class="tresorier-card info" style="margin-bottom: 20px;">
        <h3>📊 <?= t('tresorier_dashboard') ?></h3>
        <div class="stats-flex">
            <div>
                <div class="stat-big" style="color: <?= $stats['balance'] >= 0 ? '#28a745' : '#dc3545' ?>"><?= fmt($stats['balance']) ?> €</div>
                <div class="stat-label"><?= t('tresorier_balance') ?></div>
            </div>
            <div>
                <div class="stat-big"><?= $stats['nb_avions'] ?></div>
                <div class="stat-label"><?= t('tresorier_nb_avions') ?></div>
            </div>
            <div>
                <div class="stat-big"><?= fmt($stats['valeur_flotte']) ?> €</div>
                <div class="stat-label"><?= t('tresorier_valeur_flotte') ?></div>
            </div>
            <div>
                <div class="stat-big" style="color: #dc3545;"><?= fmt($stats['dette_totale']) ?> €</div>
                <div class="stat-label"><?= t('tresorier_dette') ?> (<?= $stats['nb_credit'] ?> <?= t('tresorier_appareils') ?>)</div>
            </div>
        </div>
    </div>

    <div class="tresorier-grid">

        <!-- AVIONS DORMEURS -->
        <div class="tresorier-card alert">
            <h3>😴 <?= t('tresorier_dormeurs_title') ?></h3>
            <p style="font-size:0.9em;color:#666;"><?= t('tresorier_dormeurs_intro') ?></p>
            <?php
            $dormeurs_alertes = array_filter($dormeurs, fn($d) => $d['jours_sans_vol'] > 60);
            if (empty($dormeurs_alertes)): ?>
                <p style="color:#28a745;font-weight:600;">✅ <?= t('tresorier_dormeurs_ok') ?></p>
            <?php else: ?>
                <table class="tresorier-table">
                    <thead><tr><th><?= t('tresorier_col_immat') ?></th><th><?= t('tresorier_col_type') ?></th><th><?= t('tresorier_col_dernier_vol') ?></th><th><?= t('tresorier_col_jours') ?></th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($dormeurs_alertes, 0, 8) as $d): 
                        $badge = $d['jours_sans_vol'] > 180 ? 'badge-danger' : 'badge-warning';
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($d['immat']) ?></strong></td>
                            <td><?= htmlspecialchars($d['type_nom']) ?></td>
                            <td><?= $d['dernier_vol'] ? date('d/m/Y', strtotime($d['dernier_vol'])) : t('tresorier_jamais') ?></td>
                            <td><span class="tresorier-badge <?= $badge ?>"><?= $d['jours_sans_vol'] ?> j</span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p style="font-size:0.85em;color:#888;margin-top:8px;">
                    💡 <?= t('tresorier_dormeurs_conseil') ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- TOP 5 RENTABILITÉ -->
        <div class="tresorier-card success">
            <h3>🏆 <?= t('tresorier_rentables_title') ?></h3>
            <p style="font-size:0.9em;color:#666;"><?= t('tresorier_rentables_intro') ?></p>
            <table class="tresorier-table">
                <thead><tr><th><?= t('tresorier_col_immat') ?></th><th><?= t('tresorier_col_recettes') ?></th><th>ROI</th><th><?= t('tresorier_col_vols') ?></th></tr></thead>
                <tbody>
                <?php foreach (array_slice($rentables, 0, 5) as $i => $r): 
                    $medal = ['🥇','🥈','🥉','4️⃣','5️⃣'][$i] ?? '';
                ?>
                    <tr>
                        <td><?= $medal ?> <strong><?= htmlspecialchars($r['immat']) ?></strong></td>
                        <td><?= fmt($r['recettes']) ?> €</td>
                        <td><span class="tresorier-badge <?= floatval($r['roi_pct']) >= 100 ? 'badge-ok' : 'badge-warning' ?>"><?= $r['roi_pct'] ?? 0 ?>%</span></td>
                        <td><?= $r['nb_vols'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p style="font-size:0.85em;color:#888;margin-top:8px;">
                💡 <?= t('tresorier_rentables_conseil') ?>
            </p>
        </div>

        <!-- GOUFFRES FINANCIERS -->
        <div class="tresorier-card alert">
            <h3>🕳️ <?= t('tresorier_gouffres_title') ?></h3>
            <p style="font-size:0.9em;color:#666;"><?= t('tresorier_gouffres_intro') ?></p>
            <table class="tresorier-table">
                <thead><tr><th><?= t('tresorier_col_immat') ?></th><th><?= t('tresorier_col_etat') ?></th><th><?= t('tresorier_col_maintenances') ?></th><th><?= t('tresorier_col_cout_maintenance') ?></th><th><?= t('tresorier_col_recettes') ?></th></tr></thead>
                <tbody>
                <?php foreach ($gouffres as $g): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($g['immat']) ?></strong></td>
                        <td><?= $g['etat'] ?>%</td>
                        <td><?= $g['nb_maintenance'] ?></td>
                        <td style="color:#dc3545;font-weight:600;"><?= fmt($g['cout_maintenance_cumule']) ?> €</td>
                        <td><?= fmt($g['recettes']) ?> €</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p style="font-size:0.85em;color:#888;margin-top:8px;">
                💡 <?= t('tresorier_gouffres_conseil') ?>
            </p>
        </div>

        <!-- FUN STATS -->
        <div class="tresorier-card fun">
            <h3>🎲 <?= t('tresorier_fun_title') ?></h3>
            <table class="tresorier-table">
                <tbody>
                    <tr><td>🕐 <?= t('tresorier_fun_heures') ?></td><td><strong><?= $stats['total_heures'] ?></strong></td></tr>
                    <tr><td>🛫 <?= t('tresorier_fun_vols') ?></td><td><strong><?= fmt($stats['total_vols']) ?></strong></td></tr>
                    <tr><td>⛽ <?= t('tresorier_fun_fuel') ?></td><td><strong><?= fmt($stats['fuel_total']) ?> L</strong></td></tr>
                    <tr><td>👨‍✈️ <?= t('tresorier_fun_pilote') ?></td><td><strong><?= htmlspecialchars($stats['pilote_star']) ?></strong> (<?= $stats['pilote_star_vols'] ?> <?= t('tresorier_fun_vols_label') ?>)</td></tr>
                    <tr><td>✈️ <?= t('tresorier_fun_avion') ?></td><td><strong><?= htmlspecialchars($stats['avion_star']) ?></strong> (<?= $stats['avion_star_vols'] ?> <?= t('tresorier_fun_vols_label') ?>)</td></tr>
                    <tr><td>🏛️ <?= t('tresorier_fun_aeroport') ?></td><td><strong><?= htmlspecialchars($stats['aeroport_star']) ?></strong> (<?= $stats['aeroport_star_nb'] ?>×)</td></tr>
                    <?php if ($pire_vol): ?>
                    <tr><td>💩 <?= t('tresorier_fun_pire_vol') ?></td><td><strong><?= $pire_vol['note_du_vol'] ?>/100</strong> — <?= htmlspecialchars($pire_vol['callsign']) ?> (<?= htmlspecialchars($pire_vol['depart']) ?>→<?= htmlspecialchars($pire_vol['destination']) ?>)</td></tr>
                    <?php endif; ?>
                    <?php if ($best_vol): ?>
                    <tr><td>⭐ <?= t('tresorier_fun_best_vol') ?></td><td><strong><?= $best_vol['note_du_vol'] ?>/100</strong> — <?= htmlspecialchars($best_vol['callsign']) ?> (<?= htmlspecialchars($best_vol['depart']) ?>→<?= htmlspecialchars($best_vol['destination']) ?>)</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- CONSEILS DU TRÉSORIER -->
        <div class="tresorier-card info" style="grid-column: 1 / -1;">
            <h3>📝 <?= t('tresorier_conseils_title') ?></h3>
            <ul style="font-size:0.95em;line-height:1.8;">
            <?php
            // Conseils dynamiques basés sur les données
            $conseils = [];

            // Trop de dormeurs
            $nb_dormeurs = count(array_filter($dormeurs, fn($d) => $d['jours_sans_vol'] > 90));
            if ($nb_dormeurs > 0) {
                $conseils[] = str_replace('{n}', $nb_dormeurs, t('tresorier_conseil_dormeurs'));
            }

            // Flotte excédentaire (peu de vols)
            if ($stats['nb_avions'] > 0 && $stats['total_vols'] > 0) {
                $vols_par_avion = $stats['total_vols'] / $stats['nb_avions'];
                if ($vols_par_avion < 5) {
                    $conseils[] = t('tresorier_conseil_flotte_excedentaire');
                }
            }

            // Trop de dette
            if ($stats['dette_totale'] > $stats['valeur_flotte'] * 0.5) {
                $conseils[] = t('tresorier_conseil_dette');
            }

            // Balance négative
            if ($stats['balance'] < 0) {
                $conseils[] = t('tresorier_conseil_negatif');
            }

            // Pas de dette = bien
            if ($stats['nb_credit'] == 0) {
                $conseils[] = t('tresorier_conseil_no_dette');
            }

            // Rentabilité top
            if (!empty($rentables) && floatval($rentables[0]['roi_pct'] ?? 0) >= 100) {
                $conseils[] = str_replace('{immat}', htmlspecialchars($rentables[0]['immat']), t('tresorier_conseil_roi'));
            }

            // Toujours au moins un conseil
            if (empty($conseils)) {
                $conseils[] = t('tresorier_conseil_default');
            }

            foreach ($conseils as $c): ?>
                <li><?= $c ?></li>
            <?php endforeach; ?>
            </ul>
        </div>

    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
