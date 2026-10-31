<?php
$is_pages_dir = strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false;
$p_link = $is_pages_dir ? '' : 'pages/';
$root_link = $is_pages_dir ? '../' : './';
$current_page = basename($_SERVER['PHP_SELF']);

$user_role = current_user_role();
$user_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
$user_initial = strtoupper(substr($user_name, 0, 1));

// Telemetry counters for sidebar chips
$sidebar_low_count = 0;
$sidebar_prod_count = 0;
if (isset($pdo) && $pdo !== null) {
    try {
        $stmt_side = $pdo->query("SELECT COUNT(*) AS total, SUM(CASE WHEN quantity <= min_stock THEN 1 ELSE 0 END) AS low_count FROM products");
        $side_res = $stmt_side->fetch();
        $sidebar_prod_count = (int)($side_res['total'] ?? 0);
        $sidebar_low_count = (int)($side_res['low_count'] ?? 0);
    } catch (Exception $e) {
        // Silently tolerate if connection isn't ready
    }
}
?>
<div id="leftsidebar">
    <div class="brand-header">
        <h1>
            <span>📊</span>
            <span>Stock Flow</span>
        </h1>
        <span class="brand-badge">PRO</span>
    </div>

    <!-- Active User Profile Card -->
    <div class="sidebar-user-card">
        <div class="user-avatar" title="<?php echo htmlspecialchars($user_name); ?>">
            <?php echo htmlspecialchars($user_initial); ?>
        </div>
        <div class="user-info">
            <div class="user-name" title="<?php echo htmlspecialchars($user_name); ?>">
                <?php echo htmlspecialchars($user_name); ?>
            </div>
            <span class="role-badge role-<?php echo htmlspecialchars($user_role); ?>">
                <span class="pulse-dot pulse-emerald" style="width: 5px; height: 5px;"></span>
                <?php echo strtoupper(htmlspecialchars($user_role)); ?>
            </span>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div class="sidebar-nav-section">
        <div class="sidebar-heading">Analytics</div>
        <h4>
            <a href="<?php echo $p_link; ?>dashboard.php" class="<?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
                <span class="nav-link-left">
                    <span class="nav-icon">🏠</span>
                    <span>Dashboard</span>
                </span>
            </a>
        </h4>
    </div>

    <div class="sidebar-nav-section">
        <div class="sidebar-heading">Inventory & Catalog</div>
        <h4>
            <a href="<?php echo $p_link; ?>products.php" class="<?php echo ($current_page === 'products.php') ? 'active' : ''; ?>">
                <span class="nav-link-left">
                    <span class="nav-icon">📦</span>
                    <span>Products</span>
                </span>
                <?php if ($sidebar_prod_count > 0): ?>
                    <span class="nav-pill-badge"><?php echo $sidebar_prod_count; ?></span>
                <?php endif; ?>
            </a>
        </h4>
        <h4>
            <a href="<?php echo $p_link; ?>categories.php" class="<?php echo ($current_page === 'categories.php') ? 'active' : ''; ?>">
                <span class="nav-link-left">
                    <span class="nav-icon">🏷️</span>
                    <span>Categories</span>
                </span>
            </a>
        </h4>
        <h4>
            <a href="<?php echo $p_link; ?>inventory.php" class="<?php echo ($current_page === 'inventory.php') ? 'active' : ''; ?>">
                <span class="nav-link-left">
                    <span class="nav-icon">📑</span>
                    <span>Inventory</span>
                </span>
                <?php if ($sidebar_low_count > 0): ?>
                    <span class="nav-pill-badge badge-alert" title="<?php echo $sidebar_low_count; ?> items low or out of stock">
                        ⚠️ <?php echo $sidebar_low_count; ?>
                    </span>
                <?php endif; ?>
            </a>
        </h4>
    </div>

    <div class="sidebar-nav-section">
        <div class="sidebar-heading">Transactions</div>
        <h4>
            <a href="<?php echo $p_link; ?>sales.php" class="<?php echo ($current_page === 'sales.php') ? 'active' : ''; ?>">
                <span class="nav-link-left">
                    <span class="nav-icon">💰</span>
                    <span>Sales Orders</span>
                </span>
            </a>
        </h4>
        <h4>
            <a href="<?php echo $p_link; ?>purchases.php" class="<?php echo ($current_page === 'purchases.php') ? 'active' : ''; ?>">
                <span class="nav-link-left">
                    <span class="nav-icon">🛍️</span>
                    <span>Purchases</span>
                </span>
            </a>
        </h4>
    </div>

    <div class="sidebar-nav-section">
        <div class="sidebar-heading">Partners & CRM</div>
        <h4>
            <a href="<?php echo $p_link; ?>customers.php" class="<?php echo ($current_page === 'customers.php') ? 'active' : ''; ?>">
                <span class="nav-link-left">
                    <span class="nav-icon">👥</span>
                    <span>Customers</span>
                </span>
            </a>
        </h4>
        <h4>
            <a href="<?php echo $p_link; ?>suppliers.php" class="<?php echo ($current_page === 'suppliers.php') ? 'active' : ''; ?>">
                <span class="nav-link-left">
                    <span class="nav-icon">🏬</span>
                    <span>Suppliers</span>
                </span>
            </a>
        </h4>
    </div>

    <?php if (has_role(['admin', 'manager'])): ?>
    <div class="sidebar-nav-section">
        <div class="sidebar-heading">Intelligence</div>
        <h4>
            <a href="<?php echo $p_link; ?>reports.php" class="<?php echo ($current_page === 'reports.php') ? 'active' : ''; ?>">
                <span class="nav-link-left">
                    <span class="nav-icon">📈</span>
                    <span>Financial Reports</span>
                </span>
            </a>
        </h4>
    </div>
    <?php endif; ?>

    <?php if (has_role('admin')): ?>
    <div class="sidebar-nav-section">
        <div class="sidebar-heading">System Governance</div>
        <h4>
            <a href="<?php echo $p_link; ?>users.php" class="<?php echo ($current_page === 'users.php') ? 'active' : ''; ?>">
                <span class="nav-link-left">
                    <span class="nav-icon">🔐</span>
                    <span>Users & Access</span>
                </span>
            </a>
        </h4>
        <h4>
            <a href="<?php echo $p_link; ?>settings.php" class="<?php echo ($current_page === 'settings.php') ? 'active' : ''; ?>">
                <span class="nav-link-left">
                    <span class="nav-icon">⚙️</span>
                    <span>Settings & Logs</span>
                </span>
            </a>
        </h4>
    </div>
    <?php endif; ?>

    <div class="sidebar-nav-section" id="logout-link" style="margin-top: 24px; border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 14px;">
        <h4>
            <a href="<?php echo $root_link; ?>logout.php">
                <span class="nav-link-left">
                    <span class="nav-icon">🚪</span>
                    <span>Sign Out</span>
                </span>
            </a>
        </h4>
    </div>
</div>
<!-- Mobile Backdrop -->
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleMobileSidebar()"></div>
