<?php
// ===== Stock Flow - Database Configuration & Security Helpers =====

if (ob_get_level() === 0) {
    ob_start();
}

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$db_host = "127.0.0.1";
$db_name = "stock_flow";
$db_user = "root";
$db_pass = "";

// Smart port discovery: prioritize cached port, standard 3306, or local XAMPP custom 3000
$ports_to_try = [];
if (!empty($_SESSION['db_port'])) {
    $ports_to_try[] = intval($_SESSION['db_port']);
}
if (getenv('DB_PORT')) {
    $ports_to_try[] = intval(getenv('DB_PORT'));
}
$ports_to_try[] = 3306;
$ports_to_try[] = 3000;
$ports_to_try = array_values(array_unique($ports_to_try));

$pdo = null;
$db_error = null;
$active_db_port = 3306;

foreach ($ports_to_try as $port) {
    try {
        $dsn = "mysql:host=$db_host;port=$port;dbname=$db_name;charset=utf8mb4";
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 2
        ]);
        $active_db_port = $port;
        $_SESSION['db_port'] = $port;
        $db_error = null;
        break;
    } catch (PDOException $e) {
        $db_error = $e->getMessage();
        $pdo = null;
    }
}

// ===== Authentication & Authorization Helpers =====

function is_logged_in() {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['username']);
}

function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'] ?? 0,
        'username'  => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? 'User',
        'email'     => $_SESSION['email'] ?? '',
        'role'      => $_SESSION['role'] ?? 'staff'
    ];
}

function current_user_role() {
    return $_SESSION['role'] ?? 'staff';
}

function has_role($allowed_roles) {
    if (!is_logged_in()) {
        return false;
    }
    $user_role = current_user_role();
    if (is_array($allowed_roles)) {
        return in_array($user_role, $allowed_roles, true);
    }
    return $user_role === $allowed_roles;
}

// Enforce login on protected pages
function check_login() {
    if (!is_logged_in()) {
        set_flash_message('warning', 'Please log in to continue.');
        $is_pages_dir = strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false;
        $login_url = $is_pages_dir ? '../index.php' : 'index.php';
        header("Location: $login_url");
        exit;
    }
}

// Server-side Role Guard for protected actions and pages
function check_role($allowed_roles) {
    check_login();
    if (!has_role($allowed_roles)) {
        set_flash_message('error', 'Access Denied: Your account role (' . ucfirst(current_user_role()) . ') does not have permission to view that resource.');
        $is_pages_dir = strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false;
        $redirect_url = $is_pages_dir ? 'dashboard.php' : 'pages/dashboard.php';
        header("Location: $redirect_url");
        exit;
    }
}

// ===== CSRF Protection Helpers =====

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf() {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            return false;
        }
    }
    return true;
}

// ===== Audit Activity Logger =====

function log_activity($pdo, $action, $module, $description) {
    if ($pdo === null) return;
    try {
        $user_id = $_SESSION['user_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, module, description, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $module, $description, $ip]);
    } catch (Exception $e) {
        // Silently continue so logging never interrupts user transaction
    }
}

// ===== Sanitize & Flash Messaging =====

function sanitize($data) {
    if ($data === null) return '';
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function set_flash_message($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'error', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash_message() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function render_flash() {
    $flash = get_flash_message();
    if (!$flash) return '';
    
    $type = $flash['type'];
    $msg = htmlspecialchars($flash['message']);
    
    $styles = [
        'success' => 'background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;',
        'error'   => 'background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;',
        'warning' => 'background: #fffbeb; color: #92400e; border: 1px solid #fde68a;',
        'info'    => 'background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;'
    ];
    $icons = [
        'success' => '✓',
        'error'   => '✕',
        'warning' => '⚠️',
        'info'    => 'ℹ️'
    ];
    
    $style = $styles[$type] ?? $styles['info'];
    $icon = $icons[$type] ?? 'ℹ️';
    
    return "<div class=\"flash-alert flash-{$type}\" style=\"{$style} padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; font-weight: 500; font-size: 14px;\">
        <span><strong style=\"margin-right: 8px;\">{$icon}</strong> {$msg}</span>
        <button type=\"button\" onclick=\"this.parentElement.remove();\" style=\"background: none; border: none; font-size: 18px; line-height: 1; cursor: pointer; color: inherit; opacity: 0.6;\">&times;</button>
    </div>";
}
?>
