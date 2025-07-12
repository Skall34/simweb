<?php
session_start();

require __DIR__ . '/../includes/db_connect.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$message = '';
$errors = [];
$mission = null;  // null signifie : pas encore recherché ni en modification
$mode = null;     // 'edit' ou 'create' selon résultat recherche

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recherche
    if (isset($_POST['search'])) {
        $searchLibelle = trim($_POST['libelle_search'] ?? '');
        if ($searchLibelle === '') {
            $errors[] = 'Merci de saisir un libellé à rechercher.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM MISSIONS WHERE libelle = :libelle LIMIT 1");
            $stmt->execute(['libelle' => $searchLibelle]);
            $missionFound = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($missionFound) {
                $mission = $missionFound;
                $mode = 'edit';
            } else {
                // Nouvelle mission à créer
                $mode = 'create';
                $mission = [
                    'libelle' => $searchLibelle,
                    'majoration_mission' => '1.00',
                    'Active' => 1
                ];
                $message = "Mission '$searchLibelle' non trouvée. Vous pouvez la créer ci-dessous.";
            }
        }
    }

    // Enregistrement modification ou création
    if (isset($_POST['save'])) {
        $libelle = trim($_POST['libelle'] ?? '');
        $majoration = trim($_POST['majoration_mission'] ?? '');
        $active = isset($_POST['Active']) ? 1 : 0;

        if ($libelle === '') {
            $errors[] = 'Le libellé est obligatoire.';
        }
        if (!is_numeric($majoration) || $majoration < 0) {
            $errors[] = 'La majoration doit être un nombre positif.';
        }

        if (empty($errors)) {
            // On vérifie si la mission existe
            $stmtCheck = $pdo->prepare("SELECT id FROM MISSIONS WHERE libelle = :libelle LIMIT 1");
            $stmtCheck->execute(['libelle' => $libelle]);
            $exists = $stmtCheck->fetchColumn();

            if ($exists) {
                // Update
                $stmtUpdate = $pdo->prepare("UPDATE MISSIONS SET majoration_mission = :maj, Active = :active WHERE libelle = :libelle");
                $stmtUpdate->execute([
                    'maj' => $majoration,
                    'active' => $active,
                    'libelle' => $libelle
                ]);
                $message = "Mission '$libelle' mise à jour avec succès.";
            } else {
                // Insert
                $stmtInsert = $pdo->prepare("INSERT INTO MISSIONS (libelle, majoration_mission, Active) VALUES (:libelle, :maj, :active)");
                $stmtInsert->execute([
                    'libelle' => $libelle,
                    'maj' => $majoration,
                    'active' => $active
                ]);
                $message = "Mission '$libelle' créée avec succès.";
            }

            // Ne pas afficher le formulaire complet après modif/création
            $mission = null;
            $mode = null;
        } else {
            // Remplir formulaire avec données soumises en cas d'erreur
            $mission = [
                'libelle' => $libelle,
                'majoration_mission' => $majoration,
                'Active' => $active
            ];
            $mode = $exists ? 'edit' : 'create';
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>

<main>
    <h2>Administration des missions</h2>

    <?php if ($message): ?>
        <p style="color: green;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if ($errors): ?>
        <ul style="color: red;">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <!-- Formulaire de recherche toujours visible -->
    <form method="post" style="margin-bottom: 2rem;" class="form-inscription">
        <label for="libelle_search">Rechercher une mission par son nom :</label>
        <input type="text" name="libelle_search" id="libelle_search" value="<?= htmlspecialchars($_POST['libelle_search'] ?? '') ?>" required>
        <button type="submit" name="search" class="btn">Rechercher</button>
    </form>

    <!-- Afficher formulaire complet uniquement après recherche et uniquement si on n'est pas juste après modif/création -->
    <?php if ($mission !== null): ?>
        <form method="post" style="max-width: 400px;" class="form-inscription">
            <label for="libelle">Libellé :</label>
            <input type="text" name="libelle" id="libelle" value="<?= htmlspecialchars($mission['libelle'] ?? '') ?>" required>

            <label for="majoration_mission">Majoration (ex : 1.00) :</label>
            <input type="number" step="0.10" min="0" name="majoration_mission" id="majoration_mission" value="<?= htmlspecialchars($mission['majoration_mission'] ?? '1.00') ?>" required>

            <label for="Active" style="display: inline-flex; align-items: center; gap: 0.25em; cursor: pointer;">
                <input type="checkbox" name="Active" id="Active" value="1" <?= (isset($mission['Active']) && $mission['Active']) ? 'checked' : '' ?> style="margin: 0;">
                Active
            </label>


            <?php if ($mode === 'edit'): ?>
                <button type="submit" name="save">Modifier</button>
            <?php elseif ($mode === 'create'): ?>
                <button type="submit" name="save">Créer</button>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
