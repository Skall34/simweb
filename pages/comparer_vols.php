<?php
require_once __DIR__ . '/../includes/require_login.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/calcul_cout.php';
require_once __DIR__ . '/../includes/fonctions_importer_vol.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';

const COMPARE_MAX_FLIGHTS = 8;
const COMPARE_MAX_RESULTS = 200;

$f = [
    'callsign'    => trim($_GET['callsign'] ?? ''),
    'immat'       => trim($_GET['immat'] ?? ''),
    'fleetType'   => trim($_GET['fleetType'] ?? ''),
    'mission'     => trim($_GET['mission'] ?? ''),
    'depart'      => strtoupper(trim($_GET['depart'] ?? '')),
    'dest'        => strtoupper(trim($_GET['dest'] ?? '')),
    'date_from'   => trim($_GET['date_from'] ?? ''),
    'date_to'     => trim($_GET['date_to'] ?? ''),
    'note_min'    => trim($_GET['note_min'] ?? ''),
    'note_max'    => trim($_GET['note_max'] ?? ''),
    'payload_min' => trim($_GET['payload_min'] ?? ''),
    'payload_max' => trim($_GET['payload_max'] ?? ''),
    'sort'        => trim($_GET['sort'] ?? 'recent'),
];
// Aucun résultat n'est affiché tant que la recherche n'a pas été lancée
$searched = isset($_GET['search']) || isset($_GET['compare']);
$compareRequested = isset($_GET['compare']);
$selectedIds = [];
foreach ((array)($_GET['cmp'] ?? []) as $id) {
    $id = (int)$id;
    if ($id > 0 && !in_array($id, $selectedIds, true)) {
        $selectedIds[] = $id;
    }
}
$selectedIds = array_slice($selectedIds, 0, COMPARE_MAX_FLIGHTS);
$sortOptions = [
    'recent' => 'cdvg.date_vol DESC, cdvg.heure_arrivee DESC',
    'best'   => 'cdvg.cout_vol DESC',
    'worst'  => 'cdvg.cout_vol ASC',
];
if (!isset($sortOptions[$f['sort']])) {
    $f['sort'] = 'recent';
}

$pilotsList = $pdo->query("SELECT callsign FROM PILOTES ORDER BY callsign ASC")->fetchAll(PDO::FETCH_COLUMN);
$missionsList = $pdo->query("SELECT DISTINCT libelle FROM MISSIONS WHERE libelle IS NOT NULL AND libelle <> '' ORDER BY libelle ASC")->fetchAll(PDO::FETCH_COLUMN);
$fleetTypeList = $pdo->query("SELECT DISTINCT fleet_type FROM FLEET_TYPE ORDER BY fleet_type ASC")->fetchAll(PDO::FETCH_COLUMN);

$conditions = ['cdvg.annule = 0'];
$params = [];
if ($f['callsign'] !== '')  { $conditions[] = 'p.callsign = :callsign';        $params['callsign'] = $f['callsign']; }
if ($f['immat'] !== '')     { $conditions[] = 'fl.immat LIKE :immat';           $params['immat'] = '%' . $f['immat'] . '%'; }
if ($f['fleetType'] !== '') { $conditions[] = 'ft.fleet_type = :fleetType';     $params['fleetType'] = $f['fleetType']; }
if ($f['mission'] !== '')   { $conditions[] = 'm.libelle = :mission';           $params['mission'] = $f['mission']; }
if ($f['depart'] !== '')    { $conditions[] = 'cdvg.depart LIKE :depart';       $params['depart'] = '%' . $f['depart'] . '%'; }
if ($f['dest'] !== '')      { $conditions[] = 'cdvg.destination LIKE :dest';    $params['dest'] = '%' . $f['dest'] . '%'; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['date_from'])) { $conditions[] = 'cdvg.date_vol >= :date_from'; $params['date_from'] = $f['date_from']; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['date_to']))   { $conditions[] = 'cdvg.date_vol <= :date_to';   $params['date_to'] = $f['date_to']; }
if (is_numeric($f['note_min']))    { $conditions[] = 'cdvg.note_du_vol >= :note_min';  $params['note_min'] = (int)$f['note_min']; }
if (is_numeric($f['note_max']))    { $conditions[] = 'cdvg.note_du_vol <= :note_max';  $params['note_max'] = (int)$f['note_max']; }
if (is_numeric($f['payload_min'])) { $conditions[] = 'cdvg.payload >= :payload_min';   $params['payload_min'] = (float)$f['payload_min']; }
if (is_numeric($f['payload_max'])) { $conditions[] = 'cdvg.payload <= :payload_max';   $params['payload_max'] = (float)$f['payload_max']; }
$where = ' WHERE ' . implode(' AND ', $conditions);
$from = "
    FROM CARNET_DE_VOL_GENERAL cdvg
    LEFT JOIN PILOTES p ON cdvg.pilote_id = p.id
    LEFT JOIN FLOTTE fl ON cdvg.appareil_id = fl.id
    LEFT JOIN FLEET_TYPE ft ON fl.fleet_type = ft.id
    LEFT JOIN MISSIONS m ON cdvg.mission_id = m.id";

$selectCols = "SELECT cdvg.id, cdvg.date_vol, p.callsign, fl.immat, ft.fleet_type AS fleet_type_label,
                   ft.cout_horaire, cdvg.depart, cdvg.destination, cdvg.fuel_depart, cdvg.fuel_arrivee,
                   cdvg.payload, cdvg.note_du_vol, cdvg.cout_vol, cdvg.temps_vol,
                   m.libelle AS mission_libelle, m.majoration_mission,
                   SEC_TO_TIME(
                       (UNIX_TIMESTAMP(CONCAT(cdvg.date_vol, ' ', cdvg.heure_arrivee)) - UNIX_TIMESTAMP(CONCAT(cdvg.date_vol, ' ', cdvg.heure_depart))
                        + IF(cdvg.heure_arrivee < cdvg.heure_depart, 86400, 0))
                   ) AS block_time";

$results = [];
$totalMatches = 0;
$vols = [];
$selectedById = [];
try {
    if ($searched) {
        $stmtCount = $pdo->prepare("SELECT COUNT(*) $from $where");
        $stmtCount->execute($params);
        $totalMatches = (int)$stmtCount->fetchColumn();

        $stmt = $pdo->prepare("$selectCols $from $where ORDER BY " . $sortOptions[$f['sort']] . " LIMIT " . COMPARE_MAX_RESULTS);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
    }

    // Vols sélectionnés : indépendants des filtres et des recherches précédentes
    if ($selectedIds) {
        $in = implode(',', array_fill(0, count($selectedIds), '?'));
        $stmt = $pdo->prepare("$selectCols $from WHERE cdvg.annule = 0 AND cdvg.id IN ($in)");
        $stmt->execute($selectedIds);
        foreach ($stmt->fetchAll() as $row) {
            $selectedById[(int)$row['id']] = $row;
        }
        $selectedIds = array_values(array_filter($selectedIds, fn($id) => isset($selectedById[$id])));
        if ($compareRequested && count($selectedIds) >= 2) {
            foreach ($selectedIds as $id) {
                $vols[] = $selectedById[$id];
            }
        }
    }} catch (PDOException $e) {
    echo '<p>' . t('vol_detail_error_sql') . ' ' . htmlspecialchars($e->getMessage()) . '</p>';
    exit;
}

// Recalcul du détail avec la même formule que l'import
$cols = [];
foreach ($vols as $v) {
    $temps = !empty($v['temps_vol']) && $v['temps_vol'] !== '00:00:00' ? $v['temps_vol'] : ($v['block_time'] ?? '00:00:00');
    $distance = (float)ComputeFlightDistance($v['depart'], $v['destination']);
    $majoration = $v['majoration_mission'] !== null ? (float)$v['majoration_mission'] : 1.0;
    $carburant = max(0.0, (float)$v['fuel_depart'] - (float)$v['fuel_arrivee']);
    $detail = calculerDetailRevenuVol(
        (float)$v['payload'], $temps, $distance, $majoration, $carburant,
        $v['note_du_vol'] === null || $v['note_du_vol'] === '' ? null : (int)$v['note_du_vol'],
        (float)$v['cout_horaire'], $v['immat']
    );
    $cols[] = ['vol' => $v, 'temps' => $temps, 'd' => $detail];
}

function cmp_num($v, $dec = 2) { return number_format((float)$v, $dec, ',', ' '); }
function cmp_eur($v) { return cmp_num($v, 2) . ' €'; }
function compare_tip($text) {
    return ' <span class="compare-tip" tabindex="0" data-tip="' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '">ⓘ</span>';
}
$rowTips = [
    'compare_row_formula'        => 'compare_tip_formula',
    'compare_row_fret_price'     => 'compare_tip_fret_price',
    'compare_row_mission_coef'   => 'compare_tip_mission_coef',
    'compare_row_gross'          => 'compare_tip_gross',
    'compare_row_fuel_cost'      => 'compare_tip_fuel_cost',
    'compare_row_hourly'         => 'compare_tip_hourly',
    'compare_row_note_coef'      => 'compare_tip_note_coef',
    'compare_row_aircraft_cost'  => 'compare_tip_aircraft_cost',
    'compare_row_net'            => 'compare_tip_net',
];

// Définition des lignes : [clé de traduction, callback(colonne) => texte, option "meilleur" ('max'|'min'|null)]
$sections = [
    'compare_sec_flight' => [
        ['compare_row_id',       fn($c) => '#' . $c['vol']['id'], null],
        ['compare_row_date',     fn($c) => date('d-m-Y', strtotime($c['vol']['date_vol'])), null],
        ['compare_row_pilot',    fn($c) => $c['vol']['callsign'] ?? '', null],
        ['compare_row_aircraft', fn($c) => trim(($c['vol']['immat'] ?? '') . ' ' . ($c['vol']['fleet_type_label'] ?? '')), null],
        ['compare_row_route',    fn($c) => $c['vol']['depart'] . ' → ' . $c['vol']['destination'], null],
        ['compare_row_mission',  fn($c) => $c['vol']['mission_libelle'] ?? '', null],
        ['compare_row_note',     fn($c) => (string)$c['vol']['note_du_vol'], null],
    ],
    'compare_sec_measures' => [
        ['compare_row_duration', fn($c) => substr($c['temps'], 0, 8), null],
        ['compare_row_distance', fn($c) => $c['d'] ? cmp_num($c['d']['distance'], 0) . ' NM' : '-', null],
        ['compare_row_payload',  fn($c) => cmp_num($c['vol']['payload'], 0) . ' kg', null],
        ['compare_row_fuel',     fn($c) => $c['d'] ? cmp_num($c['d']['carburant'], 0) . ' L' : '-', null],
    ],
    'compare_sec_revenue' => [
        ['compare_row_formula',   fn($c) => !$c['d'] ? '-' : ($c['d']['crash'] ? t('compare_formula_crash') : ($c['d']['vol_court'] ? t('compare_formula_short') : t('compare_formula_long'))), null],
        ['compare_row_fret_price', fn($c) => !$c['d'] ? '-' : cmp_num($c['d']['prix_kg_applique'], 2) . ' €/kg' . ($c['d']['vol_court'] ? ' (' . cmp_num($c['d']['prix_kg_base'], 2) . ' × 1,2)' : ''), null],
        ['compare_row_mission_coef', fn($c) => !$c['d'] ? '-' : '× ' . cmp_num($c['d']['majoration_mission'], 2), null],
        ['compare_row_gross',     fn($c) => !$c['d'] ? '-' : cmp_eur($c['d']['revenu_brut']), 'max'],
        ['compare_row_fuel_cost', fn($c) => !$c['d'] ? '-' : '− ' . cmp_eur($c['d']['cout_carburant']) . ' (' . cmp_num($c['d']['prix_litre_essence'], 2) . ' €/L)', null],
        ['compare_row_hourly',    fn($c) => !$c['d'] ? '-' : cmp_num($c['d']['cout_horaire'], 2) . ' €/h', null],
        ['compare_row_note_coef', fn($c) => !$c['d'] ? '-' : '× ' . cmp_num($c['d']['coef_cout_appareil'], 2), null],
        ['compare_row_aircraft_cost', fn($c) => !$c['d'] ? '-' : '− ' . cmp_eur($c['d']['cout_appareil']), null],
        ['compare_row_net',       fn($c) => !$c['d'] ? '-' : cmp_eur($c['d']['revenu_net']), 'max'],
        ['compare_row_net_hour',  fn($c) => !$c['d'] || $c['d']['heures'] <= 0 ? '-' : cmp_eur($c['d']['revenu_net'] / $c['d']['heures']) . '/h', 'max'],
        ['compare_row_net_nm',    fn($c) => !$c['d'] || $c['d']['distance'] <= 0 ? '-' : cmp_eur($c['d']['revenu_net'] / $c['d']['distance']) . '/NM', 'max'],
        ['compare_row_stored',    fn($c) => cmp_eur($c['vol']['cout_vol'] ?? 0), null],
    ],
];
// Valeurs numériques servant à désigner le meilleur vol sur une ligne
$bestValue = [
    'compare_row_gross'    => fn($c) => $c['d']['revenu_brut'] ?? null,
    'compare_row_net'      => fn($c) => $c['d']['revenu_net'] ?? null,
    'compare_row_net_hour' => fn($c) => ($c['d'] && $c['d']['heures'] > 0) ? $c['d']['revenu_net'] / $c['d']['heures'] : null,
    'compare_row_net_nm'   => fn($c) => ($c['d'] && $c['d']['distance'] > 0) ? $c['d']['revenu_net'] / $c['d']['distance'] : null,
];
?>
<main>
    <h2><?= t('compare_title') ?></h2>
    <p class="compare-intro"><?= t('compare_intro', ['max' => COMPARE_MAX_FLIGHTS]) ?></p>

    <form method="get" action="" class="filters-form">
    <div class="filters-grid">
        <div class="filter-group">
            <label for="callsign"><?= t('compare_f_pilot') ?></label>
            <select id="callsign" name="callsign" class="fleet-filter-select">
                <option value=""><?= t('compare_f_all') ?></option>
                <?php foreach ($pilotsList as $cs): ?>
                    <option value="<?= htmlspecialchars($cs) ?>" <?= $f['callsign'] === $cs ? 'selected' : '' ?>><?= htmlspecialchars($cs) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group filter-narrow">
            <label for="immat"><?= t('compare_f_immat') ?></label>
            <input type="text" id="immat" name="immat" class="fleet-filter-input" value="<?= htmlspecialchars($f['immat']) ?>">
        </div>
        <div class="filter-group">
            <label for="fleetType"><?= t('compare_f_fleet_type') ?></label>
            <select id="fleetType" name="fleetType" class="fleet-filter-select">
                <option value=""><?= t('compare_f_all') ?></option>
                <?php foreach ($fleetTypeList as $ftl): ?>
                    <option value="<?= htmlspecialchars($ftl) ?>" <?= $f['fleetType'] === $ftl ? 'selected' : '' ?>><?= htmlspecialchars($ftl) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label for="mission"><?= t('compare_f_mission') ?></label>
            <select id="mission" name="mission" class="fleet-filter-select">
                <option value=""><?= t('compare_f_all') ?></option>
                <?php foreach ($missionsList as $ml): ?>
                    <option value="<?= htmlspecialchars($ml) ?>" <?= $f['mission'] === $ml ? 'selected' : '' ?>><?= htmlspecialchars($ml) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group filter-narrow">
            <label for="depart"><?= t('compare_f_depart') ?></label>
            <input type="text" id="depart" name="depart" class="fleet-filter-input" maxlength="4" value="<?= htmlspecialchars($f['depart']) ?>">
        </div>
        <div class="filter-group filter-narrow">
            <label for="dest"><?= t('compare_f_dest') ?></label>
            <input type="text" id="dest" name="dest" class="fleet-filter-input" maxlength="4" value="<?= htmlspecialchars($f['dest']) ?>">
        </div>
        <div class="filter-group">
            <label for="date_from"><?= t('compare_f_date_from') ?></label>
            <input type="date" id="date_from" name="date_from" class="fleet-filter-input" value="<?= htmlspecialchars($f['date_from']) ?>">
        </div>
        <div class="filter-group">
            <label for="date_to"><?= t('compare_f_date_to') ?></label>
            <input type="date" id="date_to" name="date_to" class="fleet-filter-input" value="<?= htmlspecialchars($f['date_to']) ?>">
        </div>
        <div class="filter-group filter-narrow">
            <label for="note_min"><?= t('compare_f_note_min') ?></label>
            <input type="number" id="note_min" name="note_min" class="fleet-filter-input" min="1" max="10" value="<?= htmlspecialchars($f['note_min']) ?>">
        </div>
        <div class="filter-group filter-narrow">
            <label for="note_max"><?= t('compare_f_note_max') ?></label>
            <input type="number" id="note_max" name="note_max" class="fleet-filter-input" min="1" max="10" value="<?= htmlspecialchars($f['note_max']) ?>">
        </div>
        <div class="filter-group filter-narrow">
            <label for="payload_min"><?= t('compare_f_payload_min') ?></label>
            <input type="number" id="payload_min" name="payload_min" class="fleet-filter-input" min="0" value="<?= htmlspecialchars($f['payload_min']) ?>">
        </div>
        <div class="filter-group filter-narrow">
            <label for="payload_max"><?= t('compare_f_payload_max') ?></label>
            <input type="number" id="payload_max" name="payload_max" class="fleet-filter-input" min="0" value="<?= htmlspecialchars($f['payload_max']) ?>">
        </div>
        <div class="filter-group">
            <label for="sort"><?= t('compare_f_sort') ?></label>
            <select id="sort" name="sort" class="fleet-filter-select">
                <option value="recent" <?= $f['sort'] === 'recent' ? 'selected' : '' ?>><?= t('compare_sort_recent') ?></option>
                <option value="best" <?= $f['sort'] === 'best' ? 'selected' : '' ?>><?= t('compare_sort_best') ?></option>
                <option value="worst" <?= $f['sort'] === 'worst' ? 'selected' : '' ?>><?= t('compare_sort_worst') ?></option>
            </select>
        </div>
        <div class="filter-group filter-buttons">
            <label>&nbsp;</label>
            <div>
                <button type="submit" name="search" value="1" class="btn"><?= t('compare_btn_search') ?></button>
                <button type="button" class="btn btn-reset" onclick="window.location.href='<?= basename($_SERVER['PHP_SELF']) ?>';"><?= t('compare_btn_reset') ?></button>
            </div>
        </div>

    </div>

    <div class="spacer-xl"></div>

    <div class="compare-output">
    <?php if ($cols): ?>
        <h3><?= t('compare_result_title') ?></h3>
        <div class="compare-scroll">
            <table class="table-skywings compare-table">
                <thead class="table-skywings">
                    <tr class="table-skywings">
                        <th class="compare-label"></th>
                        <?php foreach ($cols as $i => $c): ?>
                            <th><?= t('compare_col_title', ['n' => $i + 1]) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="table-skywings">
                <?php foreach ($sections as $sectionKey => $rows): ?>
                    <?php if ($sectionKey === 'compare_sec_revenue'): ?>
                    <tr class="compare-section"><th colspan="<?= count($cols) + 1 ?>"><?= t($sectionKey) ?></th></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as [$labelKey, $render, $best]):
                        $bestIdx = null;
                        if ($best === 'max' && isset($bestValue[$labelKey]) && count($cols) > 1) {
                            $vals = array_map($bestValue[$labelKey], $cols);
                            $valid = array_filter($vals, fn($x) => $x !== null);
                            if ($valid && count(array_unique(array_map(fn($x) => round($x, 2), $valid))) > 1) {
                                $bestIdx = array_search(max($valid), $vals);
                            }
                        }
                        $rowClass = in_array($labelKey, ['compare_row_net', 'compare_row_stored'], true) ? ' class="compare-total"' : '';
                    ?>
                    <tr<?= $rowClass ?>>
                        <td class="compare-label"><?= t($labelKey) ?><?= isset($rowTips[$labelKey]) ? compare_tip(t($rowTips[$labelKey])) : '' ?></td>
                        <?php foreach ($cols as $i => $c):
                            $cls = [];
                            if ($bestIdx === $i) $cls[] = 'compare-best';
                            if ($labelKey === 'compare_row_net' && $c['d'] && (float)$c['d']['revenu_net'] < 0) $cls[] = 'recette-negative';
                            $text = htmlspecialchars((string)$render($c));
                            if ($labelKey === 'compare_row_net' && $i > 0 && $c['d'] && $cols[0]['d']) {
                                $delta = $c['d']['revenu_net'] - $cols[0]['d']['revenu_net'];
                                $text .= '<br><span class="compare-sub">' . ($delta >= 0 ? '+' : '−') . cmp_eur(abs($delta)) . ' ' . t('compare_vs_first') . '</span>';
                            }
                            if ($labelKey === 'compare_row_stored' && $c['d'] && abs($c['d']['revenu_net'] - (float)$c['vol']['cout_vol']) > 0.01) {
                                $cls[] = 'compare-mismatch';
                                $text .= '<br><span class="compare-sub">' . t('compare_mismatch') . '</span>';
                            }
                        ?>
                        <td<?= $cls ? ' class="' . implode(' ', $cls) . '"' : '' ?>><?= $text ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="compare-footnote"><?= t('compare_footnote') ?></p>
        <?php foreach ($selectedIds as $sid): ?>
            <input type="hidden" name="cmp[]" value="<?= (int)$sid ?>">
        <?php endforeach; ?>
    <?php else: ?>
    <?php
        $resultIds = array_map(fn($r) => (int)$r['id'], $results);
        $trayIds = array_values(array_filter($selectedIds, fn($id) => !in_array($id, $resultIds, true)));
        $listHead = function () { ?>
            <thead class="table-skywings">
                <tr class="table-skywings">
                    <th></th>
                    <th><?= t('compare_row_id') ?></th>
                    <th><?= t('compare_row_date') ?></th>
                    <th><?= t('compare_row_pilot') ?></th>
                    <th><?= t('compare_row_aircraft') ?></th>
                    <th><?= t('compare_row_route') ?></th>
                    <th><?= t('compare_row_mission') ?></th>
                    <th><?= t('compare_row_note') ?></th>
                    <th><?= t('compare_row_payload') ?></th>
                    <th><?= t('compare_row_stored') ?></th>
                </tr>
            </thead>
        <?php };
        $listRow = function ($r, $checked) { $rid = (int)$r['id']; $rec = (float)($r['cout_vol'] ?? 0); ?>
            <tr>
                <td><input type="checkbox" class="compare-check" name="cmp[]" value="<?= $rid ?>" aria-label="<?= t('compare_select_flight') ?> #<?= $rid ?>" <?= $checked ? 'checked' : '' ?>></td>
                <td>#<?= $rid ?></td>
                <td><?= htmlspecialchars(date('d-m-Y', strtotime($r['date_vol']))) ?></td>
                <td><?= htmlspecialchars($r['callsign'] ?? '') ?></td>
                <td><?= htmlspecialchars(trim(($r['immat'] ?? '') . ' ' . ($r['fleet_type_label'] ?? ''))) ?></td>
                <td><?= htmlspecialchars($r['depart'] . ' → ' . $r['destination']) ?></td>
                <td><?= htmlspecialchars($r['mission_libelle'] ?? '') ?></td>
                <td><?= htmlspecialchars((string)$r['note_du_vol']) ?></td>
                <td><?= cmp_num($r['payload'], 0) ?> kg</td>
                <td<?= $rec < 0 ? ' class="recette-negative"' : '' ?>><?= cmp_eur($rec) ?></td>
            </tr>
        <?php };
    ?>

    <?php if (!$searched && !$selectedIds): ?>
        <p class="compare-intro"><?= t('compare_start_hint') ?></p>
    <?php else: ?>
        <div class="compare-toolbar">
            <span class="filter-info">
                <?php if ($searched): ?>
                    <?= $totalMatches > count($results)
                        ? t('compare_limited', ['shown' => count($results), 'total' => $totalMatches])
                        : t('compare_count', ['count' => count($results)]) ?>
                <?php endif; ?>
            </span>
            <span class="compare-toolbar-actions">
                <span id="compare-counter" data-max="<?= COMPARE_MAX_FLIGHTS ?>" data-label="<?= htmlspecialchars(t('compare_selected')) ?>"></span>
                <button type="submit" name="compare" value="1" id="compare-submit" class="btn" disabled><?= t('compare_btn_compare') ?></button>
            </span>
        </div>
        <p class="compare-hint" id="compare-hint"><?= t('compare_select_hint', ['max' => COMPARE_MAX_FLIGHTS]) ?></p>

        <?php if ($searched): ?>
            <h3><?= t('compare_list_title') ?></h3>
            <?php if (!$results): ?>
                <p><?= t('compare_no_results') ?></p>
            <?php else: ?>
                <div class="compare-scroll">
                    <table class="table-skywings compare-list">
                        <?php $listHead(); ?>
                        <tbody class="table-skywings">
                        <?php foreach ($results as $r) { $listRow($r, in_array((int)$r['id'], $selectedIds, true)); } ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($trayIds): ?>
            <div class="spacer-xl"></div>
            <h3><?= t('compare_tray_title') ?></h3>
            <p class="compare-hint compare-hint-active"><?= t('compare_tray_hint') ?></p>
            <div class="compare-scroll">
                <table class="table-skywings compare-list">
                    <?php $listHead(); ?>
                    <tbody class="table-skywings">
                    <?php foreach ($trayIds as $tid) { $listRow($selectedById[$tid], true); } ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <script>
        (function () {
            var checks = Array.prototype.slice.call(document.querySelectorAll('.compare-check'));
            var btn = document.getElementById('compare-submit');
            var counter = document.getElementById('compare-counter');
            var hint = document.getElementById('compare-hint');
            var max = parseInt(counter.getAttribute('data-max'), 10);
            function refresh() {
                var n = checks.filter(function (c) { return c.checked; }).length;
                counter.textContent = n + ' ' + counter.getAttribute('data-label');
                btn.disabled = n < 2;
                hint.classList.toggle('compare-hint-active', n < 2);
                checks.forEach(function (c) { c.disabled = !c.checked && n >= max; });
            }
            checks.forEach(function (c) { c.addEventListener('change', refresh); });
            refresh();
        })();
        </script>
    <?php endif; ?>
    <?php endif; ?>

    <?php if ($searched || $selectedIds): ?>
        <div class="compare-reset-bottom">
            <button type="button" class="btn btn-reset" onclick="window.location.href='<?= basename($_SERVER['PHP_SELF']) ?>';"><?= t('compare_btn_reset') ?></button>
        </div>
    <?php endif; ?>
    </div>
    </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>