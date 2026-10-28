<?php
require_once __DIR__ . '/../config/db.php';
check_login();

// Base URL detection for stylesheet & navigation links
$is_pages_dir = strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false;
$base_prefix = $is_pages_dir ? '../' : './';
$pages_prefix = $is_pages_dir ? '' : 'pages/';

$page_title = isset($page_title) ? $page_title : 'Stock Flow';
$user_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
$user_role = current_user_role();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo $base_prefix; ?>style/style.css">
</head>
<body>
    <div id="header">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div id="rightsidebar">
            <!-- Top App Bar -->
            <div class="top-navbar">
                <div class="top-navbar-left">
                    <button type="button" class="mobile-menu-btn" onclick="toggleMobileSidebar()" aria-label="Toggle navigation">
                        ☰
                    </button>
                    <div class="page-breadcrumb">
                        <span>Stock Flow</span> / <strong><?php echo htmlspecialchars(str_replace('Stock Flow - ', '', $page_title)); ?></strong>
                    </div>
                </div>
                <div class="top-navbar-right">
                    <div class="top-user-pill">
                        <span>👋 Hello, <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
                        <span class="role-badge role-<?php echo htmlspecialchars($user_role); ?>">
                            <?php echo strtoupper(htmlspecialchars($user_role)); ?>
                        </span>
                    </div>
                    <a href="<?php echo $base_prefix; ?>logout.php" class="btn-top-logout" title="Sign Out">
                        Sign Out
                    </a>
                </div>
            </div>

            <!-- Global Flash Alert Messages -->
            <?php echo render_flash(); ?>
