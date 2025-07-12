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
    <h2>Statistiques de vols par année</h2>
    <?php if (empty($statsParAn)): ?>
        <p>Aucune donnée disponible.</p>
    <?php else: ?>
        <table class="table-skywings">
            <thead>
                <tr>
                    <th>Année</th>
                    <th>Nombre de vols</th>
                    <th>Total heures de vol</th>
                    <th>Top 3 callsigns</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($statsParAn as $stat): $annee = $stat['annee']; ?>
                    <tr>
                        <td><?= htmlspecialchars($annee) ?></td>
                        <td><?= htmlspecialchars($stat['nb_vols']) ?></td>
                        <td><?= number_format($stat['total_heures'], 2, ',', ' ') ?></td>
                        <td>
                            <?php
                            if (isset($topCallsignsParAn[$annee])) {
                                echo implode(', ', array_map(fn($c) => htmlspecialchars($c['callsign']) . ' (' . $c['heures'] . 'h)', $topCallsignsParAn[$annee]));
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2>Top 20 aéroports les plus visités</h2>
    <table class="table-skywings">
        <thead>
            <tr>
                <th>Aéroport</th>
                <th>Visites</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($topAeroports as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['depart']) ?></td>
                    <td><?= htmlspecialchars($a['nb_visites']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Autres statistiques</h2>
    <ul>
        <li>La compagnie possède <strong><?= $nbAppareils ?></strong> appareils</li>
        <li>Nombre de destinations distinctes : <strong><?= $nbDestinations ?></strong></li>
        <li>Durée moyenne des vols : <strong><?= $dureeMoyenneVols ?> minutes</strong></li>
        <li>Appareil le plus utilisé : <strong><?= htmlspecialchars($appareilPlusUtilise['immat']) ?> (<?= $appareilPlusUtilise['nb'] ?> vols)</strong></li>
        <li>Appareil avec le plus d'heures de vol : <strong><?= htmlspecialchars($appareilPlusDHeures['immat']) ?> (<?= $appareilPlusDHeures['total_heures'] ?> heures)</strong></li>
        <li>Pilote le plus actif : <strong><?= htmlspecialchars($pilotePlusActif['callsign']) ?> (<?= $pilotePlusActif['heures'] ?> h)</strong></li>
        <li>Trajet le plus fréquent : <strong><?= htmlspecialchars($trajetFrequent['trajet']) ?> (<?= $trajetFrequent['nb'] ?> vols)</strong></li>
    </ul>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
