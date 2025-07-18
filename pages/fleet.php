<?php
session_start();

require __DIR__ . '/../includes/db_connect.php';

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

// Récupération des filtres
$immatFilter = $_GET['immat'] ?? '';
$fleetTypeFilter = $_GET['fleet_type'] ?? '';

// Récupérer la liste des fleet_types pour le filtre
$fleetTypesList = [];
try {
    $stmtFleetTypes = $pdo->query("SELECT DISTINCT fleet_type FROM FLEET_TYPE WHERE fleet_type IS NOT NULL AND fleet_type <> '' ORDER BY fleet_type ASC");
    $fleetTypesList = $stmtFleetTypes->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Ignore erreur
}

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
if ($fleetTypeFilter !== '') {
    $sql .= " AND ft.fleet_type = :fleet_type";
    $params['fleet_type'] = $fleetTypeFilter;
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

        <label for="fleet_type" style="margin-left:18px;">Filtrer par Fleet type:</label>
        <select id="fleet_type" name="fleet_type">
            <option value="">-- Tous les types --</option>
            <?php foreach ($fleetTypesList as $ft): ?>
                <option value="<?= htmlspecialchars($ft) ?>" <?= ($fleetTypeFilter === $ft) ? 'selected' : '' ?>><?= htmlspecialchars($ft) ?></option>
            <?php endforeach; ?>
        </select>

        <button class="btn" type="submit">Filtrer</button>
        <button type="button" class="btn" style="margin-left:10px;" onclick="window.location.href='fleet.php';">Réinitialiser</button>
    </form>

    <?php if (empty($fleet)): ?>
        <p style="font-size:1.25em;color:#0066cc;font-weight:600;background:#f7fbff;padding:18px 0;border-radius:8px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.04);margin:28px 0;">Aucun appareil trouvé.</p>
    <?php else: ?>
        <style>
            .table-skywings th, .table-skywings td {
                padding: 4px 6px;
                font-size: 14px;
            }
            .table-skywings th.immat, .table-skywings td.immat { width: 90px; }
            .table-skywings th.fleet_type, .table-skywings td.fleet_type { width: 90px; }
            .table-skywings th.categorie, .table-skywings td.categorie { width: 80px; }
            .table-skywings th.localisation, .table-skywings td.localisation { width: 80px; }
            .table-skywings th.hub, .table-skywings td.hub { width: 80px; }
            .table-skywings th.status, .table-skywings td.status { width: 70px; }
            .table-skywings th.etat, .table-skywings td.etat { width: 70px; }
            .table-skywings th.pilote, .table-skywings td.pilote { width: 90px; }
            .table-skywings th.fuel, .table-skywings td.fuel { width: 70px; }
            .table-skywings th.compteur, .table-skywings td.compteur { width: 70px; }
            .table-skywings th.envol, .table-skywings td.envol { width: 50px; }
            .table-skywings th.maintenance, .table-skywings td.maintenance { width: 70px; }
        </style>
        <!-- Tableau d'en-tête fixe -->
        <table class="table-skywings table-header-fixed-fleet">
            <thead>
                <tr>
                    <th class="immat">Immatriculation</th>
                    <th class="fleet_type">Fleet_type</th>
                    <th class="categorie">Catégorie</th>
                    <th class="localisation">Localisation</th>
                    <th class="hub">Hub de rattachement</th>
                    <th class="status">Statut</th>
                    <th class="etat">État</th>
                    <th class="pilote">Dernier utilisateur</th>
                    <th class="fuel">Carburant restant</th>
                    <th class="compteur">Compteur Immo</th>
                    <th class="envol">En vol</th>
                    <th class="maintenance">Nombre maintenance</th>
                </tr>
            </thead>
        </table>
        <!-- Tableau scrollable des données -->
        <div class="table-scroll-wrapper-fleet">
            <table class="table-skywings">
                <tbody>
                    <?php foreach ($fleet as $avion): ?>
                        <tr>
                            <td class="immat"><?= htmlspecialchars($avion['immat']) ?></td>
                            <td class="fleet_type"><?= htmlspecialchars($avion['type_libelle']) ?></td>
                            <td class="categorie"><?= htmlspecialchars($avion['type']) ?></td>
                            <td class="localisation"><?= htmlspecialchars($avion['localisation']) ?></td>
                            <td class="hub"><?= htmlspecialchars($avion['hub']) ?></td>
                            <td class="status"><?= htmlspecialchars($avion['status']) ?></td>
                            <td class="etat"><?= htmlspecialchars($avion['etat']) ?></td>
                            <td class="pilote"><?= htmlspecialchars($avion['pilote_callsign'] ?? 'N/A') ?></td>
                            <td class="fuel"><?= htmlspecialchars($avion['fuel_restant']) ?></td>
                            <td class="compteur"><?= htmlspecialchars($avion['compteur_immo']) ?></td>
                            <td class="envol"><?= htmlspecialchars($avion['en_vol']) ?></td>
                            <td class="maintenance"><?= htmlspecialchars($avion['nb_maintenance']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <style>
            .table-skywings th, .table-skywings td {
                padding: 4px 6px;
                font-size: 14px;
                box-sizing: border-box;
            }
            .table-skywings th {
                white-space: normal;
                word-break: break-word;
            }
            .table-skywings th.immat, .table-skywings td.immat { width: 90px; }
            .table-skywings th.fleet_type, .table-skywings td.fleet_type { width: 90px; }
            .table-skywings th.categorie, .table-skywings td.categorie { width: 80px; }
            .table-skywings th.localisation, .table-skywings td.localisation { width: 80px; }
            .table-skywings th.hub, .table-skywings td.hub { width: 80px; }
            .table-skywings th.status, .table-skywings td.status { width: 70px; }
            .table-skywings th.etat, .table-skywings td.etat { width: 70px; }
            .table-skywings th.pilote, .table-skywings td.pilote { width: 90px; }
            .table-skywings th.fuel, .table-skywings td.fuel { width: 70px; }
            .table-skywings th.compteur, .table-skywings td.compteur { width: 70px; }
            .table-skywings th.envol, .table-skywings td.envol { width: 50px; }
            .table-skywings th.maintenance, .table-skywings td.maintenance { width: 70px; }
            .table-header-fixed-fleet th {
                background: #0d47a1;
                color: #fff;
                border-bottom: 2px solid #08306b;
                z-index: 10;
                box-shadow: 0 2px 4px rgba(0,0,0,0.03);
                text-align: center;
                font-weight: bold;
                letter-spacing: 0.5px;
            }
            .table-header-fixed-fleet {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                table-layout: auto;
                margin-bottom: 0;
            }
            .table-scroll-wrapper-fleet {
                width: 100%;
                max-height: 60vh;
                overflow-y: auto;
                overflow-x: auto;
                border-top: none;
            }
        </style>
        <script>
        // Synchronise dynamiquement la largeur des colonnes du header avec celles du tableau de données
        function syncHeaderWidthsFleet() {
            const headerTable = document.querySelector('.table-header-fixed-fleet');
            const dataTable = document.querySelector('.table-scroll-wrapper-fleet .table-skywings');
            if (!headerTable || !dataTable) return;
            const headerCells = headerTable.querySelectorAll('th');
            const dataRow = dataTable.querySelector('tr');
            if (!dataRow) return;
            const dataCells = dataRow.querySelectorAll('td');
            if (headerCells.length !== dataCells.length) return;
            // Reset widths
            headerCells.forEach(th => th.style.width = '');
            dataCells.forEach(td => td.style.width = '');
            // Get computed widths from data cells
            for (let i = 0; i < headerCells.length; i++) {
                const width = dataCells[i].getBoundingClientRect().width + 'px';
                headerCells[i].style.width = width;
                dataCells[i].style.width = width;
            }
            // Ajuste la largeur du headerTable pour ne pas dépasser le dataTable (évite le débordement dû au scrollbar)
            headerTable.style.width = dataTable.getBoundingClientRect().width + 'px';
        }
        window.addEventListener('load', syncHeaderWidthsFleet);
        window.addEventListener('resize', syncHeaderWidthsFleet);
        document.querySelector('.table-scroll-wrapper-fleet').addEventListener('scroll', function() {
            document.querySelector('.table-header-fixed-fleet').scrollLeft = this.scrollLeft;
        });
        </script>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
