<?php
require_once __DIR__ . '/../config/db.php';

// Strict Server-Side RBAC Enforcement: Restricted to Admin and Manager only!
check_role(['admin', 'manager']);

$total_sales = 0.00;
$total_purchases = 0.00;
$inventory_retail_val = 0.00;
$inventory_cost_val = 0.00;
$total_inventory_units = 0;
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
                             ORDER BY revenue DESC LIMIT 10");
        $top_products = $stmt->fetchAll();

        // 5. Low Stock Audit List
        $stmt = $pdo->query("SELECT p.*, c.name AS category_name 
                             FROM products p 
                             LEFT JOIN categories c ON p.category_id = c.id
                             WHERE p.quantity <= p.min_stock 
                             ORDER BY p.quantity ASC, p.id ASC");
        $low_stock_list = $stmt->fetchAll();

    } catch (Exception $e) {
        $err = $e->getMessage();
    }
}

$gross_profit = $total_sales - $total_purchases;
$profit_margin_pct = ($total_sales > 0) ? ($gross_profit / $total_sales) * 100 : 0.0;
$max_top_revenue = !empty($top_products) ? (float)$top_products[0]['revenue'] : 1.0;
if ($max_top_revenue <= 0) $max_top_revenue = 1.0;

$page_title = 'Stock Flow - Reports & Insights';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-title-row">
    <div>
        <h1>Financial & Inventory Telemetry Reports</h1>
        <p>Comprehensive ledger turnover, gross trading margin analytics, and asset valuation.</p>
    </div>
    <div style="display: flex; gap: 8px;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
            🖨️ Print Report
        </button>
    </div>
</div>

<?php if ($err): ?>
    <div class="flash-alert flash-alert-error">
        <span>⚠️</span>
        <div>Error generating report: <?php echo htmlspecialchars($err); ?></div>
    </div>
<?php endif; ?>

<!-- Financial Performance KPI Grid -->
<div id="dashboard">
    <div class="dashboard-item">
        <h2>
            <span>Gross Sales Revenue</span>
            <span class="kpi-trend trend-up">📈 Invoiced</span>
        </h2>
        <p style="color: #059669;">$<?php echo number_format($total_sales, 2); ?></p>
        <div class="card-subtext">Cumulative sales revenue</div>
    </div>

    <div class="dashboard-item">
        <h2>
            <span>Procurement Outlay</span>
            <span class="kpi-trend trend-neutral">🛍️ Expense</span>
        </h2>
        <p style="color: #0284c7;">$<?php echo number_format($total_purchases, 2); ?></p>
        <div class="card-subtext">Supplier restocking cost</div>
    </div>

    <div class="dashboard-item">
        <h2>
            <span>Net Trading Profit</span>
            <span class="kpi-trend <?php echo ($gross_profit >= 0) ? 'trend-up' : 'trend-neutral'; ?>">
                Margin: <?php echo number_format($profit_margin_pct, 1); ?>%
            </span>
        </h2>
        <p style="color: <?php echo ($gross_profit >= 0) ? '#059669' : '#dc2626'; ?>;">
            $<?php echo number_format($gross_profit, 2); ?>
        </p>
        <div class="card-subtext">
            <span>Operating spread on turnover</span>
        </div>
    </div>

    <div class="dashboard-item">
        <h2>
            <span>Warehouse Asset Valuation</span>
            <span class="kpi-trend trend-neutral">📦 <?php echo number_format($total_inventory_units); ?> units</span>
        </h2>
        <p style="color: #7c3aed;">$<?php echo number_format($inventory_retail_val, 2); ?></p>
        <div class="card-subtext">
            <span>Cost basis: $<?php echo number_format($inventory_cost_val, 2); ?></span>
        </div>
    </div>
</div>

<!-- Gross Profit Margin Progress Gauge Card -->
<div class="form-card" style="margin-bottom: 24px;">
    <h2>
        <span>📊 Gross Profit Margin Efficiency</span>
        <span style="font-size: 14px; font-weight: 700; color: <?php echo ($profit_margin_pct >= 20) ? '#059669' : (($profit_margin_pct >= 10) ? '#d97706' : '#dc2626'); ?>;">
            <?php echo number_format($profit_margin_pct, 1); ?>% Profit Margin
        </span>
    </h2>

    <div style="background: #f1f5f9; height: 12px; border-radius: 9999px; overflow: hidden; margin: 10px 0 16px;">
        <div style="height: 100%; border-radius: 9999px; background: linear-gradient(90deg, #10b981 0%, #059669 100%); width: <?php echo min(100, max(0, $profit_margin_pct)); ?>%; transition: width 0.8s ease;"></div>
    </div>

    <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted);">
        <span>0% Break-Even</span>
        <span>15% Industry Benchmark</span>
        <span>30%+ High Return</span>
        <span>100% Max</span>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 24px;">
    <!-- Top Selling Products Section -->
    <div class="form-card" style="margin-bottom: 0;">
        <h2>
            <span>🔥 Top Revenue Generators (<?php echo count($top_products); ?>)</span>
            <button type="button" class="btn-export-csv" onclick="exportTableToCSV('reportsTopTable', 'top_revenue_generators')">
                📥 Export CSV
            </button>
        </h2>

        <!-- Table Filter Toolbar for Top Products -->
        <div class="table-filter-bar" data-table="reportsTopTable">
            <div class="table-filter-inputs">
                <div class="filter-input-wrapper">
                    <span class="filter-icon">🔍</span>
                    <input type="text" class="filter-control filter-search" placeholder="Search product name, SKU...">
                </div>
                <button type="button" class="btn-filter-reset">↺ Reset</button>
            </div>
            <div class="table-filter-meta">
                <span class="filter-count-badge">Showing <?php echo count($top_products); ?> of <?php echo count($top_products); ?></span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="app-table" id="reportsTopTable">
                <thead>
                    <tr>
                        <th style="width: 60px;">Rank</th>
                        <th>Product & SKU</th>
                        <th>Units Sold</th>
                        <th>Revenue Contribution</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_products)): ?>
                        <tr class="empty-state-row">
                            <td colspan="4" style="padding: 24px; text-align: center; color: var(--text-muted);">
                                No sales transactions recorded yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $rank = 1; foreach ($top_products as $tp): 
                            $pct_of_top = ($tp['revenue'] / $max_top_revenue) * 100;
                        ?>
                            <tr data-name="<?php echo htmlspecialchars($tp['name']); ?>"
                                data-amount="<?php echo $tp['revenue']; ?>">
                                <td style="font-weight: 800; color: #4f46e5; text-align: center;">
                                    #<?php echo $rank++; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($tp['name']); ?></strong>
                                    <span class="cell-sku" style="display: block; font-size: 11.5px;"><?php echo htmlspecialchars($tp['sku']); ?></span>
                                </td>
                                <td><span class="badge badge-info"><?php echo $tp['total_sold']; ?> pcs</span></td>
                                <td>
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px;">
                                        <strong style="color: #059669;">$<?php echo number_format($tp['revenue'], 2); ?></strong>
                                        <span style="font-size: 11px; color: var(--text-muted);"><?php echo number_format($pct_of_top, 0); ?>%</span>
                                    </div>
                                    <div class="progress-container">
                                        <div class="progress-bar-fill" style="width: <?php echo min(100, $pct_of_top); ?>%;"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Low Stock Inventory Audit Section -->
    <div class="form-card" style="margin-bottom: 0;">
        <h2>
            <span style="display: flex; align-items: center; gap: 8px;">
                <span class="pulse-dot pulse-amber"></span>
                <span>Safety Stock Audit (<?php echo count($low_stock_list); ?> items)</span>
            </span>
            <button type="button" class="btn-export-csv" onclick="exportTableToCSV('reportsSafetyTable', 'safety_stock_audit')">
                📥 Export CSV
            </button>
        </h2>

        <!-- Table Filter Toolbar for Safety Stock -->
        <div class="table-filter-bar" data-table="reportsSafetyTable">
            <div class="table-filter-inputs">
                <div class="filter-input-wrapper">
                    <span class="filter-icon">🔍</span>
                    <input type="text" class="filter-control filter-search" placeholder="Search ID, SKU, product...">
                </div>
                <button type="button" class="btn-filter-reset">↺ Reset</button>
            </div>
            <div class="table-filter-meta">
                <span class="filter-count-badge">Showing <?php echo count($low_stock_list); ?> of <?php echo count($low_stock_list); ?></span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="app-table" id="reportsSafetyTable">
                <thead>
                    <tr>
                        <th style="width: 70px;">ID</th>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>Current Units</th>
                        <th>Min Threshold</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($low_stock_list)): ?>
                        <tr class="empty-state-row">
                            <td colspan="6" style="padding: 24px; text-align: center; color: #059669; font-weight: 600;">
                                ✓ All catalog items meet safety stock thresholds.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($low_stock_list as $ls): ?>
                            <tr data-id="<?php echo $ls['id']; ?>" 
                                data-sku="<?php echo htmlspecialchars($ls['sku']); ?>"
                                data-name="<?php echo htmlspecialchars($ls['name']); ?>"
                                data-stock="<?php echo $ls['quantity']; ?>">
                                <td class="cell-id">#<?php echo $ls['id']; ?></td>
                                <td class="cell-sku"><?php echo htmlspecialchars($ls['sku']); ?></td>
                                <td><strong><?php echo htmlspecialchars($ls['name']); ?></strong></td>
                                <td>
                                    <strong style="color: #dc2626;"><?php echo $ls['quantity']; ?></strong> units
                                </td>
                                <td style="color: var(--text-muted);"><?php echo $ls['min_stock']; ?> units</td>
                                <td>
                                    <?php if ($ls['quantity'] == 0): ?>
                                        <span class="badge badge-danger">
                                            <span class="pulse-dot pulse-rose" style="width:4px;height:4px;"></span> Out
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">
                                            <span class="pulse-dot pulse-amber" style="width:4px;height:4px;"></span> Low
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
