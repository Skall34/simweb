<?php
session_start();

require_once __DIR__ . '/../includes/db_connect.php';

require_once __DIR__ . '/../includes/require_login.php';

// 1. Statistiques de vols par année
try {
    $sqlStats = "
        SELECT 
            YEAR(date_vol) AS annee,
            COUNT(*) AS nb_vols,
            ROUND(SUM(TIME_TO_SEC(TIMEDIFF(heure_arrivee, heure_depart))) / 3600, 2) AS total_heures
        FROM CARNET_DE_VOL_GENERAL
        GROUP BY annee
        ORDER BY annee DESC
    ";
    $stmtStats = $pdo->query($sqlStats);
    $statsParAn = $stmtStats->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die(t('stats_error_par_an') . " " . $e->getMessage());
}

// 1b. Top 3 callsigns par heures de vol
try {
    $sqlTopCallsigns = "
        SELECT 
            YEAR(c.date_vol) AS annee,
            p.callsign,
            SUM(TIME_TO_SEC(TIMEDIFF(c.heure_arrivee, c.heure_depart))) AS total_secondes
        FROM CARNET_DE_VOL_GENERAL c
        LEFT JOIN PILOTES p ON c.pilote_id = p.id
        GROUP BY annee, p.callsign
        ORDER BY annee DESC, total_secondes DESC
    ";
    $stmtTopCallsigns = $pdo->query($sqlTopCallsigns);
    $topCallsignsRaw = $stmtTopCallsigns->fetchAll(PDO::FETCH_ASSOC);

    $topCallsignsParAn = [];
    foreach ($topCallsignsRaw as $row) {
        $annee = $row['annee'];
        if (!isset($topCallsignsParAn[$annee])) {
            $topCallsignsParAn[$annee] = [];
        }
        if (count($topCallsignsParAn[$annee]) < 3) {
            $row['heures'] = round($row['total_secondes'] / 3600, 1);
            $topCallsignsParAn[$annee][] = $row;
        }
    }
} catch (PDOException $e) {
    die(t('stats_error_top_callsigns') . " " . $e->getMessage());
}

// 2. Top 20 aéroports les plus visités
try {
    $sqlTopAeroports = "
        SELECT 
            depart,
            COUNT(*) AS nb_visites
        FROM CARNET_DE_VOL_GENERAL
        GROUP BY depart
        ORDER BY nb_visites DESC
        LIMIT 20
    ";
    $stmtTopAeroports = $pdo->query($sqlTopAeroports);
    $topAeroports = $stmtTopAeroports->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die(t('stats_error_top_airports') . " " . $e->getMessage());
}

// 3. Statistiques complémentaires
try {
    // Nombre d'appareils
    $nbAppareils = $pdo->query("SELECT COUNT(*) FROM FLOTTE WHERE actif=1")->fetchColumn();

    // Nombre de destinations distinctes
    $nbDestinations = $pdo->query("SELECT COUNT(DISTINCT destination) FROM CARNET_DE_VOL_GENERAL")->fetchColumn();

    // Durée moyenne des vols
    $dureeMoyenneVols = $pdo->query("SELECT ROUND(AVG(TIME_TO_SEC(TIMEDIFF(heure_arrivee, heure_depart))) / 60, 1) FROM CARNET_DE_VOL_GENERAL")->fetchColumn();

    // Appareil le plus utilisé (immat)
    $appareilPlusUtilise = $pdo->query("SELECT f.immat, COUNT(*) AS nb FROM CARNET_DE_VOL_GENERAL c JOIN FLOTTE f ON c.appareil_id = f.id GROUP BY f.immat ORDER BY nb DESC LIMIT 1")
        ->fetch(PDO::FETCH_ASSOC);

    // Appareil avec le plus d'heures de vol (immat)
    $appareilPlusDHeures = $pdo->query("SELECT f.immat, ROUND(SUM(TIME_TO_SEC(TIMEDIFF(c.heure_arrivee, c.heure_depart))/3600), 1) AS total_heures FROM CARNET_DE_VOL_GENERAL c JOIN FLOTTE f ON c.appareil_id = f.id GROUP BY f.immat ORDER BY total_heures DESC LIMIT 1")
        ->fetch(PDO::FETCH_ASSOC);

    // Pilote le plus actif
    $pilotePlusActif = $pdo->query("SELECT p.callsign, ROUND(SUM(TIME_TO_SEC(TIMEDIFF(c.heure_arrivee, c.heure_depart))/3600), 1) AS heures FROM CARNET_DE_VOL_GENERAL c JOIN PILOTES p ON c.pilote_id = p.id GROUP BY p.callsign ORDER BY heures DESC LIMIT 1")
        ->fetch(PDO::FETCH_ASSOC);

    // Trajet le plus fréquent
    $trajetFrequent = $pdo->query("SELECT CONCAT(depart, ' → ', destination) AS trajet, COUNT(*) AS nb FROM CARNET_DE_VOL_GENERAL GROUP BY trajet ORDER BY nb DESC LIMIT 1")
        ->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die(t('stats_error_complementaires') . " " . $e->getMessage());
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>


<main>
    <h2 class="stats-title"><?= t('stats_title') ?></h2>

    <!-- Cartes synthétiques -->
    <div class="stats-cards-container">
        <div class="stat-card"><div class="stat-label"><?= t('stats_card_active_planes') ?></div><div class="stat-value"><?= $nbAppareils ?></div></div>
        <div class="stat-card"><div class="stat-label"><?= t('stats_card_destinations') ?></div><div class="stat-value"><?= $nbDestinations ?></div></div>
        <div class="stat-card"><div class="stat-label"><?= t('stats_card_avg_flight_duration') ?></div><div class="stat-value"><?= $dureeMoyenneVols ?> <?= t('stats_card_minutes') ?></div></div>
        <div class="stat-card"><div class="stat-label"><?= t('stats_card_most_used_plane') ?></div><div class="stat-value"><?= $appareilPlusUtilise ? htmlspecialchars($appareilPlusUtilise['immat']) : t('stats_no_data') ?><?php if ($appareilPlusUtilise): ?><br><span class="stat-sub">(<?= $appareilPlusUtilise['nb'] ?> <?= t('stats_card_flights') ?>)</span><?php endif; ?></div></div>
        <div class="stat-card"><div class="stat-label"><?= t('stats_card_most_hours_plane') ?></div><div class="stat-value"><?= $appareilPlusDHeures ? htmlspecialchars($appareilPlusDHeures['immat']) : t('stats_no_data') ?><?php if ($appareilPlusDHeures): ?><br><span class="stat-sub">(<?= $appareilPlusDHeures['total_heures'] ?> <?= t('stats_card_hours') ?>)</span><?php endif; ?></div></div>
        <div class="stat-card"><div class="stat-label"><?= t('stats_card_most_active_pilot') ?></div><div class="stat-value"><?= $pilotePlusActif ? htmlspecialchars($pilotePlusActif['callsign']) : t('stats_no_data') ?><?php if ($pilotePlusActif): ?><br><span class="stat-sub">(<?= $pilotePlusActif['heures'] ?> <?= t('stats_card_hours') ?>)</span><?php endif; ?></div></div>
        <div class="stat-card"><div class="stat-label"><?= t('stats_card_most_frequent_route') ?></div><div class="stat-value"><?= $trajetFrequent ? htmlspecialchars($trajetFrequent['trajet']) : t('stats_no_data') ?><?php if ($trajetFrequent): ?><br><span class="stat-sub">(<?= $trajetFrequent['nb'] ?> <?= t('stats_card_flights') ?>)</span><?php endif; ?></div></div>
    </div>


    <!-- Graphique Vols par année + état flotte par immat -->
    <div class="stats-charts-row">
        <div class="stats-chart-flights">
            <h3 class="stats-chart-title"><?= t('stats_evolution_flights_per_year') ?></h3>
            <canvas id="chartVolsParAn" height="120"></canvas>
        </div>
        <div class="stats-chart-fleet">
            <h3 class="stats-chart-title"><?= t('stats_fleet_status_by_immat') ?></h3>
            <canvas id="chartEtatFlotte" height="100"></canvas>
        </div>
    </div>

    <!-- Top 10 pilotes par heures de vol -->
    <div class="stats-tables-row">
        <div class="stats-table-container">
            <h3><?= t('stats_top10_pilots') ?></h3>
            <table class="table-skywings">
                <thead><tr><th><?= t('stats_table_callsign') ?></th><th><?= t('stats_table_hours') ?></th></tr></thead>
                <tbody>
                <?php
                $topPilotes = $pdo->query("SELECT p.callsign, ROUND(SUM(TIME_TO_SEC(temps_vol)/3600),1) AS heures FROM CARNET_DE_VOL_GENERAL c JOIN PILOTES p ON c.pilote_id = p.id GROUP BY p.callsign ORDER BY heures DESC LIMIT 10")->fetchAll();
                foreach ($topPilotes as $p): ?>
                    <tr><td><?= htmlspecialchars($p['callsign']) ?></td><td><?= $p['heures'] ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="stats-table-container">
            <h3><?= t('stats_top10_planes') ?></h3>
            <table class="table-skywings">
                <thead><tr><th><?= t('stats_table_immat') ?></th><th><?= t('stats_table_hours') ?></th></tr></thead>
                <tbody>
                <?php
                $topAppareils = $pdo->query("SELECT f.immat, ROUND(SUM(TIME_TO_SEC(TIMEDIFF(c.heure_arrivee, c.heure_depart))/3600),1) AS heures FROM CARNET_DE_VOL_GENERAL c JOIN FLOTTE f ON c.appareil_id = f.id GROUP BY f.immat ORDER BY heures DESC LIMIT 10")->fetchAll();
                foreach ($topAppareils as $a): ?>
                    <tr><td><?= htmlspecialchars($a['immat']) ?></td><td><?= $a['heures'] ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top 20 aéroports les plus visités + records -->
    <div class="stats-tables-row">
        <div class="stats-table-container">
            <h3><?= t('stats_top20_airports') ?></h3>
            <table class="table-skywings">
                <thead><tr><th><?= t('stats_table_airport') ?></th><th><?= t('stats_table_visits') ?></th></tr></thead>
                <tbody>
                <?php foreach ($topAeroports as $a): ?>
                    <tr><td><?= htmlspecialchars($a['depart']) ?></td><td><?= htmlspecialchars($a['nb_visites']) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="stats-records-container">
            <h3 class="stats-records-title"><?= t('stats_company_records') ?></h3>
            <ul class="stats-records-list">
                <?php
                $volLong = $pdo->query("SELECT c.id, p.callsign, f.immat, c.depart, c.destination, TIMEDIFF(c.heure_arrivee, c.heure_depart) AS duree FROM CARNET_DE_VOL_GENERAL c LEFT JOIN PILOTES p ON c.pilote_id=p.id LEFT JOIN FLOTTE f ON c.appareil_id=f.id ORDER BY TIMEDIFF(c.heure_arrivee, c.heure_depart) DESC LIMIT 1")->fetch();
                $volCourt = $pdo->query("SELECT c.id, p.callsign, f.immat, c.depart, c.destination, TIMEDIFF(c.heure_arrivee, c.heure_depart) AS duree FROM CARNET_DE_VOL_GENERAL c LEFT JOIN PILOTES p ON c.pilote_id=p.id LEFT JOIN FLOTTE f ON c.appareil_id=f.id WHERE TIMEDIFF(c.heure_arrivee, c.heure_depart) > 0 ORDER BY TIMEDIFF(c.heure_arrivee, c.heure_depart) ASC LIMIT 1")->fetch();
                $volsParMois = $pdo->query("SELECT COUNT(*)/COUNT(DISTINCT CONCAT(YEAR(date_vol),'-',MONTH(date_vol))) AS moy FROM CARNET_DE_VOL_GENERAL")->fetchColumn();
                ?>
                <?php if ($volLong): ?>
                <li>🕑 <strong><?= t('stats_record_longest_flight') ?></strong><br><span class="stats-record-value"> <?= htmlspecialchars($volLong['callsign']) ?>, <?= htmlspecialchars($volLong['immat']) ?>, <?= htmlspecialchars($volLong['depart']) ?> → <?= htmlspecialchars($volLong['destination']) ?> (<?= $volLong['duree'] ?>)</span></li>
                <?php else: ?>
                <li>🕑 <strong><?= t('stats_record_longest_flight') ?></strong><br><span class="stats-record-value"><?= t('stats_no_data') ?></span></li>
                <?php endif; ?>
                <?php if ($volCourt): ?>
                <li>⚡ <strong><?= t('stats_record_shortest_flight') ?></strong><br><span class="stats-record-value"> <?= htmlspecialchars($volCourt['callsign']) ?>, <?= htmlspecialchars($volCourt['immat']) ?>, <?= htmlspecialchars($volCourt['depart']) ?> → <?= htmlspecialchars($volCourt['destination']) ?> (<?= $volCourt['duree'] ?>)</span></li>
                <?php else: ?>
                <li>⚡ <strong><?= t('stats_record_shortest_flight') ?></strong><br><span class="stats-record-value"><?= t('stats_no_data') ?></span></li>
                <?php endif; ?>
                <li>📈 <strong><?= t('stats_record_avg_flights_per_month') ?></strong><br><span class="stats-record-value"> <?= $volsParMois ? number_format($volsParMois,1,',',' ') : t('stats_no_data') ?></span></li>
            </ul>
        </div>
    </div>
    <!-- Fin records -->


    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Graphique Vols par année
    const ctxVols = document.getElementById('chartVolsParAn').getContext('2d');
    const dataVols = {
        labels: <?= json_encode(array_column($statsParAn, 'annee')) ?>,
        datasets: [{
            label: 'Nombre de vols',
            data: <?= json_encode(array_column($statsParAn, 'nb_vols')) ?>,
            backgroundColor: '#1976d2',
        }]
    };
    new Chart(ctxVols, {
        type: 'bar',
        data: dataVols,
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Graphique état de chaque appareil (par immatriculation)
    const ctxEtat = document.getElementById('chartEtatFlotte').getContext('2d');
    const etatFlotteData = <?php
        $flotte = $pdo->query("SELECT immat, etat FROM FLOTTE WHERE actif = 1 ORDER BY immat ASC")->fetchAll(PDO::FETCH_ASSOC);
        $immats = array_column($flotte, 'immat');
        $etats = array_column($flotte, 'etat');
        echo json_encode(['immats' => $immats, 'etats' => $etats]);
    ?>;
    new Chart(ctxEtat, {
        type: 'bar',
        data: {
            labels: etatFlotteData.immats,
            datasets: [{
                label: 'État',
                data: etatFlotteData.etats,
                backgroundColor: '#ffa000',
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            indexAxis: 'x',
            scales: {
                y: {
                    beginAtZero: false,
                    ticks: {
                        callback: function(value) { return value; },
                        font: { size: 13 }
                    },
                    title: { display: true, text: 'État (texte)' }
                },
                x: {
                    ticks: { font: { size: 11 }, autoSkip: false, maxRotation: 90, minRotation: 60 },
                    title: { display: true, text: 'Immatriculation' }
                }
            },
            animation: false
        }
    });
    </script>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
