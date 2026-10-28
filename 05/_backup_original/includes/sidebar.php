<?php
$is_pages_dir = strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false;
$p_link = $is_pages_dir ? '' : 'pages/';
$root_link = $is_pages_dir ? '../' : './';
?>
<div id="leftsidebar">
    <h1>📊 Stock Flow</h1>
    <div>
        <p id="lsheading">OVERVIEW</p>
        <h4><a href="<?php echo $p_link; ?>dashboard.php">Dashboard</a></h4>
    </div>
    <div>
        <p id="lsheading">CATALOG</p>
        <h4><a href="<?php echo $p_link; ?>products.php">Product</a></h4>
        <h4><a href="<?php echo $p_link; ?>categories.php">Category</a></h4>
        <h4><a href="<?php echo $p_link; ?>inventory.php">Inventory</a></h4>
    </div>
    <div>
        <p id="lsheading">TRANSACTIONS</p>
        <h4><a href="<?php echo $p_link; ?>sales.php">Sales</a></h4>
        <h4><a href="<?php echo $p_link; ?>purchases.php">Purchases</a></h4>
    </div>
    <div>
        <p id="lsheading">CONTACTS</p>
        <h4><a href="<?php echo $p_link; ?>customers.php">Customer</a></h4>
        <h4><a href="<?php echo $p_link; ?>suppliers.php">Suppliers</a></h4>
    </div>
    <div>
        <p id="lsheading">INSIGHTS</p>
        <h4><a href="<?php echo $p_link; ?>reports.php">Reports</a></h4>
        <h4><a href="<?php echo $p_link; ?>settings.php">Settings</a></h4>
    </div>
    <div id="logout">
        <h4><a href="<?php echo $root_link; ?>logout.php">Logout</a></h4>
    </div>
</div>
