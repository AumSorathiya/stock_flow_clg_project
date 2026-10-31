<?php
$page_title = 'Stock Flow - Dashboard';
require_once __DIR__ . '/../includes/header.php';

// Default metrics fallback
$total_sales = 0;
$total_purchases = 0;
$total_customers = 0;
$total_suppliers = 0;
$recent_sales = [];
$low_stock_products = [];

if ($pdo !== null) {
    try {
        // Fetch Total Sales Sum
        $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) AS total FROM sales");
        $total_sales = $stmt->fetch()['total'];

        // Fetch Total Purchases Sum
        $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) AS total FROM purchases");
        $total_purchases = $stmt->fetch()['total'];

        // Fetch Customer Count
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM customers");
        $total_customers = $stmt->fetch()['total'];

        // Fetch Supplier Count
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM suppliers");
        $total_suppliers = $stmt->fetch()['total'];

        // Fetch Recent Sales
        $stmt = $pdo->query("SELECT s.*, p.name AS product_name, c.name AS customer_name 
                             FROM sales s 
                             JOIN products p ON s.product_id = p.id 
                             LEFT JOIN customers c ON s.customer_id = c.id 
                             ORDER BY s.sale_date DESC LIMIT 5");
        $recent_sales = $stmt->fetchAll();

        // Fetch Low Stock Alerts
        $stmt = $pdo->query("SELECT * FROM products WHERE quantity <= min_stock ORDER BY quantity ASC");
        $low_stock_products = $stmt->fetchAll();

    } catch (Exception $e) {
        $db_err = $e->getMessage();
    }
}
?>

<h1>Dashboard</h1>

<?php if (isset($db_err)): ?>
    <div style="background: #fef2f2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
        Database Note: <?php echo htmlspecialchars($db_err); ?> (Make sure MySQL database <code>stock_flow</code> is imported from <code>db/schema.sql</code>).
    </div>
<?php endif; ?>

<div id="dashboard">
    <div class="dashboard-item">
        <h2>Total Sales</h2>
        <p>$<?php echo number_format($total_sales, 2); ?></p>
    </div>
    <div class="dashboard-item">
        <h2>Total Purchases</h2>
        <p>$<?php echo number_format($total_purchases, 2); ?></p>
    </div>
    <div class="dashboard-item">
        <h2>Total Customers</h2>
        <p><?php echo number_format($total_customers); ?></p>
    </div>
    <div class="dashboard-item">
        <h2>Total Suppliers</h2>
        <p><?php echo number_format($total_suppliers); ?></p>
    </div>
</div>

<br><br>

<!-- Low Stock Alerts Section -->
<?php if (!empty($low_stock_products)): ?>
    <div class="form-card" style="max-width: 100%; border-left: 4px solid #ef4444;">
        <h2 style="color: #ef4444;">⚠️ Low Stock Alert</h2>
        <br>
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid #e2e8f0; color: #64748b;">
                    <th style="padding: 10px;">SKU</th>
                    <th style="padding: 10px;">Product Name</th>
                    <th style="padding: 10px;">Current Stock</th>
                    <th style="padding: 10px;">Min Threshold</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($low_stock_products as $item): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 10px; font-weight: 600;"><?php echo htmlspecialchars($item['sku']); ?></td>
                        <td style="padding: 10px;"><?php echo htmlspecialchars($item['name']); ?></td>
                        <td style="padding: 10px; color: #ef4444; font-weight: 700;"><?php echo $item['quantity']; ?></td>
                        <td style="padding: 10px;"><?php echo $item['min_stock']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Recent Sales Activity Section -->
<div class="form-card" style="max-width: 100%;">
    <h2>Recent Sales Transactions</h2>
    <br>
    <?php if (empty($recent_sales)): ?>
        <p style="color: #64748b;">No recent sales transactions recorded.</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid #e2e8f0; color: #64748b;">
                    <th style="padding: 10px;">ID</th>
                    <th style="padding: 10px;">Product</th>
                    <th style="padding: 10px;">Customer</th>
                    <th style="padding: 10px;">Qty</th>
                    <th style="padding: 10px;">Total</th>
                    <th style="padding: 10px;">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_sales as $sale): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 10px;">#<?php echo $sale['id']; ?></td>
                        <td style="padding: 10px; font-weight: 600;"><?php echo htmlspecialchars($sale['product_name']); ?></td>
                        <td style="padding: 10px;"><?php echo htmlspecialchars($sale['customer_name'] ?? 'N/A'); ?></td>
                        <td style="padding: 10px;"><?php echo $sale['quantity']; ?></td>
                        <td style="padding: 10px; color: #10b981; font-weight: 700;">$<?php echo number_format($sale['total_amount'], 2); ?></td>
                        <td style="padding: 10px; color: #64748b;"><?php echo date('M d, Y', strtotime($sale['sale_date'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
