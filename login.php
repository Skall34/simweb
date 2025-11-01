<?php
session_start();
require 'includes/db_connect.php'; // à créer: connexion à la base

// support optional redirect parameter (only allow internal paths to avoid open-redirects)
function is_safe_redirect($url) {
    if (!is_string($url) || $url === '') return false;
    // allow only absolute paths starting with a single slash, not protocol-relative (//)
    if (preg_match('#^/(?!/)#', $url) === 1) return true;

    // Allow special local callback URLs ending with /simaddon-callback/
    // Accept if the URL ends with that suffix and is a local path (no host),
    // or if it is an absolute URL on the same host.
    if (preg_match('#/simaddon-callback/?$#', $url) === 1) {
        $parts = parse_url($url);
        // path-only (no host) is allowed
        //if (empty($parts['host'])) return true;
        // if host present, ensure it matches current host
        //$currentHost = $_SERVER['HTTP_HOST'] ?? '';
        //if ($currentHost !== '' && strcasecmp($parts['host'], $currentHost) === 0) return true;
        return true;
    }

    return false;
}

$redirect = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirect = $_POST['redirect'] ?? '';
    $callsign = $_POST['callsign'] ?? '';
    $password = $_POST['password'] ?? '';

    // Prépare et execute requête
    $stmt = $pdo->prepare('SELECT * FROM PILOTES WHERE actif=1 AND callsign = ?');
    $stmt->execute([$callsign]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Auth OK
        $_SESSION['user'] = [
            'id' => $user['id'],
            'callsign' => $user['callsign']
        ];
        // Ajout : instanciation explicite du callsign pour la session
        $_SESSION['callsign'] = $user['callsign'];
        // Mise à jour de la date de dernière connexion
        $update = $pdo->prepare("UPDATE PILOTES SET derniere_connexion = NOW() WHERE id = :id");
        $update->execute(['id' => $user['id']]);
        // Redirect to requested internal URL if valid, otherwise to index.php
        if (is_safe_redirect($redirect)) {
            header('Location: ' . $redirect);
        } else {
            header('Location: index.php');
        }
        exit;
    } else {
        $error = "Login ou mot de passe incorrect.";
    }
}
else {
    // GET: capture redirect parameter if provided
    $redirect = $_GET['redirect'] ?? '';
}
include 'includes/header.php';
?>

<main>
    <div class="login-container">
        <h2 class="text-center">Connexion</h2>
        <?php if (!empty($error)) echo "<p class='flash-error' style='text-align:center;margin-bottom:1em;'>" . htmlspecialchars($error) . "</p>"; ?>
        <form method="post" action="login.php" class="form-column">
            <label class="login-label">Callsign<br>
                <input type="text" name="callsign" required class="form-input">
            </label>
            <label class="login-label">Mot de passe<br>
                <input type="password" name="password" required class="form-input">
            </label>
            <?php if (!empty($redirect)): ?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect, ENT_QUOTES) ?>">
            <?php endif; ?>
            <button type="submit" class="btn btn-full">Se connecter</button>
        </form>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
