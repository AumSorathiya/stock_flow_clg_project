<?php
require_once __DIR__ . '/../config/db.php';

// Strict Server-Side RBAC Enforcement: Restricted to Admin and Manager only!
check_role(['admin', 'manager']);

$total_sales = 0.00;
$total_purchases = 0.00;
$inventory_retail_val = 0.00;
$inventory_cost_val = 0.00;
$top_products = [];
$low_stock_list = [];
$err = null;

if ($pdo !== null) {
    try {
        // 1. Total Sales Revenue
        $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) AS total FROM sales");
        $total_sales = (float)$stmt->fetch()['total'];

        // 2. Total Purchases Cost
        $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) AS total FROM purchases");
        $total_purchases = (float)$stmt->fetch()['total'];

        // 3. Inventory Stock Valuation (Retail and Cost)
        $stmt = $pdo->query("SELECT 
                                COALESCE(SUM(price * quantity), 0) AS retail_val,
                                COALESCE(SUM(cost * quantity), 0) AS cost_val,
                                COALESCE(SUM(quantity), 0) AS total_units
                             FROM products");
        $inv_data = $stmt->fetch();
        $inventory_retail_val = (float)$inv_data['retail_val'];
        $inventory_cost_val = (float)$inv_data['cost_val'];
        $total_inventory_units = (int)$inv_data['total_units'];

        // 4. Top Selling Products
        $stmt = $pdo->query("SELECT p.name, p.sku, SUM(s.quantity) AS total_sold, SUM(s.total_amount) AS revenue 
                             FROM sales s 
                             JOIN products p ON s.product_id = p.id 
                             GROUP BY p.id 
                             ORDER BY revenue DESC LIMIT 5");
        $top_products = $stmt->fetchAll();

        // 5. Low Stock Audit List
        $stmt = $pdo->query("SELECT p.*, c.name AS category_name 
                             FROM products p 
                             LEFT JOIN categories c ON p.category_id = c.id
                             WHERE p.quantity <= p.min_stock 
                             ORDER BY p.quantity ASC");
        $low_stock_list = $stmt->fetchAll();

    } catch (Exception $e) {
        $err = $e->getMessage();
    }
}

$gross_profit = $total_sales - $total_purchases;
$profit_margin_pct = ($total_sales > 0) ? ($gross_profit / $total_sales) * 100 : 0.0;

$page_title = 'Stock Flow - Reports & Insights';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-title-row">
    <div>
        <h1>Financial & Inventory Reports</h1>
        <p style="color: #64748b; font-size: 14px; margin-top: 4px;">Comprehensive financial turnover, gross margin analytics, and asset valuation.</p>
    </div>
    <div style="display: flex; gap: 8px;">
        <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm">
            🖨️ Print Report
        </button>
    </div>
</div>

<?php if ($err): ?>
    <div style="background: #fef2f2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
        Error generating report: <?php echo htmlspecialchars($err); ?>
    </div>
<?php endif; ?>

<!-- Financial Performance KPI Grid -->
<div id="dashboard">
    <div class="dashboard-item">
        <h2>Gross Sales Revenue</h2>
        <p style="color: #059669;">$<?php echo number_format($total_sales, 2); ?></p>
        <div class="card-subtext">Cumulative sales revenue</div>
    </div>
    <div class="dashboard-item">
        <h2>Total Procurement Expense</h2>
        <p style="color: #0284c7;">$<?php echo number_format($total_purchases, 2); ?></p>
        <div class="card-subtext">Restocking outlay</div>
    </div>
    <div class="dashboard-item">
        <h2>Net Trading Profit</h2>
        <p style="color: <?php echo ($gross_profit >= 0) ? '#059669' : '#dc2626'; ?>;">
            $<?php echo number_format($gross_profit, 2); ?>
        </p>
        <div class="card-subtext">
            Margin: <strong><?php echo number_format($profit_margin_pct, 1); ?>%</strong> of revenue
        </div>
    </div>
    <div class="dashboard-item">
        <h2>Total Inventory Asset Value</h2>
        <p style="color: #7c3aed;">$<?php echo number_format($inventory_retail_val, 2); ?></p>
        <div class="card-subtext">
            Cost basis: $<?php echo number_format($inventory_cost_val, 2); ?> (<?php echo number_format($total_inventory_units ?? 0); ?> units)
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px;">
    <!-- Top Selling Products Section -->
    <div class="form-card" style="margin-bottom: 0;">
        <h2>🔥 Top Revenue Generators</h2>
        <div class="table-responsive">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Units Sold</th>
                        <th>Revenue Generated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_products)): ?>
                        <tr><td colspan="3" style="padding: 20px; text-align: center; color: #64748b;">No recorded sales transactions yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($top_products as $tp): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($tp['name']); ?></strong>
                                    <span style="font-size: 11px; color: #64748b; display: block;"><?php echo htmlspecialchars($tp['sku']); ?></span>
                                </td>
                                <td><span class="badge badge-info"><?php echo $tp['total_sold']; ?> units</span></td>
                                <td style="font-weight: 700; color: #059669;">$<?php echo number_format($tp['revenue'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Low Stock Inventory Audit Section -->
    <div class="form-card" style="margin-bottom: 0;">
        <h2>⚠️ Safety Stock Audit</h2>
        <div class="table-responsive">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>Current</th>
                        <th>Min Threshold</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($low_stock_list)): ?>
                        <tr><td colspan="4" style="padding: 20px; text-align: center; color: #059669; font-weight: 600;">✓ All items meet safety stock thresholds.</td></tr>
                    <?php else: ?>
                        <?php foreach ($low_stock_list as $ls): ?>
                            <tr>
                                <td style="font-weight: 600; color: #0284c7;"><?php echo htmlspecialchars($ls['sku']); ?></td>
                                <td><strong><?php echo htmlspecialchars($ls['name']); ?></strong></td>
                                <td>
                                    <strong style="color: #dc2626;"><?php echo $ls['quantity']; ?></strong>
                                </td>
                                <td style="color: #64748b;"><?php echo $ls['min_stock']; ?> units</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
