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
        echo "<div style='font-weight:bold;color:#1ca64c;margin-bottom:16px;'>Réservation enregistrée avec succès. La réservation est valable pendant 24 heures.</div>";
        unset($_SESSION['flash_reserved']);
    } elseif (isset($_GET['reserved'])) {
        $res = $_GET['reserved'];
        if ($res === '1') {
            echo "<div style='font-weight:bold;color:#1ca64c;margin-bottom:16px;'>Réservation enregistrée avec succès. La réservation est valable pendant 24 heures.</div>";
        } elseif ($res === '0') {
            echo "<div style='font-weight:bold;color:#d60000;margin-bottom:16px;'>Échec de la réservation. Veuillez réessayer.</div>";
        } else {
            // allow a custom message but escape it
            echo "<div style='font-weight:bold;color:#1ca64c;margin-bottom:16px;'>" . htmlspecialchars($res) . "</div>";
        }
    }
    ?>
    <!-- Filters above the table so the map can align with the table header -->
    <form method="get" style="margin-bottom:1em;">
        <label>ICAO départ (de 1 à 4 caractères): <input type="text" name="icao_dep" value="<?= htmlspecialchars($filter_dep) ?>" maxlength="5" style="width:6em"/></label>
        <label style="margin-left:1em">ICAO arrivée (de 1 à 4 caractères): <input type="text" name="icao_arr" value="<?= htmlspecialchars($filter_arr) ?>" maxlength="5" style="width:6em"/></label>
        <br>
        <label style="margin-left:1em">Type de ligne:
            <select name="type_ligne" style="margin-left:.4em">
                <option value="">-- Tous --</option>
                <?php foreach ($typeLignes as $t): ?>
                    <option value="<?= (int)$t['id'] ?>" <?= ($filter_type !== null && $filter_type === (int)$t['id']) ? 'selected' : '' ?>><?= htmlspecialchars($t['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="btn">Filtrer</button>
        <button type="button" class="btn" id="resetBtn" style="margin-left:.5em">Réinitialiser</button>
    </form>
    <script>
        document.getElementById('resetBtn').addEventListener('click', function () {
            // redirige vers la même page sans paramètres
            window.location.href = 'lignes_regulieres.php';
        });
    </script>

    <div style="display:flex;gap:8px;align-items:flex-start;">
        <div class="narrow-table-wrapper" style="flex:1;margin-right:0;">
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

        <aside style="width:900px;max-width:100%;min-width:320px;flex:0 0 900px;margin-left:6px;">
            <div style="background:#fff;padding:12px;border-radius:10px;border:1px solid rgba(0,0,0,0.06);box-shadow:0 6px 18px rgba(0,0,0,0.05);">
                <h3 style="margin-top:0;font-size:1.05rem;color:#1a3552;">Carte des lignes régulières</h3>
                <div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:10px;border:1px solid rgba(0,0,0,0.06);margin-top:6px;">
                    <iframe src="https://www.google.com/maps/d/u/0/embed?mid=1fYs3mM8W3nRfVHl78xp2w8st6hcK22w" width="100%" height="100%" style="position:absolute;top:0;left:0;border:0;" allowfullscreen="allowfullscreen"></iframe>
                </div>
                <p style="margin-top:10px;font-size:0.95em;color:#333;">Utilisez les contrôles Google Maps pour zoomer et afficher les détails.</p>
            </div>
        </aside>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
