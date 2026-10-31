<?php
require_once __DIR__ . '/../config/db.php';
check_login();

// Base URL detection for stylesheet & navigation links
$is_pages_dir = strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false;
$base_prefix = $is_pages_dir ? '../' : './';
$pages_prefix = $is_pages_dir ? '' : 'pages/';

$page_title = isset($page_title) ? $page_title : 'Stock Flow';
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
