<?php
// Central require for pages that need a logged-in user.
// Usage: put `require_once __DIR__ . '/require_login.php';` at the top of the page
// before any output. This starts the session if needed and redirects to the
// login page when the user is not authenticated.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Accept either structured user session or legacy callsign session
if (!isset($_SESSION['user']['id']) && !isset($_SESSION['callsign'])) {
    // Use absolute path to avoid relative include issues
    header('Location: /index.php');
    exit;
}

?>
