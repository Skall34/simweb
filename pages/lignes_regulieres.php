<?php
require_once __DIR__ . '/../includes/require_login.php';
require_once __DIR__ . '/../includes/db_connect.php';

$userId = $_SESSION['user']['id'];

// Récupérer les filtres (GET) — on autorise des recherches partielles (prefix)
$filter_dep = isset($_GET['icao_dep']) ? strtoupper(trim($_GET['icao_dep'])) : '';
$filter_arr = isset($_GET['icao_arr']) ? strtoupper(trim($_GET['icao_arr'])) : '';
// filtre par type de ligne (id) — null = tous
$filter_type = (isset($_GET['type_ligne']) && $_GET['type_ligne'] !== '') ? (int)$_GET['type_ligne'] : null;

// récupérer la liste des types pour le select
try {
    $stmtTypes = $pdo->query("SELECT id, label FROM TYPE_LIGNE ORDER BY label ASC");
    $typeLignes = $stmtTypes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $typeLignes = [];
}

// Construire la requête de façon paramétrée (pour limiter l'impact sur l'existant)
$sql = "SELECT lr.*, tl.label AS type_label
         FROM LIGNES_REGULIERES lr
         LEFT JOIN TYPE_LIGNE tl ON lr.type_ligne = tl.id";
$conds = [];
$params = [];
if ($filter_dep !== '') {
    $conds[] = 'lr.icao_dep LIKE ?';
    $params[] = $filter_dep . '%';
}
if ($filter_arr !== '') {
    $conds[] = 'lr.icao_arr LIKE ?';
    $params[] = $filter_arr . '%';
}
if ($filter_type !== null) {
    $conds[] = 'lr.type_ligne = ?';
    $params[] = $filter_type;
}
if (count($conds) > 0) {
    $sql .= ' WHERE ' . implode(' AND ', $conds);
}
$sql .= ' ORDER BY lr.icao_dep ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les réservations en cours (reserved, in_flight)
try {
    $stmtRes = $pdo->prepare(
        "SELECT r.id AS res_id, r.ligne_id, r.immat, r.pilote_id, r.statut, r.date_reservation,
                p.callsign AS pilote_callsign, lr.icao_dep, lr.icao_arr
         FROM RESERVATIONS r
         LEFT JOIN PILOTES p ON r.pilote_id = p.id
         LEFT JOIN LIGNES_REGULIERES lr ON r.ligne_id = lr.id
         WHERE r.statut IN ('reserved','in_flight')
         ORDER BY r.date_reservation DESC"
    );
    $stmtRes->execute();
    $reservations = $stmtRes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $reservations = [];
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>
<main>
    <?php $lines_count = count($lines); ?>
    <h2><?= str_replace('{count}', $lines_count, t('lignes_title')) ?></h2>
    <p><?= t('lignes_subtitle') ?></p>
    <br>
    <?php
    if (!empty($_SESSION['flash_reserved'])) {
        echo "<div class='flash-success'>" . t('lignes_flash_success') . "</div>";
        unset($_SESSION['flash_reserved']);
    } elseif (isset($_GET['reserved'])) {
        $res = $_GET['reserved'];
        if ($res === '1') {
            echo "<div class='flash-success'>" . t('lignes_flash_success') . "</div>";
        } elseif ($res === '0') {
            echo "<div class='flash-error'>" . t('lignes_flash_error') . "</div>";
        } else {
            echo "<div class='flash-success'>" . htmlspecialchars($res) . "</div>";
        }
    }
    ?>
    <form method="get" class="filters-form">
        <label><?= t('lignes_filter_dep') ?>:
            <input type="text" name="icao_dep" value="<?= htmlspecialchars($filter_dep) ?>" maxlength="5" class="fleet-filter-input input-160" placeholder="<?= t('lignes_filter_dep_placeholder') ?>" />
        </label>
        <label><?= t('lignes_filter_arr') ?>:
            <input type="text" name="icao_arr" value="<?= htmlspecialchars($filter_arr) ?>" maxlength="5" class="fleet-filter-input input-160" placeholder="<?= t('lignes_filter_arr_placeholder') ?>" />
        </label>
        <br>
        <label><?= t('lignes_filter_type') ?>:
            <select name="type_ligne" class="fleet-filter-select input-160">
                <option value=""><?= t('lignes_filter_type_all') ?></option>
                <?php foreach ($typeLignes as $t): ?>
                    <option value="<?= (int)$t['id'] ?>" <?= ($filter_type !== null && $filter_type === (int)$t['id']) ? 'selected' : '' ?>><?= htmlspecialchars($t['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    <button type="submit" class="btn"><?= t('lignes_filter_button') ?></button>
    <button type="button" class="btn btn-reset" id="resetBtn"><?= t('lignes_reset_button') ?></button>
    </form>
    <script>
        document.getElementById('resetBtn').addEventListener('click', function () {
            window.location.href = 'lignes_regulieres.php';
        });
    </script>
       <div style="background:#fff3cd;border:1px solid #f0ad4e;color:#856404;padding:12px;border-radius:6px;margin-bottom:12px;"> 
            <strong>Attention,</strong> pour pouvoir utiliser les lignes régulières, il faut l'Acars (SimAddon) version 4.0.4 minimum.
        </div>
    <div class="content-columns">
    <div class="narrow-table-wrapper">
            <div class="panel">
                <h3><?= t('lignes_table_title') ?></h3>
                <table class="table-skywings">
        <thead>
            <tr>
                <th><?= t('lignes_table_dep') ?></th>
                <th><?= t('lignes_table_arr') ?></th>
                <th><?= t('lignes_table_type') ?></th>
                <th><?= t('lignes_table_distance') ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($lines) === 0): ?>
                <tr><td colspan="5"><?= t('lignes_no_results') ?></td></tr>
            <?php else: ?>
                <?php foreach ($lines as $line): ?>
                    <tr>
                        <td><?= htmlspecialchars($line['icao_dep']) ?></td>
                        <td><?= htmlspecialchars($line['icao_arr']) ?></td>
                        <td><?= htmlspecialchars($line['type_label'] ?? '') ?></td>
                        <td><?= is_null($line['distance']) ? '' : htmlspecialchars((int)$line['distance']) ?></td>
                        <td>
                            <a href="reserver_ligne.php?ligne_id=<?= urlencode($line['id']) ?>"><?= t('lignes_reserver_link') ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
            </div>
        </div>

        <div class="vertical-sep"></div>

        <aside class="right-aside">
            <div class="panel">
                <h3><?= t('lignes_reservations_title') ?></h3>
                <?php if (!empty($reservations)): ?>
                    <div class="reservations-scroll">
                        <table class="table-skywings compact">
                            <thead>
                                <tr>
                                    <th><?= t('lignes_reservations_pilote') ?></th>
                                    <th><?= t('lignes_reservations_ligne') ?></th>
                                    <th><?= t('lignes_reservations_appareil') ?></th>
                                    <th><?= t('lignes_reservations_statut') ?></th>
                                    <th><?= t('lignes_reservations_date') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reservations as $r): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($r['pilote_callsign'] ?: 'N/A') ?></td>
                                        <td><?= htmlspecialchars(($r['icao_dep'] ?? '') . '→' . ($r['icao_arr'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars($r['immat'] ?? '') ?></td>
                                        <td><?php
                                            $st = $r['statut'] ?? '';
                                            if ($st === 'in_flight') {
                                                echo t('lignes_statut_in_flight');
                                            } elseif ($st === 'reserved') {
                                                echo t('lignes_statut_reserved');
                                            } else {
                                                echo htmlspecialchars($st);
                                            }
                                        ?></td>
                                        <td><?php
                                            try {
                                                $dt = new DateTime($r['date_reservation']);
                                                echo htmlspecialchars($dt->format('d-m-Y H:i'));
                                            } catch (Exception $e) {
                                                echo htmlspecialchars($r['date_reservation']);
                                            }
                                        ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="empty-msg"><?= t('lignes_reservations_empty') ?></p>
                <?php endif; ?>
            </div>

            <div class="panel">
                <h3><?= t('lignes_map_title') ?></h3>
                <div class="map-embed">
                    <iframe class="map-iframe" src="https://www.google.com/maps/d/u/0/embed?mid=1fYs3mM8W3nRfVHl78xp2w8st6hcK22w" allowfullscreen="allowfullscreen"></iframe>
                </div>
                <p class="map-note"><?= t('lignes_map_note') ?></p>
            </div>
        </aside>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
