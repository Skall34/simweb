<?php
// api/api_check_session.php
// Retourne JSON { authenticated: true|false, user: { id, callsign } }
header('Content-Type: application/json');
session_start();

$authenticated = false;
$user = null;
if (isset($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
    $authenticated = true;
    $user = [
        'id' => (int)$_SESSION['user']['id'],
        'callsign' => $_SESSION['user']['callsign'] ?? null
    ];
}

echo json_encode([
    'authenticated' => $authenticated,
    'user' => $user
]);

exit;
