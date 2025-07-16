<?php
session_start();
require __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';

// Traitement du formulaire
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fleet_type = trim($_POST['fleet_type'] ?? '');
    $cout_horaire = floatval($_POST['cout_horaire'] ?? 0);
    $cout_appareil = floatval($_POST['cout_appareil'] ?? 0);

    if ($fleet_type === '') {
        $errorMessage = "Le champ 'fleet_type' est obligatoire.";
    } else {
        try {
            // Vérifier si le fleet_type existe déjà
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM FLEET_TYPE WHERE fleet_type = :fleet_type");
            $stmt->execute(['fleet_type' => $fleet_type]);
            $exists = $stmt->fetchColumn();

            if ($exists) {
                $errorMessage = "Ce type de flotte existe déjà.";
            } else {
                // Insérer
                $stmt = $pdo->prepare("INSERT INTO FLEET_TYPE (fleet_type, cout_horaire, cout_appareil) VALUES (:fleet_type, :cout_horaire, :cout_appareil)");
                $stmt->execute([
                    'fleet_type' => $fleet_type,
                    'cout_horaire' => $cout_horaire,
                    'cout_appareil' => $cout_appareil
                ]);
                $successMessage = "Nouveau fleet type ajouté avec succès.";
            }
        } catch (PDOException $e) {
            $errorMessage = "Erreur SQL : " . htmlspecialchars($e->getMessage());
        }
    }
}
// Récupérer la liste des fleet types existants
$fleetTypes = [];
try {
    $stmt = $pdo->query("SELECT fleet_type, cout_horaire, cout_appareil FROM FLEET_TYPE ORDER BY fleet_type ASC");
    $fleetTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Ignore erreur
}

?>

<main style="display:flex; flex-direction:row; align-items:flex-start; gap:40px;">
    <div style="flex:1; min-width:280px; max-width:370px;">
        <h2>Ajouter un fleet type</h2>

        <?php if ($successMessage): ?>
            <p style="color: green; font-weight:bold;"><?= $successMessage ?></p>
        <?php elseif ($errorMessage): ?>
            <p style="color: red; font-weight:bold;"><?= $errorMessage ?></p>
        <?php endif; ?>

        <form method="post" action="" class="form-inscription">
            <label>Nom du fleet type *</label>
            <input type="text" id="fleet_type" name="fleet_type" required>

            <label>Coût horaire (€) *</label>
            <input type="number" id="cout_horaire" name="cout_horaire" step="10"  style="width: 370px;" required>

            <label>Coût de l'appareil (€) *</label>
            <input type="number" id="cout_appareil" name="cout_appareil" step="10"  style="width: 370px;" required>

            <button type="submit" class="btn">Ajouter</button>
        </form>
    </div>

    <aside style="min-width:260px;max-width:800px;margin-left:40px;margin-right:auto;background:#f7fbff;border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,0.04);padding:18px 16px 12px 16px;align-self:center;">
        <h3 style="margin-top:0;margin-bottom:12px;font-size:1.1em;color:#0066cc;">Fleet types existants</h3>
        <table style="font-size:0.97em;width:100%;border-collapse:separate;border-spacing:0;border-radius:12px;overflow:hidden;">
            <thead>
                <tr style="background:#e3f2fd;">
                    <th style="padding:6px 8px;border:none;border-top-left-radius:12px;">Type</th>
                    <th style="padding:6px 8px;border:none;">Coût horaire</th>
                    <th style="padding:6px 18px;border:none;border-top-right-radius:12px;width:200px;">Prix (€)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fleetTypes as $ft): ?>
                <tr style="background:#fff;">
                    <td style="padding:6px 8px;border:none;"><?= htmlspecialchars($ft['fleet_type']) ?></td>
                    <td style="padding:6px 8px;border:none;text-align:right;"><?= number_format($ft['cout_horaire'], 2, ',', ' ') ?></td>
                    <td style="padding:6px 18px;border:none;text-align:right;font-weight:bold;"><?= number_format($ft['cout_appareil'], 0, '', ' ') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </aside>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
