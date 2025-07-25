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


// Nouvelle requête : on récupère tous les appareils (actifs et inactifs)
$sql = "SELECT f.id, ft.fleet_type AS type_libelle, ft.type AS categorie, f.immat, f.localisation, f.hub, f.status, f.etat,
               p.callsign AS pilote_callsign, f.fuel_restant, f.compteur_immo, f.en_vol, f.nb_maintenance,
               f.date_achat, f.recettes, f.nb_annees_credit, f.taux_percent, f.remboursement, f.traite_payee_cumulee, f.reste_a_payer, f.recette_vente, f.date_vente, f.actif
        FROM FLOTTE f
        LEFT JOIN FLEET_TYPE ft ON f.fleet_type = ft.id
        LEFT JOIN PILOTES p ON f.dernier_utilisateur = p.id
        WHERE 1=1";

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
$fleet = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                    <th class="immat" style="width:98px;">Immatriculation</th>
                    <th class="fleet_type" style="width:98px;">Fleet_type</th>
                    <th class="categorie" style="width:80px;">Catégorie</th>
                    <th class="localisation" style="width:80px;">Localisation</th>
                    <th class="hub" style="width:80px;">Hub de rattachement</th>
                    <th class="status" style="width:70px;">Statut</th>
                    <th class="etat" style="width:70px;">État</th>
                    <th class="pilote" style="width:90px;">Dernier utilisateur</th>
                    <th class="fuel" style="width:70px;">Carburant restant</th>
                    <th class="compteur" style="width:70px;">Compteur Immo</th>
                    <th class="envol" style="width:60px;">En vol</th>
                    <th class="maintenance" style="width:105px;">Nombre maintenance</th>
                </tr>
            </thead>
        </table>
        <!-- Tableau scrollable des données -->
        <div class="table-scroll-wrapper-fleet">
            <table class="table-skywings">
                <tbody>
                    <?php foreach ($fleet as $avion):
                        $avionId = $avion['id'];
                        // Préparer les détails FLOTTE (inclut les champs financiers)
                        $details = [
                            'Immatriculation' => $avion['immat'],
                            'Fleet_type' => $avion['type_libelle'],
                            'Catégorie' => $avion['categorie'],
                            'Localisation' => $avion['localisation'],
                            'Hub de rattachement' => $avion['hub'],
                            'Statut' => $avion['status'],
                            'État' => $avion['etat'],
                            'Dernier utilisateur' => $avion['pilote_callsign'] ?? 'N/A',
                            'Carburant restant' => $avion['fuel_restant'],
                            'Compteur Immo' => $avion['compteur_immo'],
                            'En vol' => $avion['en_vol'],
                            'Nombre maintenance' => $avion['nb_maintenance'],
                            'Date achat' => (!empty($avion['date_achat'] ?? '') && preg_match('/^\d{4}-\d{2}-\d{2}$/', $avion['date_achat'] ?? '')) ? (implode('-', array_reverse(explode('-', $avion['date_achat']))) ) : ($avion['date_achat'] ?? ''),
                            'Mode d\'achat' => (isset($avion['mode_achat']) && $avion['mode_achat'] === 'credit') ? 'Crédit' : ((isset($avion['mode_achat']) && $avion['mode_achat'] === 'comptant') ? 'Comptant' : ((isset($avion['nb_annees_credit']) && intval($avion['nb_annees_credit']) > 0) ? 'Crédit' : 'Comptant')),
                            'Recettes' => ($avion['recettes'] ?? '') . ' €',
                            'Années crédit' => $avion['nb_annees_credit'] ?? '',
                            'Taux crédit' => ($avion['taux_percent'] ?? '') . ' %',
                            'Remboursement' => ($avion['remboursement'] ?? '') . ' €',
                            'Traite payée cumulée' => ($avion['traite_payee_cumulee'] ?? '') . ' €',
                            'Reste à payer' => ($avion['reste_a_payer'] ?? '') . ' €',
                            'Recette vente' => empty($avion['date_vente'] ?? '') ? 'N/A' : (($avion['recette_vente'] ?? '') . ' €'),
                            'Date vente' => empty($avion['date_vente'] ?? '') ? 'N/A' : ($avion['date_vente'] ?? ''),
                        ];
                        $details_json = htmlspecialchars(json_encode($details), ENT_QUOTES, 'UTF-8');
                        $rowClass = 'fleet-row';
                        if (isset($avion['actif']) && !$avion['actif']) {
                            $rowClass .= ' fleet-row-inactive';
                        }
                    ?>
                        <tr class="<?= $rowClass ?>" data-details="<?= $details_json ?>">
                            <td class="immat"  style="width:98px;"><?= htmlspecialchars($avion['immat'] ?? '') ?></td>
                            <td class="fleet_type" style="width:98px;"><?= htmlspecialchars($avion['type_libelle'] ?? '') ?></td>
                            <td class="categorie" style="width:80px;"><?= htmlspecialchars($avion['categorie'] ?? '') ?></td>
                            <td class="localisation" style="width:80px;"><?= htmlspecialchars($avion['localisation'] ?? '') ?></td>
                            <td class="hub" style="width:80px;"><?= htmlspecialchars($avion['hub'] ?? '') ?></td>
                            <td class="status" style="width:98px;">
                                <?php
                                if (isset($avion['actif']) && !$avion['actif']) {
                                    echo 'Vendu';
                                } else {
                                    $statusVal = (int)($avion['status'] ?? 0);
                                    echo match($statusVal) {
                                        0 => 'OK',
                                        1 => 'En maintenance',
                                        2 => 'Crash',
                                        default => htmlspecialchars($avion['status'] ?? '')
                                    };
                                }
                                ?>
                            </td>
                            <td class="etat" style="width:98px;"><?= htmlspecialchars($avion['etat'] ?? '') ?></td>
                            <td class="pilote" style="width:98px;"><?= htmlspecialchars(($avion['pilote_callsign'] ?? 'N/A') ?: '') ?></td>
                            <td class="fuel" style="width:98px;"><?= htmlspecialchars($avion['fuel_restant'] ?? '') ?></td>
                            <td class="compteur" style="width:98px;"><?= htmlspecialchars($avion['compteur_immo'] ?? '') ?></td>
                            <td class="envol" style="width:98px;"><?= htmlspecialchars($avion['en_vol'] ?? '') ?></td>
                            <td class="maintenance" style="width:98px;"><?= htmlspecialchars($avion['nb_maintenance'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Popup modale pour détails avion -->
        <div id="fleet-modal" class="fleet-modal" style="display:none;">
            <div class="fleet-modal-content">
                <span class="fleet-modal-close" id="fleet-modal-close">&times;</span>
                <h3>Détails de l'appareil</h3>
                <div id="fleet-modal-body">
                    <!-- Les détails seront injectés ici -->
                </div>
            </div>
        </div>
        <style>
        .fleet-row-inactive td {
            color: #b0b0b0 !important;
            background: #f6f6f6 !important;
        }
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
        <style>
        .fleet-modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0,0,0,0.35);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .fleet-modal-content {
            background: #fff;
            padding: 24px 32px;
            border-radius: 10px;
            min-width: 320px;
            max-width: 90vw;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 4px 24px rgba(0,0,0,0.18);
            position: relative;
        }
        .fleet-modal-close {
            position: absolute;
            top: 12px;
            right: 18px;
            font-size: 2em;
            color: #0d47a1;
            cursor: pointer;
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

        // Gestion du popup détails avion
        document.querySelectorAll('.fleet-row').forEach(function(row) {
            row.addEventListener('click', function() {
                const details = JSON.parse(this.getAttribute('data-details'));
                let html = '<table style="width:100%;border-collapse:collapse;">';
                let modeAchat = details['Mode d\'achat'] || '';
                // Liste des clés financières
                const financeKeys = [
                    'Date achat', 'Mode d\'achat', 'Recettes', 'Années crédit', 'Taux crédit', 'Remboursement', 'Traite payée cumulée', 'Reste à payer', 'Recette vente', 'Date vente'
                ];
                let financeRows = '';
                let normalRows = '';
                for (const key of Object.keys(details)) {
                    const v = details[key];
                    // Si achat comptant, on masque les champs crédit
                    if (modeAchat === 'Comptant' && (key === 'Années crédit' || key === 'Taux crédit' || key === 'Remboursement' || key === 'Traite payée cumulée' || key === 'Reste à payer')) {
                        continue;
                    }
                    if (financeKeys.includes(key)) {
                        financeRows += '<tr><td style="font-weight:bold;padding:4px 8px;color:#0d47a1;">' + key + '</td><td style="padding:4px 8px;">' + (v ?? '') + '</td></tr>';
                    } else {
                        normalRows += '<tr><td style="font-weight:bold;padding:4px 8px;color:#0d47a1;">' + key + '</td><td style="padding:4px 8px;">' + (v ?? '') + '</td></tr>';
                    }
                }
                html += normalRows;
                if (financeRows) {
                    html += '<tr><td colspan="2" style="padding:8px 0 2px 0;"><hr style="border:0;border-top:1.5px solid #1abc9c;margin:10px 0 6px 0;"></td></tr>';
                    html += '<tr><td colspan="2" style="font-weight:bold;color:#1abc9c;font-size:1.08em;padding-bottom:6px;">Informations financières</td></tr>';
                    html += financeRows;
                }
                html += '</table>';
                document.getElementById('fleet-modal-body').innerHTML = html;
                document.getElementById('fleet-modal').style.display = 'flex';
            });
        });
        document.getElementById('fleet-modal-close').onclick = function() {
            document.getElementById('fleet-modal').style.display = 'none';
        };
        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') document.getElementById('fleet-modal').style.display = 'none';
        });
        document.getElementById('fleet-modal').addEventListener('click', function(e) {
            if (e.target === this) this.style.display = 'none';
        });
        </script>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
