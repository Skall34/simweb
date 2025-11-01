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
        if (empty($parts['host'])) return true;
        // if host present, ensure it matches current host
        $currentHost = $_SERVER['HTTP_HOST'] ?? '';
        if ($currentHost !== '' && strcasecmp($parts['host'], $currentHost) === 0) return true;
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
    <div class="login-container" style="max-width:340px;margin:40px auto 0 auto;padding:32px 28px;background:#f7fbff;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.07);">
        <h2 style="text-align:center;margin-bottom:1.2em;">Connexion</h2>
        <?php if (!empty($error)) echo "<p style='color:#d32f2f;text-align:center;font-weight:bold;margin-bottom:1em;'>$error</p>"; ?>
        <form method="post" action="login.php" style="display:flex;flex-direction:column;gap:18px;">
            <label style="font-weight:600;color:#0d47a1;">Callsign<br>
                <input type="text" name="callsign" required style="width:100%;padding:8px 10px;border-radius:6px;border:1px solid #b0bec5;font-size:1em;">
            </label>
            <label style="font-weight:600;color:#0d47a1;">Mot de passe<br>
                <input type="password" name="password" required style="width:100%;padding:8px 10px;border-radius:6px;border:1px solid #b0bec5;font-size:1em;">
            </label>
            <?php if (!empty($redirect)): ?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect, ENT_QUOTES) ?>">
            <?php endif; ?>
            <button type="submit" class="btn" style="width:100%;background:#1976d2;color:#fff;font-weight:bold;padding:10px 0;border:none;border-radius:6px;font-size:1.1em;cursor:pointer;">Se connecter</button>
        </form>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
