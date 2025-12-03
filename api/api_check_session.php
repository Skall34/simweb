<?php
// api/api_check_session.php
// Retourne JSON { authenticated: true|false, user: { id, callsign } }
header('Content-Type: application/json');

$authenticated = false;
$user = null;

// Not authenticated via session: allow token-based recovery (cookie or token param)
$token = $_COOKIE['simaddon_token'] ?? $_GET['token'] ?? null;

// Validate token if provided
require_once __DIR__ . '/../includes/tokens.php';
$userId = check_token($token);
if ($userId !== null) {
    // Token valid: retrieve associated user
    require_once __DIR__ . '/../includes/db_connect.php';
    $stmt = $pdo->prepare("SELECT id, callsign FROM PILOTES WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $authenticated = true;
    }
}
if ($authenticated) {
    echo json_encode(['authenticated' => $authenticated, 'user' => $user]);
    exit;
}else{
    //return a http 401 error
    http_response_code(401);
    echo json_encode(['authenticated' => false, 'user' => null]);
    exit;
}    

exit;
