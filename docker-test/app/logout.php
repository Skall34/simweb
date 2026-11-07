<?php
// Logout: destroy session, remove simaddon token from DB and clear cookie
session_start();

// Capture token/user before destroying session
$token = $_SESSION['ext_token'] ?? ($_COOKIE['simaddon_token'] ?? null);
$userId = $_SESSION['user']['id'] ?? null;

if (!empty($token) || !empty($userId)) {
	// attempt to remove token from DB; don't block logout on failure
	try {
		require_once __DIR__ . '/includes/db_connect.php';
		if (!empty($token)) {
			$stmt = $pdo->prepare('DELETE FROM simaddon_tokens WHERE token = :token');
			$stmt->execute(['token' => $token]);
		} elseif (!empty($userId)) {
			// if no token available, optionally remove tokens for this user
			$stmt = $pdo->prepare('DELETE FROM simaddon_tokens WHERE user_id = :uid');
			$stmt->execute(['uid' => $userId]);
		}
	} catch (Exception $e) {
		// ignore DB errors during logout
	}
}

// Clear simaddon_token cookie
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
if (isset($_COOKIE['simaddon_token'])) {
	setcookie('simaddon_token', '', time() - 3600, '/', '', $isHttps, true);
	unset($_COOKIE['simaddon_token']);
}

// Destroy session completely
$_SESSION = [];
if (ini_get('session.use_cookies')) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000,
		$params['path'], $params['domain'], $params['secure'], $params['httponly']
	);
}
session_unset();
session_destroy();

header('Location: index.php');
exit();
