<?php
// ===== Stock Flow - Database Configuration & Helpers =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = "localhost";
$db_name = "stock_flow";
$db_user = "root";
$db_pass = "";

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    // If database connection fails, define $pdo as null so pages can handle connection failure gracefully
    $pdo = null;
    $db_error = $e->getMessage();
}

// Check if user is authenticated
function is_logged_in() {
    return isset($_SESSION['user_id']) || isset($_SESSION['username']);
}

// Enforce login on protected pages
function check_login() {
    if (!is_logged_in()) {
        header("Location: ../index.php");
        exit;
    }
}

// Sanitize user inputs
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Flash messaging helper
function set_flash_message($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash_message() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
?>
