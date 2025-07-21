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
    $type = trim($_POST['type'] ?? '');
    $cout_horaire = floatval($_POST['cout_horaire'] ?? 0);
    $cout_appareil = floatval($_POST['cout_appareil'] ?? 0);

    if ($fleet_type === '' || $type === '') {
        $errorMessage = "Les champs 'fleet_type' et 'type' sont obligatoires.";
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
                $stmt = $pdo->prepare("INSERT INTO FLEET_TYPE (fleet_type, type, cout_horaire, cout_appareil) VALUES (:fleet_type, :type, :cout_horaire, :cout_appareil)");
                $stmt->execute([
                    'fleet_type' => $fleet_type,
                    'type' => $type,
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
// Récupérer toute la table FLEET_TYPE pour affichage en deux colonnes
$fleetTypes = [];
try {
    $stmt = $pdo->query("SELECT fleet_type, type, cout_horaire, cout_appareil FROM FLEET_TYPE ORDER BY fleet_type ASC");
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

            <label>Catégorie *</label>
            <select id="type" name="type" required style="width: 370px;">
                <option value="">-- Sélectionner --</option>
                <option value="Monomoteur">Monomoteur</option>
                <option value="Bimoteur">Bimoteur</option>
                <option value="Liner">Liner</option>
                <option value="Hélicoptère">Hélicoptère</option>
            </select>

            <label>Coût horaire (€) *</label>
            <input type="number" id="cout_horaire" name="cout_horaire" step="10"  style="width: 370px;" required>

            <label>Coût de l'appareil (€) *</label>
            <input type="number" id="cout_appareil" name="cout_appareil" step="10"  style="width: 370px;" required>

            <button type="submit" class="btn">Ajouter</button>
        </form>
    </div>

    <aside style="min-width:700px;max-width:1500px;margin-left:40px;margin-right:auto;background:#f7fbff;border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,0.04);padding:18px 16px 12px 16px;align-self:center;">
        <h3 style="margin-top:0;margin-bottom:12px;font-size:1.1em;color:#0066cc;">Fleet types existants</h3>
        <?php
        $total = count($fleetTypes);
        $mid = (int)ceil($total / 2);
        $col1 = array_slice($fleetTypes, 0, $mid);
        $col2 = array_slice($fleetTypes, $mid);
        ?>
        <div style="display: flex; gap: 32px; align-items: flex-start;">
            <div class="table-section">
                <table class="table-skywings">
                    <thead>
                        <tr>
                            <th class="fleet_type">Nom</th>
                            <th class="type">Catégorie</th>
                            <th class="cout_horaire">Coût horaire (€)</th>
                            <th class="prix">Prix (€)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($col1 as $ft): ?>
                        <tr>
                            <td class="fleet_type"><?= htmlspecialchars($ft['fleet_type']) ?></td>
                            <td class="type" style="color:#444; font-style:italic;"><?= htmlspecialchars($ft['type']) ?></td>
                            <td class="cout_horaire" style="text-align:right;"><?= number_format($ft['cout_horaire'], 2, ',', ' ') ?></td>
                            <td class="prix" style="text-align:right;font-weight:bold;"><?= number_format($ft['cout_appareil'], 0, '', ' ') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-section">
                <table class="table-skywings">
                    <thead>
                        <tr>
                            <th class="fleet_type">Nom</th>
                            <th class="type">Catégorie</th>
                            <th class="cout_horaire">Coût horaire (€)</th>
                            <th class="prix">Prix (€)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($col2 as $ft): ?>
                        <tr>
                            <td class="fleet_type"><?= htmlspecialchars($ft['fleet_type']) ?></td>
                            <td class="type" style="color:#444; font-style:italic;"><?= htmlspecialchars($ft['type']) ?></td>
                            <td class="cout_horaire" style="text-align:right;"><?= number_format($ft['cout_horaire'], 2, ',', ' ') ?></td>
                            <td class="prix" style="text-align:right;font-weight:bold;"><?= number_format($ft['cout_appareil'], 0, '', ' ') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </aside>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
