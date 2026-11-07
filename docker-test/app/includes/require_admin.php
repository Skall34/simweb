<?php
// Central admin guard: ensures session started, user authenticated, and is admin.
// Usage: require_once __DIR__ . '/require_admin.php'; at the top of admin pages

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in (use existing require_login behaviour)
if (!isset($_SESSION['user']['id']) && !isset($_SESSION['callsign'])) {
    header('Location: /index.php');
    exit;
}

// Ensure DB is available (some pages may not have required it yet)
require_once __DIR__ . '/db_connect.php';

// Determine callsign: prefer structured session then legacy
$callsign = $_SESSION['user']['callsign'] ?? ($_SESSION['callsign'] ?? null);
if (!$callsign) {
    header('Location: /index.php');
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT admin FROM PILOTES WHERE callsign = :callsign');
    $stmt->execute(['callsign' => $callsign]);
    $isAdmin = $stmt->fetchColumn();
} catch (Exception $e) {
    $isAdmin = 0;
}

if (! $isAdmin) {
    // Not an admin — redirect to home
    header('Location: /index.php');
    exit;
}

?>
