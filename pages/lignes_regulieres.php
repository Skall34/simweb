<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';

if (!isset($_SESSION['user']['id'])) {
    header('Location: ../login.php');
    exit;
}

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
    <h2>Lignes régulières disponibles (<?= $lines_count ?>)</h2>
    <p>Choisissez une ligne pour réserver un appareil.</p>
    <br>
    <?php
    // message flash après réservation (session) ou fallback sur GET
    if (!empty($_SESSION['flash_reserved'])) {
        echo "<div class='flash-success'>Réservation enregistrée avec succès. La réservation est valable pendant 24 heures.</div>";
        unset($_SESSION['flash_reserved']);
    } elseif (isset($_GET['reserved'])) {
        $res = $_GET['reserved'];
        if ($res === '1') {
            echo "<div class='flash-success'>Réservation enregistrée avec succès. La réservation est valable pendant 24 heures.</div>";
        } elseif ($res === '0') {
            echo "<div class='flash-error'>Échec de la réservation. Veuillez réessayer.</div>";
        } else {
            // allow a custom message but escape it
            echo "<div class='flash-success'>" . htmlspecialchars($res) . "</div>";
        }
    }
    ?>
    <!-- Filters above the table so the map can align with the table header -->
    <form method="get" class="filters-form">
        <label>ICAO départ:
            <input type="text" name="icao_dep" value="<?= htmlspecialchars($filter_dep) ?>" maxlength="5" class="fleet-filter-input input-160" placeholder="Ex: LFPG" />
        </label>
        <label>ICAO arrivée:
            <input type="text" name="icao_arr" value="<?= htmlspecialchars($filter_arr) ?>" maxlength="5" class="fleet-filter-input input-160" placeholder="Ex: LFPO" />
        </label>
        <br>
        <label>Type de ligne:
            <select name="type_ligne" class="fleet-filter-select input-160">
                <option value="">-- Tous --</option>
                <?php foreach ($typeLignes as $t): ?>
                    <option value="<?= (int)$t['id'] ?>" <?= ($filter_type !== null && $filter_type === (int)$t['id']) ? 'selected' : '' ?>><?= htmlspecialchars($t['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    <button type="submit" class="btn-bleu">Filtrer</button>
    <button type="button" class="btn btn-reset" id="resetBtn">Réinitialiser</button>
    </form>
    <script>
        document.getElementById('resetBtn').addEventListener('click', function () {
            // redirige vers la même page sans paramètres
            window.location.href = 'lignes_regulieres.php';
        });
    </script>

    <div class="content-columns">
    <div class="narrow-table-wrapper">
            <div class="panel">
                <h3>Lignes régulières</h3>
                <table class="table-skywings">
        <thead>
            <tr>
                <th>Départ</th>
                <th>Arrivée</th>
                <th>Type</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($lines) === 0): ?>
                <tr><td colspan="4">Aucune ligne trouvée pour ces filtres.</td></tr>
            <?php else: ?>
                <?php foreach ($lines as $line): ?>
                    <tr>
                        <td><?= htmlspecialchars($line['icao_dep']) ?></td>
                        <td><?= htmlspecialchars($line['icao_arr']) ?></td>
                        <td><?= htmlspecialchars($line['type_label'] ?? '') ?></td>
                        <td>
                            <!-- Lien simple (sans style btn) demandé par l'utilisateur -->
                            <a href="reserver_ligne.php?ligne_id=<?= urlencode($line['id']) ?>">Réserver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
            </div>
        </div>

        <!-- Separator between table panel and map panel -->
        <div class="vertical-sep"></div>

        <aside class="right-aside">
            <div class="panel">
                <h3>Réservations en cours</h3>
                <?php if (!empty($reservations)): ?>
                    <div class="reservations-scroll">
                        <table class="table-skywings compact">
                            <thead>
                                <tr>
                                    <th>Pilote</th>
                                    <th>Ligne</th>
                                    <th>Appareil</th>
                                    <th>Statut</th>
                                    <th>Date</th>
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
                                                echo 'En vol';
                                            } elseif ($st === 'reserved') {
                                                echo 'Réservé';
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
                    <p class="empty-msg">Aucune réservation en cours.</p>
                <?php endif; ?>
            </div>

            <div class="panel">
                <h3>Carte des lignes régulières</h3>
                <div class="map-embed">
                    <iframe class="map-iframe" src="https://www.google.com/maps/d/u/0/embed?mid=1fYs3mM8W3nRfVHl78xp2w8st6hcK22w" allowfullscreen="allowfullscreen"></iframe>
                </div>
                <p class="map-note">Utilisez les contrôles Google Maps pour zoomer et afficher les détails.</p>
            </div>
        </aside>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
