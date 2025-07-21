<?php

session_start();

require __DIR__ . '/../includes/db_connect.php';

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user']['id'];

// Requête pour récupérer les vols du pilote connecté avec la structure de tableau_vols.php

// Gestion des tris

// Filtres
$immatFilter = $_GET['immat'] ?? '';
$missionFilter = $_GET['mission'] ?? '';

// Récupérer la liste des missions pour le filtre
$missionsList = [];
try {
    $stmtMissions = $pdo->query("SELECT DISTINCT libelle FROM MISSIONS WHERE libelle IS NOT NULL AND libelle <> '' ORDER BY libelle ASC");
    $missionsList = $stmtMissions->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Ignore erreur
}

$sql = "
SELECT 
  c.date_vol,
  f.immat,
  c.depart,
  c.destination,
  c.fuel_depart,
  c.fuel_arrivee,
  c.payload,
  c.heure_depart,
  c.heure_arrivee,
  c.note_du_vol,
  m.libelle AS mission_libelle,
  c.cout_vol,
  c.pirep_maintenance,
  TIMEDIFF(c.heure_arrivee, c.heure_depart) AS block_time,
  (c.fuel_depart - c.fuel_arrivee) AS conso
FROM CARNET_DE_VOL_GENERAL c
LEFT JOIN FLOTTE f ON c.appareil_id = f.id
LEFT JOIN MISSIONS m ON c.mission_id = m.id
WHERE c.pilote_id = :id_pilote";

$params = ['id_pilote' => $userId];
if ($immatFilter !== '') {
    $sql .= " AND f.immat LIKE :immat";
    $params['immat'] = '%' . $immatFilter . '%';
}
if ($missionFilter !== '') {
    $sql .= " AND m.libelle = :mission";
    $params['mission'] = $missionFilter;
}
$sql .= " ORDER BY c.date_vol DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$flights = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>

<main>
    <h2>Mes vols</h2>
    <form method="get" action="flights.php" style="margin-bottom:12px;">
        <label for="immat">Filtrer par immatriculation:</label>
        <input type="text" id="immat" name="immat" value="<?= htmlspecialchars($immatFilter) ?>" placeholder="Ex: F-XXXX">

        <label for="mission" style="margin-left:18px;">Filtrer par Mission:</label>
        <select id="mission" name="mission">
            <option value="">-- Toutes les missions --</option>
            <?php foreach ($missionsList as $m): ?>
                <option value="<?= htmlspecialchars($m) ?>" <?= ($missionFilter === $m) ? 'selected' : '' ?>><?= htmlspecialchars($m) ?></option>
            <?php endforeach; ?>
        </select>

        <button class="btn" type="submit">Filtrer</button>
        <button type="button" class="btn" style="margin-left:10px;" onclick="window.location.href='flights.php';">Réinitialiser</button>
    </form>
    <?php
        $nbResults = count($flights);
        if ($immatFilter !== '' || $missionFilter !== '') {
            echo '<p style="margin-bottom:8px;color:#1565c0;font-weight:bold;">' . $nbResults . ' vol' . ($nbResults > 1 ? 's' : '') . ' trouvé' . ($nbResults > 1 ? 's' : '') . ' avec ce filtre.</p>';
        }
    ?>
    <?php if (empty($flights)): ?>
        <p>Aucun vol trouvé pour ce pilote.</p>
    <?php else: ?>
        <div class="table-main-padding">
            <!-- Tableau d'en-tête fixe -->
            <table class="table-skywings table-header-fixed" style="table-layout:fixed;">
                <thead>
                    <tr>
                        <th style="width:90px;">Date vol</th>
                        <th style="width:90px;">Immat</th>
                        <th style="width:90px;">Départ</th>
                        <th style="width:90px;">Destination</th>
                        <th style="width:90px;">Fuel départ</th>
                        <th style="width:90px;">Fuel arrivée</th>
                        <th style="width:90px;">Conso</th>
                        <th style="width:90px;">Payload</th>
                        <th style="width:90px;">Heure départ</th>
                        <th style="width:90px;">Heure arrivée</th>
                        <th style="width:90px;">Block time</th>
                        <th style="width:110px;">Note du vol</th>
                        <th style="width:90px;">Mission</th>
                        <th style="width:110px;">Recette du vol</th>
                        <th style="width:120px;">Pirep maintenance</th>
                    </tr>
                </thead>
            </table>
            <!-- Tableau scrollable des données -->
            <div class="table-scroll-wrapper">
                <table class="table-skywings" style="table-layout:fixed;">
                    <tbody>
                    <?php foreach ($flights as $flight):
                        $pirep_complet = $flight['pirep_maintenance'];
                        $pirep_court = mb_strimwidth($pirep_complet, 0, 13, '...');
                        $date_formatee = date("d-m-Y", strtotime($flight['date_vol']));
                        $details = [
                            'Date vol' => $date_formatee,
                            'Immat' => $flight['immat'],
                            'Départ' => $flight['depart'],
                            'Destination' => $flight['destination'],
                            'Fuel départ' => $flight['fuel_depart'],
                            'Fuel arrivée' => $flight['fuel_arrivee'],
                            'Conso' => $flight['conso'],
                            'Payload' => $flight['payload'],
                            'Heure départ' => $flight['heure_depart'],
                            'Heure arrivée' => $flight['heure_arrivee'],
                            'Block time' => $flight['block_time'],
                            'Note du vol' => $flight['note_du_vol'],
                            'Mission' => $flight['mission_libelle'],
                            'Recette du vol' => number_format($flight['cout_vol'], 2) . ' €',
                            'Pirep' => $pirep_complet
                        ];
                        $details_json = htmlspecialchars(json_encode($details), ENT_QUOTES, 'UTF-8');
                    ?>
                        <tr class="vol-row" data-details="<?= $details_json ?>">
                            <td><?= $date_formatee ?></td>
                            <td><?php echo htmlspecialchars($flight['immat']); ?></td>
                            <td><?php echo htmlspecialchars($flight['depart']); ?></td>
                            <td><?php echo htmlspecialchars($flight['destination']); ?></td>
                            <td><?php echo htmlspecialchars($flight['fuel_depart']); ?></td>
                            <td><?php echo htmlspecialchars($flight['fuel_arrivee']); ?></td>
                            <td><?php echo htmlspecialchars($flight['conso']); ?></td>
                            <td><?php echo htmlspecialchars($flight['payload']); ?></td>
                            <td><?php echo htmlspecialchars($flight['heure_depart']); ?></td>
                            <td><?php echo htmlspecialchars($flight['heure_arrivee']); ?></td>
                            <td><?php echo htmlspecialchars($flight['block_time']); ?></td>
                            <td style="max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;\"><?php echo htmlspecialchars($flight['note_du_vol']); ?></td>
                            <td><?php echo htmlspecialchars($flight['mission_libelle']); ?></td>
                            <td><?php echo number_format($flight['cout_vol'], 2) . ' €'; ?></td>
                            <td title="<?= htmlspecialchars($pirep_complet) ?>"><?= htmlspecialchars($pirep_court) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Popup modale pour détails du vol -->
        <div id="vol-modal" class="vol-modal" style="display:none;">
            <div class="vol-modal-content">
                <span class="vol-modal-close" id="vol-modal-close">&times;</span>
                <h3>Détails du vol</h3>
                <div id="vol-modal-body">
                    <!-- Les détails du vol seront injectés ici -->
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<style>
/* Sticky header flights (modèle tableau_vols.php) */
.table-header-fixed {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    table-layout: fixed;
    margin-bottom: 0;
}
.table-header-fixed th {
    background: #0d47a1;
    color: #fff;
    border-bottom: 2px solid #08306b;
    z-index: 10;
    box-shadow: 0 2px 4px rgba(0,0,0,0.03);
    padding: 8px 10px;
    text-align: center;
    font-weight: bold;
    letter-spacing: 0.5px;
    white-space: nowrap;
}
.table-scroll-wrapper {
    width: 100%;
    max-height: 60vh;
    overflow-y: auto;
    overflow-x: auto;
    border-top: none;
}
.table-skywings {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    table-layout: fixed;
}
.table-skywings td, .table-skywings th {
    padding: 8px 10px;
    text-align: center;
    min-width: 90px;
    box-sizing: border-box;
    white-space: nowrap;
}
.table-main-padding {
    padding-left: 32px;
}
.vol-modal {
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
.vol-modal-content {
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
.vol-modal-close {
    position: absolute;
    top: 12px;
    right: 18px;
    font-size: 2em;
    color: #0d47a1;
    cursor: pointer;
}
</style>

<script>
// Synchronisation du scroll horizontal de l'en-tête (modèle tableau_vols.php)
document.addEventListener('DOMContentLoaded', function() {
    var scrollWrapper = document.querySelector('.table-scroll-wrapper');
    var headerTable = document.querySelector('.table-header-fixed');
    if (scrollWrapper && headerTable) {
        scrollWrapper.addEventListener('scroll', function() {
            headerTable.scrollLeft = this.scrollLeft;
        });
    }

    // Gestion du popup détails vol (modèle tableau_vols.php)
    document.querySelectorAll('.vol-row').forEach(function(row) {
        row.addEventListener('click', function() {
            const details = JSON.parse(this.getAttribute('data-details'));
            let html = '<table style="width:100%;border-collapse:collapse;">';
            for (const key in details) {
                html += '<tr><td style="font-weight:bold;padding:4px 8px;color:#0d47a1;">' + key + '</td><td style="padding:4px 8px;">' + (details[key] ?? '') + '</td></tr>';
            }
            html += '</table>';
            document.getElementById('vol-modal-body').innerHTML = html;
            document.getElementById('vol-modal').style.display = 'flex';
        });
    });
    document.getElementById('vol-modal-close').onclick = function() {
        document.getElementById('vol-modal').style.display = 'none';
    };
    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') document.getElementById('vol-modal').style.display = 'none';
    });
    document.getElementById('vol-modal').addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
});
</script>
