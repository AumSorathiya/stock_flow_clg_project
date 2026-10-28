<?php
require_once __DIR__ . '/config/db.php';

if (is_logged_in() && $pdo !== null) {
    log_activity($pdo, 'Logout', 'Auth', "User '{$_SESSION['username']}' logged out");
}

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Start a fresh session just for the flash message
session_start();
set_flash_message('info', 'You have been logged out successfully.');

header("Location: index.php");
exit;
?>
