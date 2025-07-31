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

        <div style="height: 18px;"></div>
        <!-- Tableau d'en-tête fixe -->
        <table class="table-skywings">
            <thead class="table-skywings">
                <tr class="table-skywings">
                    <th style="width:8%;">Immatriculation</th>
                    <th style="width:8%;">Fleet_type</th>
                    <th style="width:8%;">Catégorie</th>
                    <th style="width:8%;">Localisation</th>
                    <th style="width:8%;">Hub de rattachement</th>
                    <th style="width:8%;">Statut</th>
                    <th style="width:8%;">État</th>
                    <th style="width:8%;">Dernier utilisateur</th>
                    <th style="width:8%;">Carburant restant</th>
                    <th style="width:8%;">Compteur Immo</th>
                    <th style="width:8%;">En vol</th>
                    <th style="width:8%;">Nombre maintenance</th>
                </tr>
            </thead>
            <tbody class="table-skywings">
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
                        <td style="width:8%;"><?= htmlspecialchars($avion['immat'] ?? '') ?></td>
                        <td style="width:8%;"><?= htmlspecialchars($avion['type_libelle'] ?? '') ?></td>
                        <td style="width:8%;"><?= htmlspecialchars($avion['categorie'] ?? '') ?></td>
                        <td style="width:8%;"><?= htmlspecialchars($avion['localisation'] ?? '') ?></td>
                        <td style="width:8%;"><?= htmlspecialchars($avion['hub'] ?? '') ?></td>
                        <td style="width:8%;">
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
                        <td style="width:8%;"><?= htmlspecialchars($avion['etat'] ?? '') ?></td>
                        <td style="width:8%;"><?= htmlspecialchars(($avion['pilote_callsign'] ?? 'N/A') ?: '') ?></td>
                        <td style="width:8%;"><?= htmlspecialchars($avion['fuel_restant'] ?? '') ?></td>
                        <td style="width:8%;"><?= htmlspecialchars($avion['compteur_immo'] ?? '') ?></td>
                        <td style="width:8%;"><?= htmlspecialchars($avion['en_vol'] ?? '') ?></td>
                        <td style="width:8%;"><?= htmlspecialchars($avion['nb_maintenance'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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
