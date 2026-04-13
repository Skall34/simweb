<?php
/**
 * Carnet de vol par avion
 * Affiche l'historique complet d'un avion : vols et maintenances
 */
session_start();

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/require_login.php';

// Récupérer l'ID de l'avion
$appareil_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($appareil_id <= 0) {
    header('Location: fleet.php');
    exit;
}

// Récupérer les infos de l'avion
$stmtAvion = $pdo->prepare("
    SELECT f.*, ft.fleet_type AS type_nom, ft.type AS categorie, ft.cout_horaire, ft.cout_maintenance
    FROM FLOTTE f
    LEFT JOIN FLEET_TYPE ft ON f.fleet_type = ft.id
    WHERE f.id = :id
");
$stmtAvion->execute(['id' => $appareil_id]);
$avion = $stmtAvion->fetch(PDO::FETCH_ASSOC);

if (!$avion) {
    header('Location: fleet.php');
    exit;
}

// Statistiques globales
$stmtStats = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_vols,
        SUM(TIME_TO_SEC(temps_vol)) AS total_temps_sec,
        SUM(fuel_depart - fuel_arrivee) AS total_fuel,
        SUM(cout_vol) AS total_recettes,
        COUNT(DISTINCT pilote_id) AS nb_pilotes,
        MIN(date_vol) AS premier_vol,
        MAX(date_vol) AS dernier_vol
    FROM CARNET_DE_VOL_GENERAL
    WHERE appareil_id = :id
");
$stmtStats->execute(['id' => $appareil_id]);
$stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

// Total coûts maintenance
$totalMaintenance = 0;
$maintenances = [];
try {
    $stmtMaintCout = $pdo->prepare("
        SELECT COALESCE(SUM(cout), 0) AS total_maintenance
        FROM MAINTENANCES_LOG
        WHERE appareil_id = :id AND cout IS NOT NULL
    ");
    $stmtMaintCout->execute(['id' => $appareil_id]);
    $totalMaintenance = (float)$stmtMaintCout->fetchColumn();

    // Récupérer les maintenances
    $stmtMaint = $pdo->prepare("
        SELECT id, date_maintenance, type_maintenance, etat_avant, etat_apres, cout, commentaire
        FROM MAINTENANCES_LOG
        WHERE appareil_id = :id
        ORDER BY date_maintenance DESC
    ");
    $stmtMaint->execute(['id' => $appareil_id]);
    $maintenances = $stmtMaint->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table MAINTENANCES_LOG n'existe pas encore - ignorer
    $totalMaintenance = 0;
    $maintenances = [];
}

// Récupérer les vols
$stmtVols = $pdo->prepare("
    SELECT 
        cvg.id, cvg.date_vol, cvg.depart, cvg.destination, cvg.temps_vol,
        cvg.fuel_depart, cvg.fuel_arrivee, cvg.payload, cvg.cout_vol,
        cvg.note_du_vol, cvg.pirep_maintenance, cvg.heure_depart, cvg.heure_arrivee,
        p.callsign AS pilote_callsign,
        m.libelle AS mission_nom
    FROM CARNET_DE_VOL_GENERAL cvg
    LEFT JOIN PILOTES p ON cvg.pilote_id = p.id
    LEFT JOIN MISSIONS m ON cvg.mission_id = m.id
    WHERE cvg.appareil_id = :id
    ORDER BY cvg.date_vol DESC, cvg.heure_depart DESC
");
$stmtVols->execute(['id' => $appareil_id]);
$vols = $stmtVols->fetchAll(PDO::FETCH_ASSOC);

// Fusionner vols et maintenances dans une timeline
$timeline = [];

foreach ($vols as $vol) {
    $datetime = $vol['date_vol'];
    if (!empty($vol['heure_depart'])) {
        $datetime .= ' ' . $vol['heure_depart'];
    }
    $timeline[] = [
        'type' => 'vol',
        'datetime' => $datetime,
        'data' => $vol
    ];
}

foreach ($maintenances as $maint) {
    $timeline[] = [
        'type' => 'maintenance',
        'datetime' => $maint['date_maintenance'],
        'data' => $maint
    ];
}

// Trier par date décroissante
usort($timeline, function($a, $b) {
    return strcmp($b['datetime'], $a['datetime']);
});

// Pilotes ayant utilisé cet avion
$stmtPilotes = $pdo->prepare("
    SELECT p.callsign, COUNT(*) AS nb_vols, SUM(TIME_TO_SEC(cvg.temps_vol)) AS temps_total_sec
    FROM CARNET_DE_VOL_GENERAL cvg
    JOIN PILOTES p ON cvg.pilote_id = p.id
    WHERE cvg.appareil_id = :id
    GROUP BY cvg.pilote_id, p.callsign
    ORDER BY nb_vols DESC
");
$stmtPilotes->execute(['id' => $appareil_id]);
$pilotes = $stmtPilotes->fetchAll(PDO::FETCH_ASSOC);

// Fonction pour formater le temps de vol (secondes -> HH:MM ou TIME -> HH:MM)
function formatTempsVol($value) {
    if (!$value) return '0:00';
    // Si c'est un format TIME (HH:MM:SS), extraire heures et minutes
    if (is_string($value) && strpos($value, ':') !== false) {
        $parts = explode(':', $value);
        $h = (int)$parts[0];
        $m = (int)($parts[1] ?? 0);
        return $h . ':' . str_pad($m, 2, '0', STR_PAD_LEFT);
    }
    // Sinon c'est des secondes
    $totalMinutes = floor($value / 60);
    $h = floor($totalMinutes / 60);
    $m = $totalMinutes % 60;
    return $h . ':' . str_pad($m, 2, '0', STR_PAD_LEFT);
}

// Fonction pour afficher les étoiles
function renderStars($note) {
    if ($note === null || $note === '') return '';
    $note = (int)$note;
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= ($i <= $note) ? '★' : '☆';
    }
    return '<span class="stars">' . $stars . '</span>';
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';

// Statut texte
$statusLabels = [
    0 => t('fleet_status_ok'),
    1 => t('fleet_status_maintenance'),
    2 => t('fleet_status_crash'),
    3 => t('fleet_status_vendu')
];
$statusClasses = [
    0 => 'status-ok',
    1 => 'status-maintenance',
    2 => 'status-crash',
    3 => 'status-vendu'
];

?>

<main>
    <!-- En-tête avion -->
    <div class="carnet-header">
        <div class="carnet-header-left">
            <h2>
                <a href="fleet.php" class="back-link">← <?= t('fleet_title') ?></a>
            </h2>
            <h1 class="carnet-title">
                ✈️ <?= t('carnet_avion_title') ?> — <?= htmlspecialchars($avion['immat']) ?>
            </h1>
            <p class="carnet-subtitle">
                <?= htmlspecialchars($avion['type_nom'] ?? '') ?>
                <?php if (!empty($avion['categorie'])): ?>
                    <span class="badge badge-category"><?= htmlspecialchars($avion['categorie']) ?></span>
                <?php endif; ?>
            </p>
        </div>
        <div class="carnet-header-right">
            <div class="carnet-info-item">
                <span class="label"><?= t('fleet_table_hub') ?></span>
                <span class="value"><?= htmlspecialchars($avion['hub'] ?? '—') ?></span>
            </div>
            <div class="carnet-info-item">
                <span class="label"><?= t('fleet_table_localisation') ?></span>
                <span class="value"><?= htmlspecialchars($avion['localisation'] ?? '—') ?></span>
            </div>
            <div class="carnet-info-item">
                <span class="label"><?= t('fleet_table_etat') ?></span>
                <span class="value"><?= (int)$avion['etat'] ?>%</span>
            </div>
            <div class="carnet-info-item">
                <span class="label"><?= t('fleet_table_status') ?></span>
                <span class="value <?= $statusClasses[(int)$avion['status']] ?? '' ?>">
                    <?= $statusLabels[(int)$avion['status']] ?? '?' ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="carnet-stats">
        <div class="stat-card">
            <div class="stat-value"><?= (int)$stats['total_vols'] ?></div>
            <div class="stat-label"><?= t('carnet_stat_vols') ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= formatTempsVol($stats['total_temps_sec']) ?></div>
            <div class="stat-label"><?= t('carnet_stat_heures') ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= number_format((float)$stats['total_fuel'], 0, ',', ' ') ?> kg</div>
            <div class="stat-label"><?= t('carnet_stat_fuel') ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= number_format((float)$stats['total_recettes'], 0, ',', ' ') ?> €</div>
            <div class="stat-label"><?= t('carnet_stat_recettes') ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= (int)$avion['nb_maintenance'] ?></div>
            <div class="stat-label"><?= t('carnet_stat_maintenances') ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= number_format($totalMaintenance, 0, ',', ' ') ?> €</div>
            <div class="stat-label"><?= t('carnet_stat_cout_maint') ?></div>
        </div>
    </div>

    <!-- Timeline -->
    <h3 class="section-title"><?= t('carnet_timeline_title') ?></h3>
    
    <?php if (empty($timeline)): ?>
        <p class="empty-message"><?= t('carnet_no_events') ?></p>
    <?php else: ?>
        <div class="carnet-timeline">
            <?php foreach ($timeline as $event): ?>
                <?php if ($event['type'] === 'vol'): ?>
                    <?php $vol = $event['data']; ?>
                    <div class="timeline-item timeline-vol">
                        <span class="tl-date">📅 <?= date('d/m/Y', strtotime($event['datetime'])) ?></span>
                        <span class="tl-route">✈️ <strong><?= htmlspecialchars($vol['depart']) ?></strong> → <strong><?= htmlspecialchars($vol['destination']) ?></strong></span>
                        <span class="tl-info">⏱️ <?= formatTempsVol($vol['temps_vol']) ?></span>
                        <span class="tl-info">👤 <?= htmlspecialchars($vol['pilote_callsign'] ?? '?') ?></span>
                        <span class="tl-info">📦 <?= number_format((float)$vol['payload'], 0, ',', ' ') ?> kg</span>
                        <span class="tl-info tl-money">💰 <?= number_format((float)$vol['cout_vol'], 0, ',', ' ') ?> €</span>
                        <?php if ($vol['note_du_vol'] !== null && $vol['note_du_vol'] !== ''): ?>
                            <span class="tl-note">⭐ <?= (int)$vol['note_du_vol'] ?>/10</span>
                        <?php endif; ?>
                        <?php if (!empty($vol['pirep_maintenance'])): ?>
                            <span class="tl-pirep" title="<?= htmlspecialchars($vol['pirep_maintenance']) ?>">ℹ️</span>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php 
                        $maint = $event['data'];
                        $maintTypes = [
                            'usure' => ['label' => t('carnet_maint_usure'), 'class' => 'tl-maint-usure'],
                            'crash' => ['label' => t('carnet_maint_crash'), 'class' => 'tl-maint-crash'],
                            'sortie' => ['label' => t('carnet_maint_sortie'), 'class' => 'tl-maint-sortie'],
                            'sortie_crash' => ['label' => t('carnet_maint_sortie_crash'), 'class' => 'tl-maint-sortie']
                        ];
                        $maintInfo = $maintTypes[$maint['type_maintenance']] ?? ['label' => $maint['type_maintenance'], 'class' => ''];
                    ?>
                    <div class="timeline-item timeline-maintenance">
                        <span class="tl-date"><?= date('d/m/Y', strtotime($event['datetime'])) ?></span>
                        <span class="tl-maint-badge <?= $maintInfo['class'] ?>">🔧 <?= $maintInfo['label'] ?></span>
                        <?php if ($maint['etat_avant'] !== null && $maint['etat_apres'] !== null): ?>
                            <span class="tl-info"><?= (int)$maint['etat_avant'] ?>% → <?= (int)$maint['etat_apres'] ?>%</span>
                        <?php endif; ?>
                        <?php if ($maint['cout'] !== null && $maint['cout'] > 0): ?>
                            <span class="tl-info tl-cost">-<?= number_format((float)$maint['cout'], 0, ',', ' ') ?> €</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Pilotes -->
    <?php if (!empty($pilotes)): ?>
        <h3 class="section-title"><?= t('carnet_pilotes_title') ?> (<?= count($pilotes) ?>)</h3>
        <div class="carnet-pilotes">
            <?php foreach ($pilotes as $pilote): ?>
                <div class="pilote-chip">
                    <span class="pilote-callsign"><?= htmlspecialchars($pilote['callsign']) ?></span>
                    <span class="pilote-stats"><?= (int)$pilote['nb_vols'] ?> <?= t('carnet_vols') ?> · <?= formatTempsVol($pilote['temps_total_sec']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
