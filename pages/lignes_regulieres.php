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

// Construire la requête de façon paramétrée (pour limiter l'impact sur l'existant)
$sql = "SELECT lr.* FROM LIGNES_REGULIERES lr";
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
if (count($conds) > 0) {
    $sql .= ' WHERE ' . implode(' AND ', $conds);
}
$sql .= ' ORDER BY lr.id DESC';

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
    <form method="get" style="margin-bottom:1em;">
        <label>ICAO départ (de 1 à 4 caractères): <input type="text" name="icao_dep" value="<?= htmlspecialchars($filter_dep) ?>" maxlength="5" style="width:6em"/></label>
        <label style="margin-left:1em">ICAO arrivée (de 1 à 4 caractères): <input type="text" name="icao_arr" value="<?= htmlspecialchars($filter_arr) ?>" maxlength="5" style="width:6em"/></label>
        <button type="submit" class="btn">Filtrer</button>
        <button type="button" class="btn" id="resetBtn" style="margin-left:.5em">Réinitialiser</button>
    </form>
    <script>
        document.getElementById('resetBtn').addEventListener('click', function () {
            // redirige vers la même page sans paramètres
            window.location.href = 'lignes_regulieres.php';
        });
    </script>
    <table class="table-skywings">
        <thead>
            <tr>
                <th>Départ</th>
                <th>Arrivée</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($lines) === 0): ?>
                <tr><td colspan="3">Aucune ligne trouvée pour ces filtres.</td></tr>
            <?php else: ?>
                <?php foreach ($lines as $line): ?>
                    <tr>
                        <td><?= htmlspecialchars($line['icao_dep']) ?></td>
                        <td><?= htmlspecialchars($line['icao_arr']) ?></td>
                        <td>
                            <!-- Lien simple (sans style btn) demandé par l'utilisateur -->
                            <a href="reserver_ligne.php?ligne_id=<?= urlencode($line['id']) ?>">Réserver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
