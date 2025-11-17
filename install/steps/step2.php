<?php
/**
 * Étape 2 : Configuration base de données
 */

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host'] ?? '');
    $db_port = trim($_POST['db_port'] ?? '3306');
    $db_name = trim($_POST['db_name'] ?? '');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = $_POST['db_pass'] ?? '';
    
    // Validation
    if (empty($db_host)) $errors[] = 'L\'hôte de la base de données est requis';
    if (empty($db_name)) $errors[] = 'Le nom de la base de données est requis';
    if (empty($db_user)) $errors[] = 'L\'utilisateur de la base de données est requis';
    
    if (empty($errors)) {
        // Test de connexion
        try {
            $dsn = "mysql:host=$db_host;port=$db_port;charset=utf8mb4";
            $pdo = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            // Tester si la base existe déjà
            $stmt = $pdo->query("SHOW DATABASES LIKE '$db_name'");
            $db_exists = $stmt->rowCount() > 0;
            
            // Sauvegarder en session
            $_SESSION['install_data']['database'] = [
                'host' => $db_host,
                'port' => $db_port,
                'name' => $db_name,
                'user' => $db_user,
                'pass' => $db_pass,
                'exists' => $db_exists
            ];
            
            $success = true;
            header('Location: ?step=3');
            exit;
            
        } catch (PDOException $e) {
            $errors[] = 'Erreur de connexion : ' . $e->getMessage();
        }
    }
} else {
    // Préremplir avec les valeurs de session si disponibles
    $db_host = $_SESSION['install_data']['database']['host'] ?? 'localhost';
    $db_port = $_SESSION['install_data']['database']['port'] ?? '3306';
    $db_name = $_SESSION['install_data']['database']['name'] ?? '';
    $db_user = $_SESSION['install_data']['database']['user'] ?? '';
    $db_pass = $_SESSION['install_data']['database']['pass'] ?? '';
}

?>

<div class="step-content">
    <h2>🗄️ Configuration de la base de données</h2>
    <p>Entrez les informations de connexion à votre serveur MySQL/MariaDB.</p>

    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <h4>Erreurs de validation :</h4>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="install-form" id="db-form">
        <div class="form-group">
            <label for="db_host">Hôte de la base de données *</label>
            <input type="text" id="db_host" name="db_host" value="<?= htmlspecialchars($db_host ?? 'localhost') ?>" required>
            <small>Généralement "localhost" ou "127.0.0.1"</small>
        </div>

        <div class="form-group">
            <label for="db_port">Port</label>
            <input type="number" id="db_port" name="db_port" value="<?= htmlspecialchars($db_port ?? '3306') ?>" required>
            <small>Port MySQL standard : 3306</small>
        </div>

        <div class="form-group">
            <label for="db_name">Nom de la base de données *</label>
            <input type="text" id="db_name" name="db_name" value="<?= htmlspecialchars($db_name ?? '') ?>" required pattern="[a-zA-Z0-9_]+">
            <small>Exemple : yourva_database (sans espaces ni caractères spéciaux)</small>
        </div>

        <div class="form-group">
            <label for="db_user">Utilisateur MySQL *</label>
            <input type="text" id="db_user" name="db_user" value="<?= htmlspecialchars($db_user ?? '') ?>" required>
            <small>L'utilisateur doit avoir les droits CREATE DATABASE et CREATE TABLE</small>
        </div>

        <div class="form-group">
            <label for="db_pass">Mot de passe MySQL</label>
            <input type="password" id="db_pass" name="db_pass" value="<?= htmlspecialchars($db_pass ?? '') ?>">
            <small>Laissez vide si aucun mot de passe</small>
        </div>

        <div class="info-box">
            <strong>💡 Information importante :</strong>
            <p>L'installateur va créer automatiquement la base de données et importer les tables. 
            Assurez-vous que l'utilisateur MySQL a les droits suffisants.</p>
        </div>

        <div class="actions">
            <a href="?step=1" class="btn btn-secondary">← Retour</a>
            <button type="submit" class="btn btn-primary">Tester et continuer →</button>
        </div>
    </form>
</div>

<script>
// Test de connexion en temps réel (optionnel)
document.getElementById('db-form').addEventListener('submit', function(e) {
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = 'Test de connexion...';
});
</script>
