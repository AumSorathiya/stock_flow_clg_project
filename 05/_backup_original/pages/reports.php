<?php
$page_title = 'Stock Flow - Reports & Insights';
require_once __DIR__ . '/../includes/header.php';

$total_sales = 0;
$total_purchases = 0;
$inventory_value = 0;
$top_products = [];
$low_stock_list = [];

if ($pdo !== null) {
    try {
        // Total Sales
        $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) AS total FROM sales");
        $total_sales = $stmt->fetch()['total'];

        // Total Purchases
        $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) AS total FROM purchases");
        $total_purchases = $stmt->fetch()['total'];

        // Inventory Stock Valuation (Price * Quantity)
        $stmt = $pdo->query("SELECT COALESCE(SUM(price * quantity), 0) AS val FROM products");
        $inventory_value = $stmt->fetch()['val'];

        // Top Selling Products
        $stmt = $pdo->query("SELECT p.name, p.sku, SUM(s.quantity) AS total_sold, SUM(s.total_amount) AS revenue 
                             FROM sales s 
                             JOIN products p ON s.product_id = p.id 
                             GROUP BY p.id 
                             ORDER BY revenue DESC LIMIT 5");
        $top_products = $stmt->fetchAll();

        // Low Stock Audit List
        $stmt = $pdo->query("SELECT * FROM products WHERE quantity <= min_stock ORDER BY quantity ASC");
        $low_stock_list = $stmt->fetchAll();

    } catch (Exception $e) {
        $err = $e->getMessage();
    }
}

$gross_profit = $total_sales - $total_purchases;
?>

<h1>Reports & Insights</h1>

<div id="dashboard">
    <div class="dashboard-item">
        <h2>Total Sales Revenue</h2>
        <p style="color: #10b981;">$<?php echo number_format($total_sales, 2); ?></p>
    </div>
    <div class="dashboard-item">
        <h2>Total Purchases Cost</h2>
        <p style="color: #3b82f6;">$<?php echo number_format($total_purchases, 2); ?></p>
    </div>
    <div class="dashboard-item">
        <h2>Gross Profit</h2>
        <p style="color: <?php echo $gross_profit >= 0 ? '#10b981' : '#ef4444'; ?>;">
            $<?php echo number_format($gross_profit, 2); ?>
        </p>
    </div>
    <div class="dashboard-item">
        <h2>Total Inventory Valuation</h2>
        <p style="color: #8b5cf6;">$<?php echo number_format($inventory_value, 2); ?></p>
    </div>
</div>

<br><br>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <!-- Top Selling Products -->
    <div class="form-card" style="max-width: 100%;">
        <h2>🔥 Top Selling Products</h2>
        <br>
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid #e2e8f0; color: #64748b;">
                    <th style="padding: 8px;">Product</th>
                    <th style="padding: 8px;">Units Sold</th>
                    <th style="padding: 8px;">Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($top_products)): ?>
                    <tr><td colspan="3" style="padding: 10px; color: #64748b;">No sales data available yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($top_products as $tp): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 8px; font-weight: 600;"><?php echo htmlspecialchars($tp['name']); ?></td>
                            <td style="padding: 8px; font-weight: 600; color: #0284c7;"><?php echo $tp['total_sold']; ?></td>
                            <td style="padding: 8px; font-weight: 700; color: #10b981;">$<?php echo number_format($tp['revenue'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Low Stock Audit List -->
    <div class="form-card" style="max-width: 100%;">
        <h2>⚠️ Low Stock Report</h2>
        <br>
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid #e2e8f0; color: #64748b;">
                    <th style="padding: 8px;">SKU</th>
                    <th style="padding: 8px;">Product</th>
                    <th style="padding: 8px;">Current Qty</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($low_stock_list)): ?>
                    <tr><td colspan="3" style="padding: 10px; color: #10b981; font-weight: 600;">All products have healthy stock levels!</td></tr>
                <?php else: ?>
                    <?php foreach ($low_stock_list as $ls): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 8px; font-weight: 600; color: #38bdf8;"><?php echo htmlspecialchars($ls['sku']); ?></td>
                            <td style="padding: 8px; font-weight: 600;"><?php echo htmlspecialchars($ls['name']); ?></td>
                            <td style="padding: 8px; font-weight: 700; color: #ef4444;"><?php echo $ls['quantity']; ?> (Min: <?php echo $ls['min_stock']; ?>)</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
