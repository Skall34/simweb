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
?>

<main>
    <h2>Ajouter un fleet type</h2>

    <?php if ($successMessage): ?>
        <p style="color: green;"><?= $successMessage ?></p>
    <?php elseif ($errorMessage): ?>
        <p style="color: red;"><?= $errorMessage ?></p>
    <?php endif; ?>

    <form method="post" action="" class="form-inscription">
        <label>Nom du fleet type *</label>
        <input type="text" id="fleet_type" name="fleet_type" required>

        <label>Coût horaire (€) *</label>
        <input type="number" id="cout_horaire" name="cout_horaire" step="10"  style="width: 400px;" required>

        <label>Coût de l'appareil (€) *</label>
        <input type="number" id="cout_appareil" name="cout_appareil" step="10"  style="width: 400px;" required>

        <button type="submit" class="btn">Ajouter</button>
    </form>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
