<?php
session_start();

require __DIR__ . '/../includes/db_connect.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

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
    die("Erreur stats par an: " . $e->getMessage());
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
    die("Erreur top callsigns: " . $e->getMessage());
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
    die("Erreur top aéroports: " . $e->getMessage());
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
    die("Erreur stats complémentaires: " . $e->getMessage());
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>


<main>
    <h2 style="margin-bottom: 1.5em;">Statistiques générales de la compagnie</h2>

    <!-- Cartes synthétiques -->
    <div style="display: flex; flex-wrap: wrap; gap: 24px; margin-bottom: 2.5em;">
        <div class="stat-card"><div class="stat-label">Appareils actifs</div><div class="stat-value"><?= $nbAppareils ?></div></div>
        <div class="stat-card"><div class="stat-label">Destinations</div><div class="stat-value"><?= $nbDestinations ?></div></div>
        <div class="stat-card"><div class="stat-label">Durée moyenne d'un vol</div><div class="stat-value"><?= $dureeMoyenneVols ?> min</div></div>
        <div class="stat-card"><div class="stat-label">Appareil le plus utilisé</div><div class="stat-value"><?= htmlspecialchars($appareilPlusUtilise['immat']) ?><br><span class="stat-sub">(<?= $appareilPlusUtilise['nb'] ?> vols)</span></div></div>
        <div class="stat-card"><div class="stat-label">Appareil ayant le plus d'heures</div><div class="stat-value"><?= htmlspecialchars($appareilPlusDHeures['immat']) ?><br><span class="stat-sub">(<?= $appareilPlusDHeures['total_heures'] ?> h)</span></div></div>
        <div class="stat-card"><div class="stat-label">Pilote le plus actif</div><div class="stat-value"><?= htmlspecialchars($pilotePlusActif['callsign']) ?><br><span class="stat-sub">(<?= $pilotePlusActif['heures'] ?> h)</span></div></div>
        <div class="stat-card"><div class="stat-label">Trajet le plus fréquent</div><div class="stat-value"><?= htmlspecialchars($trajetFrequent['trajet']) ?><br><span class="stat-sub">(<?= $trajetFrequent['nb'] ?> vols)</span></div></div>
    </div>


    <!-- Graphique Vols par année + état flotte par immat -->
    <div style="display:flex; flex-wrap:wrap; gap:40px; align-items:flex-start; margin-bottom:2.5em;">
        <div style="flex:1; min-width:320px; max-width:700px;">
            <h3 style="margin-bottom:0.5em;">Évolution du nombre de vols par année</h3>
            <canvas id="chartVolsParAn" height="120"></canvas>
        </div>
        <div style="flex:3; min-width:600px; max-width:1400px;">
            <h3 style="margin-bottom:0.5em;">État de chaque appareil (par immatriculation)</h3>
            <canvas id="chartEtatFlotte" height="100"></canvas>
        </div>
    </div>

    <!-- Top 10 pilotes par heures de vol -->
    <div style="display:flex; flex-wrap:wrap; gap:40px; align-items:flex-start; margin-bottom:2.5em;">
        <div style="flex:1; min-width:320px;">
            <h3>Top 10 pilotes (heures de vol)</h3>
            <table class="table-skywings">
                <thead><tr><th>Callsign</th><th>Heures</th></tr></thead>
                <tbody>
                <?php
                $topPilotes = $pdo->query("SELECT p.callsign, ROUND(SUM(TIME_TO_SEC(temps_vol)/3600),1) AS heures FROM CARNET_DE_VOL_GENERAL c JOIN PILOTES p ON c.pilote_id = p.id GROUP BY p.callsign ORDER BY heures DESC LIMIT 10")->fetchAll();
                foreach ($topPilotes as $p): ?>
                    <tr><td><?= htmlspecialchars($p['callsign']) ?></td><td><?= $p['heures'] ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="flex:1; min-width:320px;">
            <h3>Top 10 appareils (heures de vol)</h3>
            <table class="table-skywings">
                <thead><tr><th>Immat</th><th>Heures</th></tr></thead>
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

    <!-- Top 20 aéroports les plus visités + graphique -->
    <div style="display:flex; flex-wrap:wrap; gap:40px; align-items:flex-start; margin-bottom:2.5em;">
        <div style="flex:1; min-width:320px;">
            <h3>Top 20 aéroports les plus visités</h3>
            <table class="table-skywings">
                <thead><tr><th>Aéroport</th><th>Visites</th></tr></thead>
                <tbody>
                <?php foreach ($topAeroports as $a): ?>
                    <tr><td><?= htmlspecialchars($a['depart']) ?></td><td><?= htmlspecialchars($a['nb_visites']) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="flex:1; min-width:320px;">
            <h3>Répartition des visites par aéroport</h3>
            <canvas id="chartAeroports" height="220"></canvas>
        </div>
    </div>

    <!-- Section records -->
    <div style="margin-bottom:2.5em;">
        <h3>Records de la compagnie</h3>
        <ul>
            <?php
            // Vol le plus long (durée)
            $volLong = $pdo->query("SELECT c.id, p.callsign, f.immat, c.depart, c.destination, TIMEDIFF(c.heure_arrivee, c.heure_depart) AS duree FROM CARNET_DE_VOL_GENERAL c LEFT JOIN PILOTES p ON c.pilote_id=p.id LEFT JOIN FLOTTE f ON c.appareil_id=f.id ORDER BY TIMEDIFF(c.heure_arrivee, c.heure_depart) DESC LIMIT 1")->fetch();
            // Vol le plus court
            $volCourt = $pdo->query("SELECT c.id, p.callsign, f.immat, c.depart, c.destination, TIMEDIFF(c.heure_arrivee, c.heure_depart) AS duree FROM CARNET_DE_VOL_GENERAL c LEFT JOIN PILOTES p ON c.pilote_id=p.id LEFT JOIN FLOTTE f ON c.appareil_id=f.id WHERE TIMEDIFF(c.heure_arrivee, c.heure_depart) > 0 ORDER BY TIMEDIFF(c.heure_arrivee, c.heure_depart) ASC LIMIT 1")->fetch();
            // Moyenne vols par mois
            $volsParMois = $pdo->query("SELECT COUNT(*)/COUNT(DISTINCT CONCAT(YEAR(date_vol),'-',MONTH(date_vol))) AS moy FROM CARNET_DE_VOL_GENERAL")->fetchColumn();
            ?>
            <li>Vol le plus long : <strong><?= htmlspecialchars($volLong['callsign']) ?>, <?= htmlspecialchars($volLong['immat']) ?>, <?= htmlspecialchars($volLong['depart']) ?> → <?= htmlspecialchars($volLong['destination']) ?> (<?= $volLong['duree'] ?>)</strong></li>
            <li>Vol le plus court : <strong><?= htmlspecialchars($volCourt['callsign']) ?>, <?= htmlspecialchars($volCourt['immat']) ?>, <?= htmlspecialchars($volCourt['depart']) ?> → <?= htmlspecialchars($volCourt['destination']) ?> (<?= $volCourt['duree'] ?>)</strong></li>
            <li>Moyenne de vols par mois : <strong><?= number_format($volsParMois,1,',',' ') ?></strong></li>
        </ul>
    </div>

    <!-- Styles pour stats -->
    <style>
        .stat-card {
            background: #f7fbff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            padding: 18px 28px;
            min-width: 180px;
            flex: 1 1 180px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .stat-label {
            color: #0d47a1;
            font-size: 1.1em;
            margin-bottom: 6px;
            font-weight: 600;
        }
        .stat-value {
            font-size: 2.1em;
            font-weight: bold;
            color: #222;
        }
        .stat-sub {
            font-size: 0.95em;
            color: #555;
        }
        .table-skywings th, .table-skywings td {
            padding: 4px 8px;
            font-size: 14px;
        }
        .table-skywings th {
            background: #0d47a1;
            color: #fff;
            font-weight: 600;
        }
        .table-skywings tr:nth-child(even) td {
            background: #f7fbff;
        }
    </style>

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
        $flotte = $pdo->query("SELECT immat, etat FROM FLOTTE ORDER BY immat ASC")->fetchAll(PDO::FETCH_ASSOC);
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

    // Graphique aéroports les plus visités
    const ctxAero = document.getElementById('chartAeroports').getContext('2d');
    const dataAero = {
        labels: <?= json_encode(array_column($topAeroports, 'depart')) ?>,
        datasets: [{
            label: 'Visites',
            data: <?= json_encode(array_column($topAeroports, 'nb_visites')) ?>,
            backgroundColor: '#1976d2',
        }]
    };
    new Chart(ctxAero, {
        type: 'bar',
        data: dataAero,
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            indexAxis: 'y',
            scales: { x: { beginAtZero: true } }
        }
    });
    </script>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
