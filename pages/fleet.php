<?php
session_start();

require_once __DIR__ . '/../includes/db_connect.php';

require_once __DIR__ . '/../includes/require_login.php';

// Récupération des filtres
$immatFilter = $_GET['immat'] ?? '';
$fleetTypeFilter = $_GET['fleet_type'] ?? '';
$showVendus = isset($_GET['show_vendus']) ? (bool)$_GET['show_vendus'] : false;
$showMaintenance = isset($_GET['show_maintenance']) ? (bool)$_GET['show_maintenance'] : false;

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
               p.callsign AS pilote_callsign, f.fuel_restant, f.compteur_immo, f.en_vol, f.nb_maintenance, f.reservee,
               f.date_achat, f.mode_achat, COALESCE((SELECT SUM(cdvg.cout_vol) FROM CARNET_DE_VOL_GENERAL cdvg WHERE cdvg.appareil_id = f.id), 0) AS recettes_calculees, f.nb_annees_credit, f.nb_mois_restants, f.taux_percent, f.remboursement, f.traite_payee_cumulee, f.reste_a_payer, f.recette_vente, f.date_vente, f.actif
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
if (!$showVendus) {
    $sql .= " AND (f.actif = 1 OR f.actif IS NULL)";
}
if ($showMaintenance) {
    $sql .= " AND f.status = 1";
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
    <h2><?= t('fleet_title') ?> : <?= str_replace('{count}', $count, t('fleet_count')) ?></h2>

    <form method="get" action="fleet.php">
        <label for="immat"><?= t('fleet_filter_immat') ?>:</label>
        <input type="text" id="immat" name="immat" value="<?= htmlspecialchars($immatFilter) ?>" placeholder="Ex: F-XXXX" class="fleet-filter-input">

        <label for="fleet_type" class="filter-margin"><?= t('fleet_filter_fleet_type') ?>:</label>
        <select id="fleet_type" name="fleet_type" class="fleet-filter-select">
            <option value=""><?= t('fleet_filter_fleet_type_all') ?></option>
            <?php foreach ($fleetTypesList as $ft): ?>
                <option value="<?= htmlspecialchars($ft) ?>" <?= ($fleetTypeFilter === $ft) ? 'selected' : '' ?>><?= htmlspecialchars($ft) ?></option>
            <?php endforeach; ?>
        </select>

        <label class="filter-margin">
            <input type="checkbox" name="show_vendus" value="1" <?= $showVendus ? 'checked' : '' ?>>
            <?= t('fleet_filter_show_vendus') ?>
        </label>
        <label class="filter-margin">
            <input type="checkbox" name="show_maintenance" value="1" <?= $showMaintenance ? 'checked' : '' ?>>
            <?= t('fleet_filter_show_maintenance') ?>
        </label>

        <button class="btn" type="submit"><?= t('fleet_filter_button') ?></button>
        <button type="button" class="btn btn-reset" onclick="window.location.href='fleet.php';"><?= t('fleet_reset_button') ?></button>
    </form>

    <?php if (empty($fleet)): ?>
        <p class="no-results"><?= t('fleet_no_results') ?></p>
    <?php else: ?>

    <div class="fleet-spacer"></div>
        <!-- Tableau d'en-tête fixe -->
        <table class="table-skywings fleet-table">
            <thead class="table-skywings">
                <tr class="table-skywings">
                    <th><?= t('fleet_table_immat') ?></th>
                    <th><?= t('fleet_table_fleet_type') ?></th>
                    <th><?= t('fleet_table_categorie') ?></th>
                    <th><?= t('fleet_table_localisation') ?></th>
                    <th><?= t('fleet_table_hub') ?></th>
                    <th><?= t('fleet_table_status') ?></th>
                    <th><?= t('fleet_table_etat') ?></th>
                    <th><?= t('fleet_table_pilote') ?></th>
                    <th><?= t('fleet_table_fuel') ?></th>
                    <th><?= t('fleet_table_last_use') ?></th>
                    <th><?= t('fleet_table_en_vol') ?></th>
                    <th><?= t('fleet_table_reserve') ?></th>

                </tr>
            </thead>
            <tbody class="table-skywings">
                <?php foreach ($fleet as $avion):
                    $avionId = $avion['id'];
                    // Récupérer la date du dernier vol pour cet avion
                    $dernierVol = null;
                    try {
                        $stmtDernierVol = $pdo->prepare("SELECT MAX(date_vol) AS date_dernier_vol FROM CARNET_DE_VOL_GENERAL WHERE appareil_id = :appareil_id");
                        $stmtDernierVol->execute(['appareil_id' => $avionId]);
                        $rowDernierVol = $stmtDernierVol->fetch(PDO::FETCH_ASSOC);
                        if (!empty($rowDernierVol['date_dernier_vol'])) {
                            $date = $rowDernierVol['date_dernier_vol'];
                            // Format FR
                            $dernierVol = implode('-', array_reverse(explode('-', $date)));
                        } else {
                            $dernierVol = t('fleet_text_no_flight');
                        }
                    } catch (Exception $e) {
                        $dernierVol = t('fleet_text_error');
                    }
                    // Préparer les détails FLOTTE (inclut les champs financiers)
                    $details = [
                        t('fleet_detail_immat') => $avion['immat'],
                        t('fleet_detail_fleet_type') => $avion['type_libelle'],
                        t('fleet_detail_categorie') => $avion['categorie'],
                        t('fleet_detail_localisation') => $avion['localisation'],
                        t('fleet_detail_hub') => $avion['hub'],
                        t('fleet_detail_statut') => $avion['status'],
                        t('fleet_detail_etat') => $avion['etat']. ' %',
                        t('fleet_detail_last_user') => $avion['pilote_callsign'] ?? t('fleet_text_na'),
                        t('fleet_detail_fuel') => $avion['fuel_restant'],
                        t('fleet_detail_compteur') => $avion['compteur_immo'],
                        t('fleet_detail_en_vol') => (isset($avion['en_vol']) && (int)$avion['en_vol'] === 1) ? t('fleet_text_yes') : t('fleet_text_no'),
                        t('fleet_detail_reserve') => (isset($avion['reservee']) && (int)$avion['reservee'] === 1) ? t('fleet_text_yes') : t('fleet_text_no'),
                        t('fleet_detail_last_flight') => $dernierVol,
                        t('fleet_detail_date_achat') => (!empty($avion['date_achat'] ?? '') && preg_match('/^\d{4}-\d{2}-\d{2}$/', $avion['date_achat'] ?? '')) ? (implode('-', array_reverse(explode('-', $avion['date_achat']))) ) : ($avion['date_achat'] ?? ''),
                        t('fleet_detail_mode_achat') => (isset($avion['mode_achat']) && $avion['mode_achat'] === 'credit') ? t('fleet_text_credit') : ((isset($avion['mode_achat']) && $avion['mode_achat'] === 'comptant') ? t('fleet_text_cash') : ((isset($avion['nb_annees_credit']) && intval($avion['nb_annees_credit']) > 0) ? t('fleet_text_credit') : t('fleet_text_cash'))),
                        t('fleet_detail_recettes') => number_format(floatval($avion['recettes_calculees'] ?? 0), 2, ',', ' ') . ' €',
                        t('fleet_detail_annees_credit') => $avion['nb_annees_credit'] ?? '',
                        t('fleet_detail_taux_credit') => ($avion['taux_percent'] ?? '') . ' %',
                        t('fleet_detail_mensualite') => $avion['mode_achat'] === 'credit' && intval($avion['nb_annees_credit']) > 0 && floatval($avion['taux_percent']) > 0 ? number_format(floatval($avion['remboursement']) * ((floatval($avion['taux_percent']) / 100 / 12) / (1 - pow(1 + floatval($avion['taux_percent']) / 100 / 12, -(intval($avion['nb_annees_credit']) * 12)))), 2, ',', ' ') . ' €' : t('fleet_text_na'),
                        t('fleet_detail_mois_restants') => $avion['nb_mois_restants'] ?? '',
                        t('fleet_detail_traite_payee') => ($avion['traite_payee_cumulee'] ?? '') . ' €',
                        t('fleet_detail_reste_payer') => ($avion['reste_a_payer'] ?? '') . ' €',
                        t('fleet_detail_recette_vente') => empty($avion['date_vente'] ?? '') ? t('fleet_text_na') : (($avion['recette_vente'] ?? '') . ' €'),
                        t('fleet_detail_date_vente') => empty($avion['date_vente'] ?? '') ? t('fleet_text_na') : ($avion['date_vente'] ?? ''),
                    ];
                    $details_json = htmlspecialchars(json_encode($details), ENT_QUOTES, 'UTF-8');
                    $rowClass = 'fleet-row';
                    if (isset($avion['actif']) && !$avion['actif']) {
                        $rowClass .= ' fleet-row-inactive';
                    }
                ?>
                    <tr class="<?= $rowClass ?>" data-details="<?= $details_json ?>">
                        <td><?= htmlspecialchars($avion['immat'] ?? '') ?></td>
                        <td><?= htmlspecialchars($avion['type_libelle'] ?? '') ?></td>
                        <td><?= htmlspecialchars($avion['categorie'] ?? '') ?></td>
                        <td><?= htmlspecialchars($avion['localisation'] ?? '') ?></td>
                        <td><?= htmlspecialchars($avion['hub'] ?? '') ?></td>
                        <td>
                            <?php
                            if (isset($avion['actif']) && !$avion['actif']) {
                                echo t('fleet_status_vendu');
                            } else {
                                $statusVal = (int)($avion['status'] ?? 0);
                                echo match($statusVal) {
                                    0 => t('fleet_status_ok'),
                                    1 => t('fleet_status_maintenance'),
                                    2 => t('fleet_status_crash'),
                                    default => htmlspecialchars($avion['status'] ?? '')
                                };
                            }
                            ?>
                        </td>
                        <td><?= htmlspecialchars(($avion['etat'] ?? '') . ' %') ?></td>
                        <td><?= htmlspecialchars(($avion['pilote_callsign'] ?? t('fleet_text_na')) ?: '') ?></td>
                        <td><?= htmlspecialchars($avion['fuel_restant'] ?? '') ?></td>
                        <td><?= htmlspecialchars($dernierVol ?? '') ?></td>
                        <td>
                            <?= (isset($avion['en_vol']) && (int)$avion['en_vol'] === 1) ? t('fleet_text_yes') : t('fleet_text_no') ?>
                        </td>
                        <td><?php
                            $res = $avion['reservee'] ?? null;
                            if ($res === null || $res === '') {
                                echo t('fleet_text_no');
                            } else {
                                echo ((int)$res === 1) ? t('fleet_text_yes') : t('fleet_text_no');
                            }
                        ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- Popup modale pour détails avion -->
        <div id="fleet-modal" class="fleet-modal">
            <div class="fleet-modal-content">
                <span class="fleet-modal-close" id="fleet-modal-close">&times;</span>
                <h3><?= t('fleet_modal_title') ?></h3>
                <div id="fleet-modal-body">
                    <!-- Les détails seront injectés ici -->
                </div>
            </div>
        </div>
        <script>

        // Gestion du popup détails avion
        document.querySelectorAll('.fleet-row').forEach(function(row) {
            row.addEventListener('click', function() {
                const details = JSON.parse(this.getAttribute('data-details'));
                let html = '<table class="fd-table">';
                let modeAchat = details[<?= json_encode(t('fleet_detail_mode_achat')) ?>] || '';
                // Liste des clés financières
                const financeKeys = [
                    <?= json_encode(t('fleet_detail_date_achat')) ?>, <?= json_encode(t('fleet_detail_mode_achat')) ?>, <?= json_encode(t('fleet_detail_recettes')) ?>, <?= json_encode(t('fleet_detail_annees_credit')) ?>, <?= json_encode(t('fleet_detail_taux_credit')) ?>, <?= json_encode(t('fleet_detail_mensualite')) ?>, <?= json_encode(t('fleet_detail_mois_restants')) ?>, <?= json_encode(t('fleet_detail_traite_payee')) ?>, <?= json_encode(t('fleet_detail_reste_payer')) ?>, <?= json_encode(t('fleet_detail_recette_vente')) ?>, <?= json_encode(t('fleet_detail_date_vente')) ?>
                ];
                let financeRows = '';
                let normalRows = '';
                // Fonction de formatage nombre avec espace tous les 3 chiffres
                function formatNumberFr(val) {
                    if (typeof val === 'number' || (!isNaN(val) && val !== null && val !== '')) {
                        let n = Number(val);
                        return n.toLocaleString('fr-FR');
                    }
                    // Si déjà formaté ou pas un nombre, retourne tel quel
                    return val;
                }
                for (const key of Object.keys(details)) {
                    let v = details[key];
                    // Si valeur numérique (hors pourcentage), on la formate
                    if (typeof v === 'string' && v.match(/^[-+]?\d{1,3}(?:[\d\s.,]*)?(?:\s?€|\s?%|)$/)) {
                        // On extrait la partie numérique
                        let match = v.match(/([-+]?\d+[\d.,]*)/);
                        if (match) {
                            let num = match[1].replace(/\s/g, '').replace(',', '.');
                            let formatted = formatNumberFr(num);
                            v = v.replace(match[1], formatted);
                        }
                    }
                    // Si achat comptant, on masque les champs crédit
                    if ((modeAchat === <?= json_encode(t('fleet_text_cash')) ?> || modeAchat === 'Comptant') && (key === <?= json_encode(t('fleet_detail_annees_credit')) ?> || key === <?= json_encode(t('fleet_detail_taux_credit')) ?> || key === <?= json_encode(t('fleet_detail_mensualite')) ?> || key === <?= json_encode(t('fleet_detail_mois_restants')) ?> || key === <?= json_encode(t('fleet_detail_traite_payee')) ?> || key === <?= json_encode(t('fleet_detail_reste_payer')) ?>)) {
                        continue;
                    }
                    if (financeKeys.includes(key)) {
                        financeRows += '<tr><td class="fd-label">' + key + '</td><td class="fd-value">' + (v ?? '') + '</td></tr>';
                    } else {
                        normalRows += '<tr><td class="fd-label">' + key + '</td><td class="fd-value">' + (v ?? '') + '</td></tr>';
                    }
                }
                html += normalRows;
                if (financeRows) {
                    html += '<tr><td colspan="2"><hr class="divider"></td></tr>';
                    html += '<tr><td colspan="2" style="font-weight:bold;color:#1abc9c;font-size:1.08em;padding-bottom:6px;">' + <?= json_encode(t('fleet_modal_finance_title')) ?> + '</td></tr>';
                    html += financeRows;
                }
                //s'il y a un fichier image associé, on l'affiche
                const imagePath = '/assets/images/fleet/' + details[<?= json_encode(t('fleet_detail_immat')) ?>] + '.jpg';

                //verifie avec une requête AJAX si l'image existe                             
                html += '<tr><td colspan="2"><hr class="divider"></td></tr>';
                html += '<tr><td colspan="2" style="font-weight:bold;color:#1abc9c;font-size:1.08em;padding-bottom:6px;">' + <?= json_encode(t('fleet_modal_image')) ?> + '</td></tr>';
                html += '<tr><td colspan="2" style="text-align:center;"><img src="' + imagePath + '" alt="Image de l\'appareil" class="responsive-img"></td></tr>';
                                       
                html += '</table>';
                //si l'utilisateur est admin, on ajoute le bouton pour uploader une image
                if (details[<?= json_encode(t('fleet_detail_immat')) ?>] && <?php echo (int)($_SESSION['user']['isAdmin'] ?? 0); ?> === 1) {
                    html += '<hr class="divider">';
                    html += '<p style="font-weight:bold;color:#1abc9c;font-size:1.08em;padding-bottom:6px;">' + <?= json_encode(t('fleet_modal_actions')) ?> + '</p>';
                    html += '<form id="uploadForm" enctype="multipart/form-data" method="post" action="/scripts/admin_fleet_image.php">';
                    html += '<input type="hidden" name="immat" value="' + details[<?= json_encode(t('fleet_detail_immat')) ?>] + '">';
                    html += '<input type="file" name="image" accept=".jpg" required class="mb-8">';
                    html += '<button class="btn mt-8" type="submit">' + <?= json_encode(t('fleet_modal_upload')) ?> + '</button>';
                    html += '</form>';
                }
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
