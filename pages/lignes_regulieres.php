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

// Prepare all airports with their coordinates for the map
$allAirports = [];
try {
    $stmt = $pdo->query("SELECT ident, latitude_deg, longitude_deg FROM AEROPORTS WHERE ident IN (SELECT DISTINCT icao_dep FROM LIGNES_REGULIERES UNION SELECT DISTINCT icao_arr FROM LIGNES_REGULIERES)");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $allAirports[$row['ident']] = [
            'lat' => (float)$row['latitude_deg'],
            'lon' => (float)$row['longitude_deg']
        ];
    }
} catch (Exception $e) {
    // Silently fail if airports data is unavailable
}

// Historique : dernières lignes régulières effectuées (completed)
$historyCompleted = [];
try {
    $stmtHist = $pdo->query(
        "SELECT r.date_fin, p.callsign AS pilote_callsign, lr.icao_dep, lr.icao_arr, r.immat
         FROM RESERVATIONS r
         LEFT JOIN PILOTES p ON r.pilote_id = p.id
         LEFT JOIN LIGNES_REGULIERES lr ON r.ligne_id = lr.id
         WHERE r.statut = 'completed'
         ORDER BY r.date_fin DESC
         LIMIT 8"
    );
    $historyCompleted = $stmtHist->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $historyCompleted = [];
}

// Historique : dernières lignes régulières créées
$historyCreated = [];
try {
    $stmtCreated = $pdo->query(
        "SELECT lr.icao_dep, lr.icao_arr, lr.distance, lr.created_at, tl.label AS type_label
         FROM LIGNES_REGULIERES lr
         LEFT JOIN TYPE_LIGNE tl ON lr.type_ligne = tl.id
         ORDER BY lr.created_at DESC
         LIMIT 8"
    );
    $historyCreated = $stmtCreated->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $historyCreated = [];
}

// Matrice des lignes intérieures : pour chaque paire (dep, arr) du type 'intérieur',
// on récupère la date du dernier vol complété.
$matrixData = [];
$matrixDeps = [];
$matrixArrs = [];
try {
    $stmtMatrix = $pdo->query(
        "SELECT lr.icao_dep, lr.icao_arr,
                MAX(r.date_fin) AS last_flight
         FROM LIGNES_REGULIERES lr
         LEFT JOIN RESERVATIONS r ON r.ligne_id = lr.id AND r.statut = 'completed'
         WHERE lr.type_ligne = 2
         GROUP BY lr.icao_dep, lr.icao_arr
         ORDER BY lr.icao_dep ASC, lr.icao_arr ASC"
    );
    $matrixRows = $stmtMatrix->fetchAll(PDO::FETCH_ASSOC);
    $depSet = [];
    $arrSet = [];
    foreach ($matrixRows as $row) {
        $dep = $row['icao_dep'];
        $arr = $row['icao_arr'];
        $depSet[$dep] = true;
        $arrSet[$arr] = true;
        $matrixData[$dep][$arr] = $row['last_flight'];
    }
    $matrixDeps = array_keys($depSet);
    $matrixArrs = array_keys($arrSet);
    sort($matrixDeps);
    sort($matrixArrs);
} catch (PDOException $e) {
    $matrixDeps = [];
    $matrixArrs = [];
    $matrixData = [];
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
                <div id="map-container" style="height: 500px; background: #e0e0e0; border-radius: 6px; overflow: hidden;">
                    <div id="map" style="height: 100%;"></div>
                </div>
            </div>

            <!-- Historique : dernières lignes effectuées -->
            <div class="panel" style="margin-top:12px;">
                <h3><?= t('lignes_history_completed_title') ?></h3>
                <?php if (!empty($historyCompleted)): ?>
                        <table class="table-skywings compact" style="font-size:0.85em;">
                            <thead>
                                <tr>
                                    <th><?= t('lignes_history_date') ?></th>
                                    <th><?= t('lignes_reservations_pilote') ?></th>
                                    <th><?= t('lignes_reservations_ligne') ?></th>
                                    <th><?= t('lignes_reservations_appareil') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historyCompleted as $hc): ?>
                                <tr>
                                    <td><?php
                                        try {
                                            $dt = new DateTime($hc['date_fin']);
                                            echo htmlspecialchars($dt->format('d/m/Y'));
                                        } catch (Exception $e) {
                                            echo htmlspecialchars($hc['date_fin'] ?? '');
                                        }
                                    ?></td>
                                    <td><?= htmlspecialchars($hc['pilote_callsign'] ?: 'N/A') ?></td>
                                    <td><?= htmlspecialchars(($hc['icao_dep'] ?? '') . ' → ' . ($hc['icao_arr'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars($hc['immat'] ?? '') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                            <p class="empty-msg"><?= t('lignes_history_completed_empty') ?></p>
                        <?php endif; ?>
            </div>

            <!-- Historique : dernières lignes créées -->
            <div class="panel" style="margin-top:12px;">
                <h3><?= t('lignes_history_created_title') ?></h3>
                <?php if (!empty($historyCreated)): ?>
                        <table class="table-skywings compact" style="font-size:0.85em;">
                            <thead>
                                <tr>
                                    <th><?= t('lignes_history_date') ?></th>
                                    <th><?= t('lignes_reservations_ligne') ?></th>
                                    <th><?= t('lignes_table_type') ?></th>
                                    <th><?= t('lignes_table_distance') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historyCreated as $hcr): ?>
                                <tr>
                                    <td><?php
                                        try {
                                            $dt = new DateTime($hcr['created_at']);
                                            echo htmlspecialchars($dt->format('d/m/Y'));
                                        } catch (Exception $e) {
                                            echo htmlspecialchars($hcr['created_at'] ?? '');
                                        }
                                    ?></td>
                                    <td><?= htmlspecialchars(($hcr['icao_dep'] ?? '') . ' → ' . ($hcr['icao_arr'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars($hcr['type_label'] ?? '') ?></td>
                                    <td><?= is_null($hcr['distance']) ? '' : htmlspecialchars((int)$hcr['distance']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                            <p class="empty-msg"><?= t('lignes_history_created_empty') ?></p>
                        <?php endif; ?>
            </div>
                    <!-- Matrice des lignes régulières intérieures -->
        <!--<section style="margin-top:28px;">-->
            <div class="panel">
                <h3>Matrice des lignes régulières intérieures</h3>
                <p style="font-size:0.85em;color:#666;margin-bottom:10px;">Lignes en lignes&nbsp;=&nbsp;départs, colonnes&nbsp;=&nbsp;arrivées. Valeur&nbsp;: nombre de jours depuis le dernier vol sur la liaison.</p>
                <?php if (empty($matrixDeps)): ?>
                    <p class="empty-msg">Aucune ligne intérieure trouvée.</p>
                <?php else: ?>
                    <?php $matrixNow = new DateTime(); ?>
                    <div style="overflow-x:auto;">
                    <table style="border-collapse:collapse; font-size:0.82em; text-align:center; white-space:nowrap;">
                        <thead>
                            <tr>
                                <th style="background:#f0f0f0; border:1px solid #ccc; padding:5px 8px; font-weight:bold;">DEP \ ARR</th>
                                <?php foreach ($matrixArrs as $matArr): ?>
                                    <th style="background:#f0f0f0; border:1px solid #ccc; padding:5px 8px; font-weight:bold;"><?= htmlspecialchars($matArr) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($matrixDeps as $matDep): ?>
                            <tr>
                                <th style="background:#f0f0f0; border:1px solid #ccc; padding:5px 8px; font-weight:bold; text-align:left;"><?= htmlspecialchars($matDep) ?></th>
                                <?php foreach ($matrixArrs as $matArr): ?>
                                <td style="border:1px solid #ccc; padding:4px 7px;">
                                    <?php
                                    if ($matDep === $matArr) {
                                        echo '<span style="color:#ccc;font-size:1.1em;">&#215;</span>';
                                    } elseif (!isset($matrixData[$matDep][$matArr])) {
                                        echo '<span style="color:#ccc;">&#8212;</span>';
                                    } else {
                                        $lastFlight = $matrixData[$matDep][$matArr];
                                        if ($lastFlight === null) {
                                            echo '<span style="background:#e9ecef;color:#6c757d;padding:2px 6px;border-radius:4px;">Jamais</span>';
                                        } else {
                                            try {
                                                $dtLast = new DateTime($lastFlight);
                                                $diffDays = (int)$matrixNow->diff($dtLast)->days;
                                                if ($diffDays === 0) {
                                                    $badge = 'Auj.';
                                                    $bg = '#28a745';
                                                } elseif ($diffDays <= 180) {
                                                    $badge = $diffDays . 'j';
                                                    $bg = '#28a745';
                                                } elseif ($diffDays <= 365) {
                                                    $badge = $diffDays . 'j';
                                                    $bg = '#fd7e14';
                                                } else {
                                                    $badge = $diffDays . 'j';
                                                    $bg = '#dc3545';
                                                }
                                                echo '<span style="background:' . $bg . ';color:#fff;padding:2px 6px;border-radius:4px;font-weight:600;">' . $badge . '</span>';
                                            } catch (Exception $e) {
                                                echo '?';
                                            }
                                        }
                                    }
                                    ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                    <div style="margin-top:10px;font-size:0.8em;color:#555;display:flex;gap:14px;flex-wrap:wrap;align-items:center;">
                        <span><span style="display:inline-block;background:#28a745;color:#fff;padding:1px 7px;border-radius:4px;">0–6m</span>&nbsp;Récent</span>
                        <span><span style="display:inline-block;background:#fd7e14;color:#fff;padding:1px 7px;border-radius:4px;">6–12m</span>&nbsp;Modéré</span>
                        <span><span style="display:inline-block;background:#dc3545;color:#fff;padding:1px 7px;border-radius:4px;">&gt;12m</span>&nbsp;Ancien</span>
                        <span><span style="display:inline-block;background:#e9ecef;color:#6c757d;padding:1px 7px;border-radius:4px;">Jamais</span>&nbsp;Jamais volé</span>
                        <span style="color:#aaa;">&#8212;&nbsp;Liaison inexistante</span>
                    </div>
                <?php endif; ?>
            </div>
    <!--</section>-->

        </aside>
    </div>

</main>

<style>
    svg .animated-route {
        stroke-dasharray: 12, 8;
    }
</style>

<script>
    // All lines and airports data for the interactive map
    const allLines = <?php echo json_encode($lines); ?>;
    const allAirports = <?php echo json_encode($allAirports); ?>;
    let currentFilterIcao = '';
    let idleRefreshTimer = null;

    // Initialize Leaflet map centered on world
    const map = L.map('map').setView([20, 0], 3);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 18
    }).addTo(map);

    // Layer groups for dynamic content
    const routeLayer = new L.FeatureGroup();
    const markerLayer = new L.FeatureGroup();
    map.addLayer(routeLayer);
    map.addLayer(markerLayer);

    // Track animations for cleanup between refreshes
    const activeRouteAnimations = [];

    function stopIdleMode() {
        if (idleRefreshTimer) {
            clearTimeout(idleRefreshTimer);
            idleRefreshTimer = null;
        }
    }

    function clearRouteAnimations() {
        stopIdleMode();
        activeRouteAnimations.forEach(ctx => {
            if (typeof ctx.stop === 'function') {
                ctx.stop();
                return;
            }
            ctx.stopped = true;
            if (ctx.intervalId) {
                clearInterval(ctx.intervalId);
                ctx.intervalId = null;
            }
            if (ctx.timeoutId) {
                clearTimeout(ctx.timeoutId);
                ctx.timeoutId = null;
            }
            if (ctx.pointer && markerLayer.hasLayer(ctx.pointer)) {
                markerLayer.removeLayer(ctx.pointer);
            }
            if (ctx.line && routeLayer.hasLayer(ctx.line)) {
                routeLayer.removeLayer(ctx.line);
            }
        });
        activeRouteAnimations.length = 0;
    }

    function buildCurvedPath(startLatLng, endLatLng, segments = 80, direction = 1) {
        const [lat1, lon1] = startLatLng;
        const [lat2, lon2] = endLatLng;
        const dx = lat2 - lat1;
        const dy = lon2 - lon1;
        const dist = Math.sqrt(dx * dx + dy * dy) || 0.0001;

        const midLat = (lat1 + lat2) / 2;
        const midLon = (lon1 + lon2) / 2;

        const normX = (-dy / dist) * direction;
        const normY = (dx / dist) * direction;

        const curvature = Math.min(2.5, Math.max(0.15, dist * 0.35));

        const controlLat = midLat + normX * curvature;
        const controlLon = midLon + normY * curvature;

        const points = [];
        for (let i = 0; i <= segments; i++) {
            const t = i / segments;
            const oneMinusT = 1 - t;
            const lat = oneMinusT * oneMinusT * lat1 + 2 * oneMinusT * t * controlLat + t * t * lat2;
            const lon = oneMinusT * oneMinusT * lon1 + 2 * oneMinusT * t * controlLon + t * t * lon2;
            points.push([lat, lon]);
        }
        return points;
    }

    function shuffleArray(arr) {
        for (let i = arr.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [arr[i], arr[j]] = [arr[j], arr[i]];
        }
    }

    function toRad(deg) {
        return deg * Math.PI / 180;
    }

    function distanceNm(lat1, lon1, lat2, lon2) {
        const R = 6371; // Earth radius in km
        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
                  Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        const distKm = R * c;
        return distKm / 1.852; // convert to nautical miles
    }

    function startIdleMode() {
        stopIdleMode();

        const airportEntries = Object.entries(allAirports).filter(([, info]) =>
            info && typeof info.lat === 'number' && typeof info.lon === 'number'
        );
        if (airportEntries.length < 2) {
            return;
        }

        const totalAirports = airportEntries.length;
        let routesToShow = 1;
        if (totalAirports >= 12) {
            routesToShow = 10;
        } else if (totalAirports >= 8) {
            routesToShow = 8;
        } else if (totalAirports >= 5) {
            routesToShow = Math.min(6, totalAirports - 1);
        } else {
            routesToShow = Math.max(1, totalAirports - 1);
        }

        const plannedRoutes = [];
        const usedPairs = new Set();

        function computeBounds(entries) {
            let minLat = Infinity, maxLat = -Infinity, minLon = Infinity, maxLon = -Infinity;
            entries.forEach(([, info]) => {
                if (info.lat < minLat) minLat = info.lat;
                if (info.lat > maxLat) maxLat = info.lat;
                if (info.lon < minLon) minLon = info.lon;
                if (info.lon > maxLon) maxLon = info.lon;
            });
            return { minLat, maxLat, minLon, maxLon };
        }

        const bounds = computeBounds(airportEntries);
        const splitLat = (bounds.minLat + bounds.maxLat) / 2;
        const splitLon = (bounds.minLon + bounds.maxLon) / 2;

        const regions = {
            nw: [], ne: [], sw: [], se: []
        };

        airportEntries.forEach(entry => {
            const [, info] = entry;
            if (info.lat >= splitLat && info.lon < splitLon) {
                regions.nw.push(entry);
            } else if (info.lat >= splitLat && info.lon >= splitLon) {
                regions.ne.push(entry);
            } else if (info.lat < splitLat && info.lon < splitLon) {
                regions.sw.push(entry);
            } else {
                regions.se.push(entry);
            }
        });

        const regionNames = Object.keys(regions).sort((a, b) => regions[b].length - regions[a].length);

        function chooseStartFromRegion(regionEntries) {
            if (!regionEntries || regionEntries.length === 0) {
                return null;
            }
            const candidates = regionEntries.slice();
            shuffleArray(candidates);
            return candidates.slice(0, Math.min(3, candidates.length));
        }

        const candidateStarts = [];
        regionNames.forEach(region => {
            const picks = chooseStartFromRegion(regions[region]);
            if (picks) {
                picks.forEach(entry => {
                    if (!candidateStarts.some(item => item[0] === entry[0])) {
                        candidateStarts.push(entry);
                    }
                });
            }
        });

        if (candidateStarts.length < routesToShow) {
            const extra = airportEntries.slice();
            shuffleArray(extra);
            extra.forEach(entry => {
                if (candidateStarts.length >= routesToShow * 2) {
                    return;
                }
                if (!candidateStarts.some(item => item[0] === entry[0])) {
                    candidateStarts.push(entry);
                }
            });
        }

        const distanceBands = [
            { min: 400, max: 1400 },
            { min: 250, max: 900 },
            { min: 0, max: Infinity }
        ];

        function pickModerateDestination(startCode, startAirport) {
            for (let bandIndex = 0; bandIndex < distanceBands.length; bandIndex++) {
                const band = distanceBands[bandIndex];
                let bestCandidate = null;
                let bestScore = Infinity;
                const target = (band.max === Infinity) ? band.min + 800 : (band.min + band.max) / 2;

                airportEntries.forEach(([endCode, endAirport]) => {
                    if (!endAirport || endCode === startCode) {
                        return;
                    }
                    const pairKey = `${startCode}-${endCode}`;
                    if (usedPairs.has(pairKey)) {
                        return;
                    }
                    const distNm = distanceNm(startAirport.lat, startAirport.lon, endAirport.lat, endAirport.lon);
                    if (distNm < band.min || distNm > band.max) {
                        return;
                    }
                    const score = Math.abs(distNm - target);
                    if (score < bestScore) {
                        bestScore = score;
                        bestCandidate = { endCode, endAirport, distNm };
                    }
                });

                if (bestCandidate) {
                    return bestCandidate;
                }
            }
            return null;
        }

        candidateStarts.forEach(([startCode, startAirport]) => {
            if (plannedRoutes.length >= routesToShow) {
                return;
            }
            const candidate = pickModerateDestination(startCode, startAirport);
            if (candidate) {
                plannedRoutes.push({
                    startCode,
                    startAirport,
                    endCode: candidate.endCode,
                    endAirport: candidate.endAirport,
                    distNm: candidate.distNm
                });
                usedPairs.add(`${startCode}-${candidate.endCode}`);
            }
        });

        if (plannedRoutes.length < routesToShow) {
            const shuffled = airportEntries.slice();
            shuffleArray(shuffled);
            shuffled.forEach(([startCode, startAirport]) => {
                if (plannedRoutes.length >= routesToShow) {
                    return;
                }
                if (!startAirport) {
                    return;
                }
                const candidate = pickModerateDestination(startCode, startAirport);
                if (candidate) {
                    plannedRoutes.push({
                        startCode,
                        startAirport,
                        endCode: candidate.endCode,
                        endAirport: candidate.endAirport,
                        distNm: candidate.distNm
                    });
                    usedPairs.add(`${startCode}-${candidate.endCode}`);
                }
            });
        }

        if (plannedRoutes.length === 0 && airportEntries.length >= 2) {
            const [firstCode, firstAirport] = airportEntries[0];
            const [secondCode, secondAirport] = airportEntries[1];
            plannedRoutes.push({
                startCode: firstCode,
                startAirport: firstAirport,
                endCode: secondCode,
                endAirport: secondAirport,
                distNm: distanceNm(firstAirport.lat, firstAirport.lon, secondAirport.lat, secondAirport.lon)
            });
        }

        plannedRoutes.slice(0, routesToShow).forEach(route => {
            const startLatLng = [route.startAirport.lat, route.startAirport.lon];
            const endLatLng = [route.endAirport.lat, route.endAirport.lon];
            const direction = Math.random() > 0.5 ? 1 : -1;
            const curvePoints = buildCurvedPath(startLatLng, endLatLng, 80, direction);
            const distanceLabel = route.distNm ? `${Math.round(route.distNm)} NM` : '';
            const popupText = distanceLabel ? `${route.startCode} → ${route.endCode} (${distanceLabel})` : `${route.startCode} → ${route.endCode}`;
            startRouteAnimation(curvePoints, popupText);
        });

        idleRefreshTimer = setTimeout(() => {
            if (!currentFilterIcao) {
                clearRouteAnimations();
                startIdleMode();
            }
        }, 12000);
    }

    function startRouteAnimation(curvePoints, popupText) {
        const ctx = { intervalId: null, timeoutId: null, line: null, pointer: null, stopped: false };

        function cleanupLayers() {
            if (ctx.pointer && markerLayer.hasLayer(ctx.pointer)) {
                markerLayer.removeLayer(ctx.pointer);
                ctx.pointer = null;
            }
            if (ctx.line && routeLayer.hasLayer(ctx.line)) {
                routeLayer.removeLayer(ctx.line);
                ctx.line = null;
            }
        }

        function runCycle() {
            if (ctx.stopped) {
                return;
            }

            cleanupLayers();

            ctx.line = L.polyline([curvePoints[0]], {
                color: '#4dabf7',
                weight: 3,
                opacity: 0.9,
                dashArray: '12 8'
            });

            if (popupText) {
                ctx.line.bindPopup(popupText);
            }

            ctx.line.addTo(routeLayer);

            setTimeout(() => {
                if (ctx.line && ctx.line._path) {
                    const path = ctx.line._path;
                    path.classList.add('animated-route');
                    path.setAttribute('stroke-linecap', 'round');
                    path.setAttribute('stroke-linejoin', 'round');
                    path.setAttribute('stroke-dasharray', '12 8');
                }
            }, 0);

            ctx.pointer = L.circleMarker(curvePoints[0], {
                radius: 4,
                fillColor: '#ffffff',
                color: '#1c7ed6',
                weight: 2,
                opacity: 1,
                fillOpacity: 1
            }).addTo(markerLayer);

            let index = 1;
            ctx.intervalId = setInterval(() => {
                if (ctx.stopped) {
                    clearInterval(ctx.intervalId);
                    ctx.intervalId = null;
                    return;
                }

                if (index < curvePoints.length) {
                    ctx.line.setLatLngs(curvePoints.slice(0, index + 1));
                    if (ctx.pointer) {
                        ctx.pointer.setLatLng(curvePoints[index]);
                        ctx.pointer.bringToFront();
                    }
                    index++;
                } else {
                    clearInterval(ctx.intervalId);
                    ctx.intervalId = null;

                    if (ctx.pointer) {
                        markerLayer.removeLayer(ctx.pointer);
                        ctx.pointer = null;
                    }

                    ctx.timeoutId = setTimeout(() => {
                        ctx.timeoutId = null;
                        runCycle();
                    }, 1200);
                }
            }, 45);
        }

        ctx.stop = function () {
            ctx.stopped = true;
            if (ctx.intervalId) {
                clearInterval(ctx.intervalId);
                ctx.intervalId = null;
            }
            if (ctx.timeoutId) {
                clearTimeout(ctx.timeoutId);
                ctx.timeoutId = null;
            }
            cleanupLayers();
        };

        activeRouteAnimations.push(ctx);
        runCycle();
    }

    // Function to render routes from a given airport
    function updateMapRoutes(filterIcao) {
        clearRouteAnimations();
        routeLayer.clearLayers();
        markerLayer.clearLayers();

        if (!filterIcao || !allAirports[filterIcao]) {
            map.setView([20, 0], 3); // Reset to world view
            startIdleMode();
            return;
        }

        const centerAirport = allAirports[filterIcao];
        const centerLatLng = [centerAirport.lat, centerAirport.lon];

        // Add center airport marker
        L.circleMarker(centerLatLng, {
            radius: 8,
            fillColor: '#ff6b6b',
            color: '#c92a2a',
            weight: 2,
            opacity: 1,
            fillOpacity: 0.8
        }).bindPopup(`<strong>${filterIcao}</strong>`).addTo(markerLayer);

        // Find all routes connected to this airport and draw lines
        const connectedAirports = new Set();
        allLines.forEach(line => {
            let otherIcao = null;
            if (line.icao_dep === filterIcao && line.icao_arr) {
                otherIcao = line.icao_arr;
            } else if (line.icao_arr === filterIcao && line.icao_dep) {
                otherIcao = line.icao_dep;
            }

            if (otherIcao && allAirports[otherIcao]) {
                connectedAirports.add(otherIcao);
                const otherAirport = allAirports[otherIcao];
                const otherLatLng = [otherAirport.lat, otherAirport.lon];

                // Static marker at the destination point
                L.circleMarker(otherLatLng, {
                    radius: 6,
                    fillColor: '#4dabf7',
                    color: '#0c5aa0',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.7
                }).bindPopup(`<strong>${otherIcao}</strong>`).addTo(markerLayer);

                const popupText = `${filterIcao} → ${otherIcao} (${line.distance ? line.distance + ' NM' : '-'})`;
                const direction = filterIcao < otherIcao ? 1 : -1;
                const curvePoints = buildCurvedPath(centerLatLng, otherLatLng, 80, direction);

                startRouteAnimation(curvePoints, popupText);
            }
        });

        // Fit map to bounds if we have routes
        if (connectedAirports.size > 0) {
            const allMarkers = [centerLatLng];
            connectedAirports.forEach(icao => {
                const airport = allAirports[icao];
                allMarkers.push([airport.lat, airport.lon]);
            });
            const group = new L.featureGroup(allMarkers.map(coords => L.marker(coords)));
            map.fitBounds(group.getBounds().pad(0.1));
        } else {
            map.setView(centerLatLng, 6);
        }
    }

    // Listen for changes on ICAO filter inputs
    const depInput = document.querySelector('input[name="icao_dep"]');
    const arrInput = document.querySelector('input[name="icao_arr"]');

    function handleFilterChange() {
        const depIcao = (depInput.value || '').trim().toUpperCase();
        const arrIcao = (arrInput.value || '').trim().toUpperCase();
        const filterIcao = depIcao || arrIcao; // Priority to departure, else arrival
        currentFilterIcao = (filterIcao && allAirports[filterIcao]) ? filterIcao : '';
        updateMapRoutes(filterIcao);
    }

    if (depInput) depInput.addEventListener('input', handleFilterChange);
    if (arrInput) arrInput.addEventListener('input', handleFilterChange);
    const typeSelect = document.querySelector('select[name="type_ligne"]');
    if (typeSelect) typeSelect.addEventListener('change', handleFilterChange);

    handleFilterChange();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

