<?php
/**
 * Page : rapport_grades.php
 * -------------------------
 * Affiche un rapport complet sur les grades des pilotes :
 * - Vue d'ensemble avec statistiques
 * - Tableau détaillé de tous les pilotes avec progression
 * - Liste des promotions imminentes
 * - Graphiques de répartition
 */
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/config.php';

// Vérifier que l'utilisateur est admin
if (!isset($_SESSION['user']['callsign'])) {
    header('Location: /login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT admin FROM PILOTES WHERE callsign = :callsign");
$stmt->execute(['callsign' => $_SESSION['user']['callsign']]);
$isAdmin = $stmt->fetchColumn();

if (!$isAdmin) {
    header('Location: /index.php');
    exit;
}

// Seuils de grades (à synchroniser avec le script de promotion)
$seuils_grades = [
    1 => 0,
    2 => 100,
    3 => 200,
    4 => 300,
    5 => 400
];

// Récupérer tous les grades
$stmtGrades = $pdo->query("SELECT id, nom FROM GRADES ORDER BY id ASC");
$grades = [];
while ($row = $stmtGrades->fetch(PDO::FETCH_ASSOC)) {
    $grades[$row['id']] = $row['nom'];
}

// Récupérer tous les pilotes avec leurs heures
$stmtPilotes = $pdo->query("
    SELECT 
        p.id,
        p.callsign,
        p.prenom,
        p.nom,
        p.grade_id,
        COALESCE(SUM(TIME_TO_SEC(cdvg.temps_vol)), 0) AS total_secondes
    FROM PILOTES p
    LEFT JOIN CARNET_DE_VOL_GENERAL cdvg ON p.id = cdvg.pilote_id
    GROUP BY p.id, p.callsign, p.prenom, p.nom, p.grade_id
    ORDER BY total_secondes DESC
");

$pilotes_data = [];
$stats_grades = array_fill_keys(array_keys($grades), 0);
$promotions_imminentes = [];

while ($pilote = $stmtPilotes->fetch(PDO::FETCH_ASSOC)) {
    $total_heures = $pilote['total_secondes'] / 3600;
    $grade_actuel = $pilote['grade_id'];
    $grade_nom = $grades[$grade_actuel] ?? 'Inconnu';
    
    // Calculer le prochain grade et progression
    $prochain_grade_id = null;
    $prochain_grade_nom = null;
    $heures_restantes = null;
    $progression = 100;
    
    foreach ($seuils_grades as $gid => $seuil) {
        if ($gid > $grade_actuel) {
            $prochain_grade_id = $gid;
            $prochain_grade_nom = $grades[$gid] ?? 'Inconnu';
            $heures_restantes = $seuil - $total_heures;
            
            // Calculer la progression entre le grade actuel et le prochain
            $seuil_actuel = $seuils_grades[$grade_actuel] ?? 0;
            $seuil_suivant = $seuil;
            if ($seuil_suivant > $seuil_actuel) {
                $progression = min(100, (($total_heures - $seuil_actuel) / ($seuil_suivant - $seuil_actuel)) * 100);
            }
            break;
        }
    }
    
    $pilotes_data[] = [
        'callsign' => $pilote['callsign'],
        'prenom' => $pilote['prenom'],
        'nom' => $pilote['nom'],
        'grade_id' => $grade_actuel,
        'grade_nom' => $grade_nom,
        'heures' => $total_heures,
        'prochain_grade_id' => $prochain_grade_id,
        'prochain_grade_nom' => $prochain_grade_nom,
        'heures_restantes' => $heures_restantes,
        'progression' => $progression
    ];
    
    $stats_grades[$grade_actuel]++;
    
    // Promotions imminentes (< 20h)
    if ($heures_restantes !== null && $heures_restantes > 0 && $heures_restantes < 20) {
        $promotions_imminentes[] = [
            'callsign' => $pilote['callsign'],
            'prenom' => $pilote['prenom'],
            'nom' => $pilote['nom'],
            'heures_restantes' => $heures_restantes,
            'prochain_grade' => $prochain_grade_nom
        ];
    }
}

// Trier les promotions imminentes par heures restantes
usort($promotions_imminentes, function($a, $b) {
    return $a['heures_restantes'] <=> $b['heures_restantes'];
});

// Calculer stats globales
$total_pilotes = count($pilotes_data);
$total_heures_vol = array_sum(array_column($pilotes_data, 'heures'));
$moyenne_heures = $total_pilotes > 0 ? $total_heures_vol / $total_pilotes : 0;

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>

<style>
.rapport-container {
    max-width: 1400px;
    margin: 20px auto;
    padding: 20px;
}

.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.stat-card.secondary {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-card.tertiary {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.stat-card .value {
    font-size: 32px;
    font-weight: bold;
    margin: 0;
}

.section-title {
    font-size: 24px;
    font-weight: bold;
    margin: 30px 0 15px 0;
    color: #1a202c;
    border-bottom: 3px solid #667eea;
    padding-bottom: 8px;
}

.table-wrapper {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
    margin-bottom: 30px;
}

.rapport-table {
    width: 100%;
    border-collapse: collapse;
}

.rapport-table thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.rapport-table th {
    padding: 15px 12px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.rapport-table tbody tr {
    border-bottom: 1px solid #e2e8f0;
    transition: background 0.2s;
}

.rapport-table tbody tr:hover {
    background: #f7fafc;
}

.rapport-table td {
    padding: 12px;
    font-size: 14px;
}

.badge-grade {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: white;
}

.grade-1 { background: #718096; }
.grade-2 { background: #48bb78; }
.grade-3 { background: #4299e1; }
.grade-4 { background: #ed8936; }
.grade-5 { background: #f56565; }

.progress-bar-container {
    width: 100%;
    height: 20px;
    background: #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    transition: width 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 11px;
    font-weight: bold;
}

.alert-promo {
    background: #fef5e7;
    border-left: 4px solid #f39c12;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-promo h4 {
    margin: 0 0 10px 0;
    color: #f39c12;
    font-size: 16px;
}

.promo-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.promo-list li {
    padding: 8px 0;
    border-bottom: 1px solid #fdebd0;
}

.promo-list li:last-child {
    border-bottom: none;
}

.grade-distribution {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 30px;
}

.grade-box {
    flex: 1;
    min-width: 150px;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    color: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.grade-box .grade-name {
    font-size: 12px;
    opacity: 0.9;
    margin-bottom: 5px;
}

.grade-box .grade-count {
    font-size: 28px;
    font-weight: bold;
}

.filter-bar {
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
    align-items: center;
}

.filter-bar input {
    padding: 8px 12px;
    border: 1px solid #cbd5e0;
    border-radius: 6px;
    font-size: 14px;
}

.filter-bar select {
    padding: 8px 12px;
    border: 1px solid #cbd5e0;
    border-radius: 6px;
    font-size: 14px;
}
</style>

<main>
    <div class="rapport-container">
        <h1 style="font-size: 32px; margin-bottom: 10px;">📊 Rapport des Grades et Progressions</h1>
        <p style="color: #718096; margin-bottom: 30px;">Vue d'ensemble des grades des pilotes et suivi des promotions</p>

        <!-- Stats Overview -->
        <div class="stats-overview">
            <div class="stat-card">
                <h3>Total Pilotes</h3>
                <p class="value"><?= $total_pilotes ?></p>
            </div>
            <div class="stat-card secondary">
                <h3>Heures de Vol Totales</h3>
                <p class="value"><?= number_format($total_heures_vol, 0) ?>h</p>
            </div>
            <div class="stat-card tertiary">
                <h3>Moyenne par Pilote</h3>
                <p class="value"><?= number_format($moyenne_heures, 1) ?>h</p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <h3>Promotions Imminentes</h3>
                <p class="value"><?= count($promotions_imminentes) ?></p>
            </div>
        </div>

        <!-- Répartition par grade -->
        <h2 class="section-title">Répartition par Grade</h2>
        <div class="grade-distribution">
            <?php 
            $grade_colors = [1 => '#718096', 2 => '#48bb78', 3 => '#4299e1', 4 => '#ed8936', 5 => '#f56565'];
            foreach ($grades as $gid => $gnom): 
                $count = $stats_grades[$gid] ?? 0;
                $color = $grade_colors[$gid] ?? '#718096';
            ?>
                <div class="grade-box" style="background: <?= $color ?>;">
                    <div class="grade-name"><?= htmlspecialchars($gnom) ?></div>
                    <div class="grade-count"><?= $count ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Promotions imminentes -->
        <?php if (count($promotions_imminentes) > 0): ?>
        <div class="alert-promo">
            <h4>⚠️ Promotions Imminentes (moins de 20h restantes)</h4>
            <ul class="promo-list">
                <?php foreach ($promotions_imminentes as $promo): ?>
                    <li>
                        <strong><?= htmlspecialchars($promo['callsign']) ?></strong> 
                        (<?= htmlspecialchars($promo['prenom'] . ' ' . $promo['nom']) ?>) 
                        → <strong><?= htmlspecialchars($promo['prochain_grade']) ?></strong> 
                        dans <strong style="color: #f39c12;"><?= number_format($promo['heures_restantes'], 1) ?>h</strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Tableau détaillé -->
        <h2 class="section-title">Détails par Pilote</h2>
        
        <div class="filter-bar">
            <input type="text" id="searchInput" placeholder="🔍 Rechercher un pilote...">
            <select id="gradeFilter">
                <option value="">Tous les grades</option>
                <?php foreach ($grades as $gid => $gnom): ?>
                    <option value="<?= $gid ?>"><?= htmlspecialchars($gnom) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="table-wrapper">
            <table class="rapport-table" id="pilotesTable">
                <thead>
                    <tr>
                        <th>Callsign</th>
                        <th>Nom</th>
                        <th>Grade Actuel</th>
                        <th style="text-align: right;">Heures de Vol</th>
                        <th>Prochain Grade</th>
                        <th style="text-align: right;">Heures Restantes</th>
                        <th style="min-width: 200px;">Progression</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pilotes_data as $p): ?>
                        <tr data-grade="<?= $p['grade_id'] ?>" data-callsign="<?= strtolower($p['callsign']) ?>" data-nom="<?= strtolower($p['prenom'] . ' ' . $p['nom']) ?>">
                            <td><strong><?= htmlspecialchars($p['callsign']) ?></strong></td>
                            <td><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?></td>
                            <td>
                                <span class="badge-grade grade-<?= $p['grade_id'] ?>">
                                    <?= htmlspecialchars($p['grade_nom']) ?>
                                </span>
                            </td>
                            <td style="text-align: right; font-weight: bold;"><?= number_format($p['heures'], 1) ?>h</td>
                            <td>
                                <?php if ($p['prochain_grade_nom']): ?>
                                    <span class="badge-grade grade-<?= $p['prochain_grade_id'] ?>">
                                        <?= htmlspecialchars($p['prochain_grade_nom']) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #a0aec0; font-style: italic;">Grade max</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <?php if ($p['heures_restantes'] !== null && $p['heures_restantes'] > 0): ?>
                                    <span style="color: <?= $p['heures_restantes'] < 10 ? '#f56565' : '#718096' ?>; font-weight: bold;">
                                        <?= number_format($p['heures_restantes'], 1) ?>h
                                    </span>
                                <?php else: ?>
                                    <span style="color: #a0aec0;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['heures_restantes'] !== null): ?>
                                    <div class="progress-bar-container">
                                        <div class="progress-bar" style="width: <?= number_format($p['progression'], 1) ?>%;">
                                            <?= number_format($p['progression'], 0) ?>%
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="progress-bar-container">
                                        <div class="progress-bar" style="width: 100%; background: #48bb78;">
                                            100%
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
// Filtres en temps réel
const searchInput = document.getElementById('searchInput');
const gradeFilter = document.getElementById('gradeFilter');
const table = document.getElementById('pilotesTable');
const rows = table.querySelectorAll('tbody tr');

function applyFilters() {
    const searchTerm = searchInput.value.toLowerCase();
    const selectedGrade = gradeFilter.value;
    
    rows.forEach(row => {
        const callsign = row.dataset.callsign;
        const nom = row.dataset.nom;
        const grade = row.dataset.grade;
        
        const matchesSearch = callsign.includes(searchTerm) || nom.includes(searchTerm);
        const matchesGrade = selectedGrade === '' || grade === selectedGrade;
        
        if (matchesSearch && matchesGrade) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

searchInput.addEventListener('input', applyFilters);
gradeFilter.addEventListener('change', applyFilters);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
