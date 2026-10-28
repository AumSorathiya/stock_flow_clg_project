<?php
$is_pages_dir = strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false;
$p_link = $is_pages_dir ? '' : 'pages/';
$root_link = $is_pages_dir ? '../' : './';
$current_page = basename($_SERVER['PHP_SELF']);

$user_role = current_user_role();
$user_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
$user_initial = strtoupper(substr($user_name, 0, 1));
?>
<div id="leftsidebar">
    <div class="brand-header">
        <h1>📊 Stock Flow</h1>
    </div>

    <!-- Active User Profile Card -->
    <div class="sidebar-user-card">
        <div class="user-avatar"><?php echo htmlspecialchars($user_initial); ?></div>
        <div class="user-info">
            <div class="user-name" title="<?php echo htmlspecialchars($user_name); ?>"><?php echo htmlspecialchars($user_name); ?></div>
            <span class="role-badge role-<?php echo htmlspecialchars($user_role); ?>">
                <?php echo strtoupper(htmlspecialchars($user_role)); ?>
            </span>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div class="sidebar-nav-section">
        <div class="sidebar-heading">Overview</div>
        <h4>
            <a href="<?php echo $p_link; ?>dashboard.php" class="<?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
                <span>🏠</span> Dashboard
            </a>
        </h4>
    </div>

    <div class="sidebar-nav-section">
        <div class="sidebar-heading">Catalog</div>
        <h4>
            <a href="<?php echo $p_link; ?>products.php" class="<?php echo ($current_page === 'products.php') ? 'active' : ''; ?>">
                <span>📦</span> Products
            </a>
        </h4>
        <h4>
            <a href="<?php echo $p_link; ?>categories.php" class="<?php echo ($current_page === 'categories.php') ? 'active' : ''; ?>">
                <span>🏷️</span> Categories
            </a>
        </h4>
        <h4>
            <a href="<?php echo $p_link; ?>inventory.php" class="<?php echo ($current_page === 'inventory.php') ? 'active' : ''; ?>">
                <span>📑</span> Inventory
            </a>
        </h4>
    </div>

    <div class="sidebar-nav-section">
        <div class="sidebar-heading">Transactions</div>
        <h4>
            <a href="<?php echo $p_link; ?>sales.php" class="<?php echo ($current_page === 'sales.php') ? 'active' : ''; ?>">
                <span>💰</span> Sales
            </a>
        </h4>
        <h4>
            <a href="<?php echo $p_link; ?>purchases.php" class="<?php echo ($current_page === 'purchases.php') ? 'active' : ''; ?>">
                <span>🛍️</span> Purchases
            </a>
        </h4>
    </div>

    <div class="sidebar-nav-section">
        <div class="sidebar-heading">Contacts</div>
        <h4>
            <a href="<?php echo $p_link; ?>customers.php" class="<?php echo ($current_page === 'customers.php') ? 'active' : ''; ?>">
                <span>👥</span> Customers
            </a>
        </h4>
        <h4>
            <a href="<?php echo $p_link; ?>suppliers.php" class="<?php echo ($current_page === 'suppliers.php') ? 'active' : ''; ?>">
                <span>🏬</span> Suppliers
            </a>
        </h4>
    </div>

    <?php if (has_role(['admin', 'manager'])): ?>
    <div class="sidebar-nav-section">
        <div class="sidebar-heading">Insights</div>
        <h4>
            <a href="<?php echo $p_link; ?>reports.php" class="<?php echo ($current_page === 'reports.php') ? 'active' : ''; ?>">
                <span>📈</span> Reports
            </a>
        </h4>
    </div>
    <?php endif; ?>

    <?php if (has_role('admin')): ?>
    <div class="sidebar-nav-section">
        <div class="sidebar-heading">Administration</div>
        <h4>
            <a href="<?php echo $p_link; ?>users.php" class="<?php echo ($current_page === 'users.php') ? 'active' : ''; ?>">
                <span>🔐</span> User Management
            </a>
        </h4>
        <h4>
            <a href="<?php echo $p_link; ?>settings.php" class="<?php echo ($current_page === 'settings.php') ? 'active' : ''; ?>">
                <span>⚙️</span> System Settings
            </a>
        </h4>
    </div>
    <?php endif; ?>

    <div class="sidebar-nav-section" id="logout-link" style="margin-top: 18px; border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 12px;">
        <h4>
            <a href="<?php echo $root_link; ?>logout.php">
                <span>🚪</span> Sign Out
            </a>
        </h4>
    </div>
</div>
<!-- Mobile Backdrop -->
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleMobileSidebar()"></div>
