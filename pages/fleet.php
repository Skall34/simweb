<?php
session_start();

require __DIR__ . '/../includes/db_connect.php';

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

// Récupération du filtre (immat)
$immatFilter = $_GET['immat'] ?? '';

// Requête avec jointure sur FLEET_TYPE et PILOTES, filtre possible
$sql = "SELECT f.id, ft.fleet_type AS type_libelle, f.type, f.immat, f.localisation, f.hub, f.status, f.etat, 
               p.callsign AS pilote_callsign, f.fuel_restant, f.compteur_immo, f.en_vol, f.nb_maintenance
        FROM FLOTTE f
        LEFT JOIN FLEET_TYPE ft ON f.fleet_type = ft.id
        LEFT JOIN PILOTES p ON f.dernier_utilisateur = p.id
        WHERE f.actif = 1";

$params = [];

if ($immatFilter !== '') {
    $sql .= " AND f.immat LIKE :immat";
    $params['immat'] = '%' . $immatFilter . '%';
}

$sql .= " ORDER BY f.immat";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$fleet = $stmt->fetchAll();


$sqlCount = "SELECT count(*) AS total FROM FLOTTE WHERE actif = 1";
$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute();
$countRow = $stmtCount->fetch();
$count = $countRow['total'];


include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';

?>

<main>
    <h2>Liste de la flotte : nous avons <?= $count ?>&nbsp;appareils actifs</h2>

    <form method="get" action="fleet.php">
        <label for="immat">Filtrer par immatriculation:</label>
        <input type="text" id="immat" name="immat" value="<?= htmlspecialchars($immatFilter) ?>" placeholder="Ex: F-XXXX">
        <button class="btn" type="submit">Filtrer</button>
        <button type="button" class="btn" style="margin-left:10px;" onclick="window.location.href='fleet.php';">Réinitialiser</button>
    </form>

    <?php if (empty($fleet)): ?>
        <p>Aucun avion trouvé.</p>
    <?php else: ?>
        <table class="table-skywings" border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Immatriculation</th>
                    <th>Type</th>
                    <th>Type interne</th>
                    <th>Localisation</th>
                    <th>Hub</th>
                    <th>Status</th>
                    <th>État</th>
                    <th>Dernier utilisateur</th>
                    <th>Carburant restant</th>
                    <th>Compteur Immo</th>
                    <th>En vol</th>
                    <th>Nombre maintenance</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fleet as $avion): ?>
                    <tr>
                        <td><?= htmlspecialchars($avion['immat']) ?></td>
                        <td><?= htmlspecialchars($avion['type_libelle']) ?></td>
                        <td><?= htmlspecialchars($avion['type']) ?></td>
                        <td><?= htmlspecialchars($avion['localisation']) ?></td>
                        <td><?= htmlspecialchars($avion['hub']) ?></td>
                        <td><?= htmlspecialchars($avion['status']) ?></td>
                        <td><?= htmlspecialchars($avion['etat']) ?></td>
                        <td><?= htmlspecialchars($avion['pilote_callsign'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($avion['fuel_restant']) ?></td>
                        <td><?= htmlspecialchars($avion['compteur_immo']) ?></td>
                        <td><?= htmlspecialchars($avion['en_vol']) ?></td>
                        <td><?= htmlspecialchars($avion['nb_maintenance']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
