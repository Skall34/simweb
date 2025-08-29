<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';

// Récupérer la liste des avions
$stmt = $pdo->query("SELECT f.id, f.immat, ft.fleet_type FROM FLOTTE f LEFT JOIN FLEET_TYPE ft ON f.fleet_type = ft.id ORDER BY f.immat");
$avions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$gain_net = null;
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $avion_id = (int)($_POST['avion_id'] ?? 0);
    $payload = (float)($_POST['payload'] ?? 0);
    $distance = (float)($_POST['distance'] ?? 0);
    $temps_vol = (float)($_POST['temps_vol'] ?? 0);

    // Récupérer les infos de l'avion
    $stmt = $pdo->prepare("SELECT f.immat, ft.fleet_type, ft.cout_horaire FROM FLOTTE f LEFT JOIN FLEET_TYPE ft ON f.fleet_type = ft.id WHERE f.id = ?");
    $stmt->execute([$avion_id]);
    $avion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$avion) {
        $message = "Avion non trouvé.";
    } elseif ($payload <= 0 || $distance <= 0 || $temps_vol <= 0) {
        $message = "Tous les champs doivent être remplis avec des valeurs positives.";
    } else {
        // Paramètres de simulation
        $majoration_mission = 1.0; // mission standard
        $carburant = $distance * 3; // estimation simple : 3L/nm
        $note = 8; // note standard
        $cout_horaire = (float)($avion['cout_horaire'] ?? 350);

        // Coefficient de note
        $coef_note = 0.8; // pour note 8

        // Calcul du revenu brut
        if ($distance < 100) {
            $revenu_brut = $payload * 5 * $temps_vol * $majoration_mission * 1.2;
        } else {
            $revenu_brut = $payload * 5 * $distance * $majoration_mission / 1000;
        }
        $cout_carburant = $carburant * 0.88;
        $cout_appareil = $cout_horaire * $temps_vol * $coef_note;
        $gain_net = $revenu_brut - ($cout_carburant + $cout_appareil);
    }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/menu_logged.php'; ?>
<main>
    <div class="container" style="max-width:600px;margin:40px auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
        <h2 style="text-align:center;">Simulation de gain net d'un vol</h2>
        <form method="post" style="margin-top:32px;">
            <label for="avion_id"><b>Avion :</b></label>
            <select name="avion_id" id="avion_id" required>
                <option value="">-- Choisir un avion --</option>
                <?php foreach ($avions as $a): ?>
                    <option value="<?= $a['id'] ?>" <?= (isset($_POST['avion_id']) && $_POST['avion_id'] == $a['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($a['immat']) ?> (<?= htmlspecialchars($a['fleet_type']) ?>)
                    </option>
                <?php endforeach; ?>
            </select><br><br>
            <label for="payload"><b>Fret (kg) :</b></label>
            <input type="number" name="payload" id="payload" min="0" step="1" value="<?= htmlspecialchars($_POST['payload'] ?? '') ?>" required><br><br>
            <label for="distance"><b>Distance (nm) :</b></label>
            <input type="number" name="distance" id="distance" min="0" step="1" value="<?= htmlspecialchars($_POST['distance'] ?? '') ?>" required><br><br>
            <label for="temps_vol"><b>Temps de vol (heures décimales) :</b></label>
            <input type="number" name="temps_vol" id="temps_vol" min="0" step="0.01" value="<?= htmlspecialchars($_POST['temps_vol'] ?? '') ?>" required><br><br>
            <button class="btn" type="submit">Simuler</button>
        </form>
        <p style="margin-top:24px;font-size:1.05em;color:#555;">
            <b>Paramètres de simulation&nbsp;:</b><br>
            - Majoration de mission : <b>1</b> (mission standard)<br>
            - Estimation consommation carburant : <b>3 L/nm</b> (soit <?= isset($_POST['distance']) && is_numeric($_POST['distance']) ? (float)$_POST['distance']*3 : 'distance × 3' ?> L pour ce vol)<br>
            - Coefficient lié à la note : <b>0,8</b> (note 8/10)
        </p>
        <?php if ($message): ?>
            <div style="color:#c0392b;font-weight:bold;margin-top:18px;"><?= htmlspecialchars($message) ?></div>
        <?php elseif ($gain_net !== null): ?>
            <div style="margin-top:32px;padding:18px;background:#eafaf1;border-radius:8px;text-align:center;">
                <b>Gain net estimé :</b> <span style="color:#16a085;font-size:1.5em;"><?= number_format($gain_net, 2, ',', ' ') ?> €</span>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
