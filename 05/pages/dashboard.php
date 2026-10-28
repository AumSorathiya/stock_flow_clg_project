<?php
require_once __DIR__ . '/../config/db.php';
check_login();

// Metrics defaults
$total_sales = 0.00;
$total_purchases = 0.00;
$total_customers = 0;
$total_suppliers = 0;
$total_products = 0;
$low_stock_count = 0;
$recent_sales = [];
$low_stock_products = [];
$db_err = null;

if ($pdo !== null) {
    try {
        // 1. Total Sales Sum
        $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) AS total FROM sales");
        $total_sales = (float)$stmt->fetch()['total'];

        // 2. Total Purchases Sum
        $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) AS total FROM purchases");
        $total_purchases = (float)$stmt->fetch()['total'];

        // 3. Customer Count
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM customers");
        $total_customers = (int)$stmt->fetch()['total'];

        // 4. Supplier Count
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM suppliers");
        $total_suppliers = (int)$stmt->fetch()['total'];

        // 5. Total Products Count & Low Stock Count
        $stmt = $pdo->query("SELECT COUNT(*) AS total, SUM(CASE WHEN quantity <= min_stock THEN 1 ELSE 0 END) AS low_count FROM products");
        $prod_stats = $stmt->fetch();
        $total_products = (int)($prod_stats['total'] ?? 0);
        $low_stock_count = (int)($prod_stats['low_count'] ?? 0);

        // 6. Recent Sales
        $stmt = $pdo->query("SELECT s.*, p.name AS product_name, p.sku, c.name AS customer_name, u.username AS cashier
                             FROM sales s 
                             JOIN products p ON s.product_id = p.id 
                             LEFT JOIN customers c ON s.customer_id = c.id 
                             LEFT JOIN users u ON s.user_id = u.id
                             ORDER BY s.id DESC LIMIT 5");
        $recent_sales = $stmt->fetchAll();

        // 7. Low Stock Alerts
        $stmt = $pdo->query("SELECT p.*, c.name AS category_name 
                             FROM products p 
                             LEFT JOIN categories c ON p.category_id = c.id
                             WHERE p.quantity <= p.min_stock 
                             ORDER BY p.quantity ASC LIMIT 10");
        $low_stock_products = $stmt->fetchAll();

    } catch (Exception $e) {
        $db_err = $e->getMessage();
    }
}

$page_title = 'Stock Flow - Executive Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-title-row">
    <div>
        <h1>Business Overview</h1>
        <p style="color: #64748b; font-size: 14px; margin-top: 4px;">Real-time inventory levels, financial turnover, and supply chain telemetry.</p>
    </div>
    <div>
        <span class="badge badge-info" style="font-size: 13px; padding: 6px 14px;">
            Port <?php echo htmlspecialchars($active_db_port ?? '3306'); ?> Connected
        </span>
    </div>
</div>

<?php if ($db_err): ?>
    <div style="background: #fef2f2; color: #991b1b; padding: 14px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fecaca;">
        <strong>Database Notice:</strong> <?php echo htmlspecialchars($db_err); ?>
    </div>
<?php endif; ?>

<!-- Executive KPI Summary Cards -->
<div id="dashboard">
    <div class="dashboard-item">
        <h2>Total Sales Revenue</h2>
        <p style="color: #059669;">$<?php echo number_format($total_sales, 2); ?></p>
        <div class="card-subtext">Gross billed customer orders</div>
    </div>
    <div class="dashboard-item">
        <h2>Total Procurement</h2>
        <p style="color: #0284c7;">$<?php echo number_format($total_purchases, 2); ?></p>
        <div class="card-subtext">Supplier restocking expenditures</div>
    </div>
    <div class="dashboard-item">
        <h2>Active Customers</h2>
        <p><?php echo number_format($total_customers); ?></p>
        <div class="card-subtext">Registered client accounts</div>
    </div>
    <div class="dashboard-item">
        <h2>Total Vendors</h2>
        <p><?php echo number_format($total_suppliers); ?></p>
        <div class="card-subtext">Verified supply partners</div>
    </div>
</div>

<!-- Secondary Stats Quick Bar -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div style="background: #ffffff; padding: 16px 20px; border-radius: 10px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <span style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Product SKUs</span>
            <div style="font-size: 20px; font-weight: 700; color: #1e293b;"><?php echo $total_products; ?> Items</div>
        </div>
        <span style="font-size: 24px;">📦</span>
    </div>

    <div style="background: #ffffff; padding: 16px 20px; border-radius: 10px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <span style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Low Stock Warning</span>
            <div style="font-size: 20px; font-weight: 700; color: <?php echo ($low_stock_count > 0) ? '#dc2626' : '#059669'; ?>;">
                <?php echo $low_stock_count; ?> Alert<?php echo ($low_stock_count !== 1) ? 's' : ''; ?>
            </div>
        </div>
        <span style="font-size: 24px;">⚠️</span>
    </div>

    <div style="background: #ffffff; padding: 16px 20px; border-radius: 10px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <span style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">System Role</span>
            <div style="font-size: 16px; font-weight: 700; color: #0284c7; text-transform: capitalize;">
                <?php echo htmlspecialchars(current_user_role()); ?> Access
            </div>
        </div>
        <span style="font-size: 24px;">🛡️</span>
    </div>
</div>

<!-- Low Stock Critical Alert Section -->
<?php if (!empty($low_stock_products)): ?>
    <div class="form-card" style="border-left: 4px solid #ef4444;">
        <h2 style="color: #b91c1c;">
            <span>⚠️ Safety Stock Alert: Items Requiring Restock</span>
            <a href="inventory.php" class="btn btn-warning btn-sm">Restock in Inventory</a>
        </h2>
        <div class="table-responsive">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Available Stock</th>
                        <th>Reorder Threshold</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($low_stock_products as $item): ?>
                        <tr>
                            <td style="font-weight: 600; color: #0284c7;"><?php echo htmlspecialchars($item['sku']); ?></td>
                            <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                            <td style="color: #64748b;"><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></td>
                            <td>
                                <strong style="color: #dc2626; font-size: 15px;"><?php echo $item['quantity']; ?></strong>
                            </td>
                            <td style="color: #64748b;"><?php echo $item['min_stock']; ?> units</td>
                            <td>
                                <?php if ($item['quantity'] == 0): ?>
                                    <span class="badge badge-danger">OUT OF STOCK</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">LOW INVENTORY</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Recent Sales Activity Section -->
<div class="form-card" style="max-width: 100%;">
    <h2>
        <span>Recent Sales Orders</span>
        <a href="sales.php" class="btn btn-primary btn-sm">+ New Sale Order</a>
    </h2>
    <div class="table-responsive">
        <table class="app-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Product</th>
                    <th>Customer</th>
                    <th>Quantity</th>
                    <th>Amount</th>
                    <th>Processed By</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_sales)): ?>
                    <tr>
                        <td colspan="7" style="padding: 20px; text-align: center; color: #64748b;">No recent sales transactions recorded.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_sales as $sale): ?>
                        <tr>
                            <td><strong>#SL-<?php echo str_pad($sale['id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($sale['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer'); ?></td>
                            <td><?php echo $sale['quantity']; ?></td>
                            <td style="color: #059669; font-weight: 700;">$<?php echo number_format($sale['total_amount'], 2); ?></td>
                            <td style="color: #64748b; font-size: 13px;">
                                <?php echo htmlspecialchars($sale['cashier'] ?? 'Staff'); ?>
                            </td>
                            <td style="color: #64748b; font-size: 13px;"><?php echo date('M d, Y H:i', strtotime($sale['sale_date'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
