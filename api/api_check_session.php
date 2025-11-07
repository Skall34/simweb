<?php
// api/api_check_session.php
// Retourne JSON { authenticated: true|false, user: { id, callsign } }
header('Content-Type: application/json');
session_start();

$authenticated = false;
$user = null;

// If session already has user, return immediately
if (isset($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
    $authenticated = true;
    $user = [
        'id' => (int)$_SESSION['user']['id'],
        'callsign' => $_SESSION['user']['callsign'] ?? null
    ];

    echo json_encode(['authenticated' => $authenticated, 'user' => $user]);
    exit;
}

// Not authenticated via session: allow token-based recovery (cookie or token param)
$token = $_COOKIE['simaddon_token'] ?? $_GET['token'] ?? null;
if (!empty($token)) {
    try {
        require_once __DIR__ . '/../includes/db_connect.php';

        // Find valid token (not expired)
        $stmt = $pdo->prepare("SELECT user_id, expires_at FROM simaddon_tokens WHERE token = :token LIMIT 1");
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            // check expiry if set
            if (empty($row['expires_at']) || strtotime($row['expires_at']) > time()) {
                // load user to verify still active
                $stmtU = $pdo->prepare("SELECT id, callsign FROM PILOTES WHERE id = :id AND actif = 1 LIMIT 1");
                $stmtU->execute(['id' => $row['user_id']]);
                $u = $stmtU->fetch(PDO::FETCH_ASSOC);
                if ($u) {
                    // restore session user
                    $_SESSION['user'] = ['id' => (int)$u['id'], 'callsign' => $u['callsign']];
                    $_SESSION['callsign'] = $u['callsign'];
                    $authenticated = true;
                    $user = ['id' => (int)$u['id'], 'callsign' => $u['callsign']];

                    // Optionally refresh token expiry (extend by 15 days)
                    $newExpiry = date('Y-m-d H:i:s', time() + 15 * 24 * 3600);
                    $upd = $pdo->prepare("UPDATE simaddon_tokens SET expires_at = :expires_at WHERE token = :token");
                    $upd->execute(['expires_at' => $newExpiry, 'token' => $token]);
                    // Refresh cookie
                    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
                    setcookie('simaddon_token', $token, 0, '/', '', $isHttps, true);
                }
            }
        }
    } catch (Exception $e) {
        // log if available, but don't leak DB errors to clients
        if (function_exists('logMsg')) logMsg('api_check_session token lookup failed: ' . $e->getMessage(), __DIR__ . '/../scripts/logs/api_check_session.log');
    }
}

echo json_encode(['authenticated' => $authenticated, 'user' => $user]);
exit;
