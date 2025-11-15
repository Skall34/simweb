<?php
session_start();
require 'includes/db_connect.php'; // à créer: connexion à la base
require_once 'includes/log_func.php';

// support optional redirect parameter (only allow internal paths to avoid open-redirects)
function is_safe_redirect($url) {
    if (!is_string($url) || $url === '') return false;
    // allow only absolute paths starting with a single slash, not protocol-relative (//)
    if (preg_match('#^/(?!/)#', $url) === 1) return true;

    // Allow special local callback URLs ending with /simaddon-callback/
    // Accept if the URL ends with that suffix and is a local path (no host),
    // or if it is an absolute URL on the same host.
    // if URL contains the simaddon callback marker anywhere, allow it under the same rules
    if (preg_match('#simaddon-callback#', $url) === 1) {
        $parts = parse_url($url);
        // path-only (no host) is allowed
        if (empty($parts['host'])) return true;
        // if host present, only allow 127.0.0.1
        if (isset($parts['host']) && $parts['host'] === '127.0.0.1') return true;
    }

    return false;
}

$redirect = '';
$token = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirect = $_POST['redirect'] ?? '';
    $token = $_POST['token'] ?? '';
    $callsign = $_POST['callsign'] ?? '';
    $password = $_POST['password'] ?? '';

    // Prépare et execute requête
    $stmt = $pdo->prepare('SELECT * FROM PILOTES WHERE actif=1 AND callsign = ?');
    $stmt->execute([$callsign]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Auth OK
    logMsg('User ' . $user['id'] . ' (' . $user['callsign'] . ') logged in successfully.', __DIR__ . '/scripts/logs/login.log');
        $_SESSION['user'] = [
            'id' => $user['id'],
            'callsign' => $user['callsign']
        ];
        // Ajout : instanciation explicite du callsign pour la session
        $_SESSION['callsign'] = $user['callsign'];
        // Mise à jour de la date de dernière connexion
        $update = $pdo->prepare("UPDATE PILOTES SET derniere_connexion = NOW() WHERE id = :id");
        $update->execute(['id' => $user['id']]);
        // Associate token with session if provided (ensure session continuity)
        if (!empty($token)) {
            // regenerate session id to prevent fixation and then store token
            session_regenerate_id(true);
            $_SESSION['ext_token'] = $token;
            // set a secure HttpOnly cookie to help continuity with external callbacks
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
            setcookie('simaddon_token', $token, 0, '/', '', $isHttps, true);
            // Persist token in database for cross-process/session continuity
            try {
                // expires in 15 days - adjust as needed
                $expiresAt = date('Y-m-d H:i:s', time() + 15 * 24 * 3600);
                // Ensure table `simaddon_tokens` exists (see migration SQL below)
                $stmtTok = $pdo->prepare(
                    "INSERT INTO simaddon_tokens (user_id, token, created_at, expires_at, ip, user_agent) VALUES (:user_id, :token, NOW(), :expires_at, :ip, :ua) " .
                    "ON DUPLICATE KEY UPDATE created_at = NOW(), expires_at = :expires_at_upd, ip = :ip_upd, user_agent = :ua_upd"
                );
                $params = [
                    'user_id' => $user['id'],
                    'token' => $token,
                    'expires_at' => $expiresAt,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                    // duplicate params for the UPDATE clause
                    'expires_at_upd' => $expiresAt,
                    'ip_upd' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'ua_upd' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
                ];
                $stmtTok->execute($params);
                logMsg('Token persisted for user ' . $user['id'], __DIR__ . '/scripts/logs/login.log');
            } catch (Exception $e) {
                // Don't block login on DB token persistence failure; log if logger available
                logMsg('Token persistence failed: ' . $e->getMessage(), __DIR__ . '/scripts/logs/login.log');
            }
        }else{
            logMsg('No token provided for user ' . $user['id'] . ' during login.', __DIR__ . '/scripts/logs/login.log');
        }
        // Redirect to requested internal URL if valid, otherwise to index.php
        if (is_safe_redirect($redirect)) {
            header('Location: ' . $redirect);
        } else {
            header('Location: index.php');
        }
        exit;
    } else {
        $error = t('login_error_credentials');
    }
}
else {
    // GET: capture redirect and token parameters if provided
    $redirect = $_GET['redirect'] ?? '';
    $token = $_GET['token'] ?? '';
}
include 'includes/header.php';
?>

<main>
    <div class="login-container">
        <h2 class="text-center"><?= t('login_title') ?></h2>
        <?php if (!empty($error)) echo "<p class='flash-error' style='text-align:center;margin-bottom:1em;'>" . htmlspecialchars($error) . "</p>"; ?>
        <form method="post" action="login.php" class="form-column">
            <label class="login-label"><?= t('login_label_callsign') ?><br>
                <input type="text" name="callsign" required class="form-input">
            </label>
            <label class="login-label"><?= t('login_label_password') ?><br>
                <input type="password" name="password" required class="form-input">
            </label>
            <?php if (!empty($redirect)): ?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect, ENT_QUOTES) ?>">
            <?php endif; ?>
            <?php if (!empty($token)): ?>
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
            <?php endif; ?>
            <button type="submit" class="btn btn-full"><?= t('login_btn_submit') ?></button>
        </form>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
