<?php
require_once __DIR__ . '/../config/db.php';
check_login();

// Metrics defaults
$total_sales = 0.00;
$total_purchases = 0.00;
$total_customers = 0;
$total_suppliers = 0;
$total_products = 0;
$healthy_stock_count = 0;
$low_stock_count = 0;
$out_of_stock_count = 0;
$inventory_retail_val = 0.00;
$recent_sales = [];
$low_stock_products = [];
$chart_months = [];
$chart_sales = [];
$chart_purchases = [];
$db_err = null;

if ($pdo !== null) {
    try {
        // 1. Total Sales Sum
        $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) AS total FROM sales");
        $total_sales = (float)$stmt->fetch()['total'];

        // 2. Total Purchases Sum
        $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) AS total FROM purchases");
        $total_purchases = (float)$stmt->fetch()['total'];

        // 3. Customer & Supplier Counts
        $total_customers = (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
        $total_suppliers = (int)$pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();

        // 4. Products Catalog & Stock Health Breakdown
        $stmt = $pdo->query("SELECT 
                                COUNT(*) AS total, 
                                COALESCE(SUM(price * quantity), 0) AS retail_val,
                                SUM(CASE WHEN quantity > min_stock THEN 1 ELSE 0 END) AS healthy_count,
                                SUM(CASE WHEN quantity <= min_stock AND quantity > 0 THEN 1 ELSE 0 END) AS low_count,
                                SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) AS out_count
                             FROM products");
        $prod_stats = $stmt->fetch();
        $total_products      = (int)($prod_stats['total'] ?? 0);
        $inventory_retail_val = (float)($prod_stats['retail_val'] ?? 0);
        $healthy_stock_count = (int)($prod_stats['healthy_count'] ?? 0);
        $low_stock_count     = (int)($prod_stats['low_count'] ?? 0);
        $out_of_stock_count  = (int)($prod_stats['out_count'] ?? 0);

        // 5. Recent 6-Month Telemetry for Revenue vs Procurement Chart
        // Generate trailing 6 month keys
        for ($i = 5; $i >= 0; $i--) {
            $mKey = date('Y-m', strtotime("-$i months"));
            $mLabel = date('M Y', strtotime("-$i months"));
            $chart_months[$mKey] = $mLabel;
            $chart_sales[$mKey] = 0.00;
            $chart_purchases[$mKey] = 0.00;
        }

        // Fetch sales aggregated by month
        $s_stmt = $pdo->query("SELECT DATE_FORMAT(sale_date, '%Y-%m') AS ym, COALESCE(SUM(total_amount), 0) AS total 
                               FROM sales 
                               WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                               GROUP BY ym");
        while ($r = $s_stmt->fetch()) {
            if (isset($chart_sales[$r['ym']])) {
                $chart_sales[$r['ym']] = (float)$r['total'];
            }
        }

        // Fetch purchases aggregated by month
        $p_stmt = $pdo->query("SELECT DATE_FORMAT(purchase_date, '%Y-%m') AS ym, COALESCE(SUM(total_amount), 0) AS total 
                               FROM purchases 
                               WHERE purchase_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                               GROUP BY ym");
        while ($r = $p_stmt->fetch()) {
            if (isset($chart_purchases[$r['ym']])) {
                $chart_purchases[$r['ym']] = (float)$r['total'];
            }
        }

        // 6. Recent Sales Orders (Left Join for resilience)
        $stmt = $pdo->query("SELECT s.*, p.name AS product_name, p.sku, c.name AS customer_name, u.username AS cashier
                             FROM sales s 
                             LEFT JOIN products p ON s.product_id = p.id 
                             LEFT JOIN customers c ON s.customer_id = c.id 
                             LEFT JOIN users u ON s.user_id = u.id
                             ORDER BY s.id DESC LIMIT 10");
        $recent_sales = $stmt->fetchAll();

        // 7. Low Stock Alerts
        $stmt = $pdo->query("SELECT p.*, c.name AS category_name 
                             FROM products p 
                             LEFT JOIN categories c ON p.category_id = c.id
                             WHERE p.quantity <= p.min_stock 
                             ORDER BY p.quantity ASC, p.id ASC LIMIT 15");
        $low_stock_products = $stmt->fetchAll();

    } catch (Exception $e) {
        $db_err = $e->getMessage();
    }
}

$gross_profit = $total_sales - $total_purchases;
$page_title = 'Stock Flow - Executive Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-title-row">
    <div>
        <h1>Executive Dashboard</h1>
        <p>Live inventory telemetry, operational velocity, and gross margin analytics.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <span class="live-telemetry-pill">
            <span class="pulse-dot pulse-emerald"></span>
            <span>MySQL Port <?php echo htmlspecialchars($active_db_port ?? '3000'); ?> Live</span>
        </span>
    </div>
</div>

<?php if ($db_err): ?>
    <div class="flash-alert flash-alert-error">
        <span>⚠️</span>
        <div><strong>Database Notice:</strong> <?php echo htmlspecialchars($db_err); ?></div>
    </div>
<?php endif; ?>

<!-- 1. Executive KPI Summary Cards -->
<div id="dashboard">
    <div class="dashboard-item">
        <h2>
            <span>Gross Sales Revenue</span>
            <span class="kpi-trend trend-up">📈 Growth</span>
        </h2>
        <p style="color: #059669;">$<?php echo number_format($total_sales, 2); ?></p>
        <div class="card-subtext">
            <span>Cumulative billed customer revenue</span>
        </div>
    </div>

    <div class="dashboard-item">
        <h2>
            <span>Procurement Outlay</span>
            <span class="kpi-trend trend-neutral">🛍️ Inbound</span>
        </h2>
        <p style="color: #0284c7;">$<?php echo number_format($total_purchases, 2); ?></p>
        <div class="card-subtext">
            <span>Supplier purchasing expenditures</span>
        </div>
    </div>

    <div class="dashboard-item">
        <h2>
            <span>Net Trading Margin</span>
            <span class="kpi-trend <?php echo ($gross_profit >= 0) ? 'trend-up' : 'trend-neutral'; ?>">
                <?php echo ($gross_profit >= 0) ? '🟢 Surplus' : '🔴 Deficit'; ?>
            </span>
        </h2>
        <p style="color: <?php echo ($gross_profit >= 0) ? '#059669' : '#dc2626'; ?>;">
            $<?php echo number_format($gross_profit, 2); ?>
        </p>
        <div class="card-subtext">
            <span>Gross sales minus procurement cost</span>
        </div>
    </div>

    <div class="dashboard-item">
        <h2>
            <span>Inventory Valuation</span>
            <span class="kpi-trend trend-neutral">📦 <?php echo $total_products; ?> SKUs</span>
        </h2>
        <p style="color: #7c3aed;">$<?php echo number_format($inventory_retail_val, 2); ?></p>
        <div class="card-subtext">
            <span>Current warehouse stock retail value</span>
        </div>
    </div>
</div>

<!-- 2. Interactive Charts Grid (Chart.js + SVG Fallback) -->
<div class="charts-grid">
    <!-- Chart 1: Revenue vs Procurement -->
    <div class="chart-card">
        <div class="chart-card-header">
            <div>
                <h3><span>📊</span> Monthly Financial Performance</h3>
                <p>Trailing 6-month Revenue vs Restocking Expenditures</p>
            </div>
            <div style="display: flex; gap: 8px;">
                <span class="badge badge-success"><span class="pulse-dot pulse-emerald" style="width:5px;height:5px;"></span> Revenue</span>
                <span class="badge badge-info"><span class="pulse-dot pulse-blue" style="width:5px;height:5px;"></span> Procurement</span>
            </div>
        </div>

        <div class="chart-container-box">
            <canvas id="monthlyTurnoverChart" height="240"></canvas>
            <!-- Accessible SVG fallback if offline -->
            <noscript>
                <div style="padding: 20px; text-align: center; color: #64748b;">
                    Total Revenue: $<?php echo number_format($total_sales, 2); ?> | Total Procurement: $<?php echo number_format($total_purchases, 2); ?>
                </div>
            </noscript>
        </div>
    </div>

    <!-- Chart 2: Stock Health Distribution Donut -->
    <div class="chart-card">
        <div class="chart-card-header">
            <div>
                <h3><span>🎯</span> Stock Health Distribution</h3>
                <p>Catalog balance by inventory safety threshold</p>
            </div>
        </div>

        <div class="chart-container-box" style="display: flex; align-items: center; justify-content: center;">
            <canvas id="stockHealthChart" height="240"></canvas>
        </div>

        <div style="display: flex; justify-content: space-around; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border); font-size: 12px;">
            <div style="text-align: center;">
                <div style="font-weight: 700; color: #059669; font-size: 16px;"><?php echo $healthy_stock_count; ?></div>
                <div style="color: #64748b;">Healthy</div>
            </div>
            <div style="text-align: center;">
                <div style="font-weight: 700; color: #d97706; font-size: 16px;"><?php echo $low_stock_count; ?></div>
                <div style="color: #64748b;">Low Stock</div>
            </div>
            <div style="text-align: center;">
                <div style="font-weight: 700; color: #dc2626; font-size: 16px;"><?php echo $out_of_stock_count; ?></div>
                <div style="color: #64748b;">Out of Stock</div>
            </div>
        </div>
    </div>
</div>

<!-- 3. High Frequency Quick Action Hub -->
<div class="quick-actions-bar">
    <a href="sales.php" class="quick-action-card">
        <div class="quick-action-icon" style="background: #ecfdf5; color: #059669;">💰</div>
        <div>
            <span class="action-title">Record Sale</span>
            <span class="action-desc">Issue customer invoice</span>
        </div>
    </a>
    <a href="inventory.php" class="quick-action-card">
        <div class="quick-action-icon" style="background: #eef2ff; color: #4f46e5;">📑</div>
        <div>
            <span class="action-title">Adjust Inventory</span>
            <span class="action-desc">Restock or write-off units</span>
        </div>
    </a>
    <a href="products.php" class="quick-action-card">
        <div class="quick-action-icon" style="background: #f0f9ff; color: #0284c7;">📦</div>
        <div>
            <span class="action-title">Catalog Item</span>
            <span class="action-desc">Add SKU & set price</span>
        </div>
    </a>
    <a href="customers.php" class="quick-action-card">
        <div class="quick-action-icon" style="background: #fffbeb; color: #d97706;">👥</div>
        <div>
            <span class="action-title">Register Client</span>
            <span class="action-desc">Create customer account</span>
        </div>
    </a>
</div>

<!-- 4. Critical Safety Stock Alerts Section -->
<?php if (!empty($low_stock_products)): ?>
    <div class="form-card" style="border-left: 4px solid #ef4444;">
        <h2>
            <span style="color: #b91c1c; display: flex; align-items: center; gap: 8px;">
                <span class="pulse-dot pulse-rose"></span>
                <span>Critical Safety Stock Alert (<?php echo count($low_stock_products); ?> items)</span>
            </span>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn-export-csv" onclick="exportTableToCSV('dashboardLowStockTable', 'low_stock_alerts')">
                    📥 Export CSV
                </button>
                <a href="inventory.php" class="btn btn-warning btn-sm">Restock in Inventory</a>
            </div>
        </h2>

        <!-- Table Filter Toolbar -->
        <div class="table-filter-bar" data-table="dashboardLowStockTable">
            <div class="table-filter-inputs">
                <div class="filter-input-wrapper">
                    <span class="filter-icon">🔍</span>
                    <input type="text" class="filter-control filter-search" placeholder="Search ID, SKU, product name...">
                </div>
                <button type="button" class="btn-filter-reset">↺ Reset</button>
            </div>
            <div class="table-filter-meta">
                <span class="filter-count-badge">Showing <?php echo count($low_stock_products); ?> of <?php echo count($low_stock_products); ?></span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="app-table" id="dashboardLowStockTable">
                <thead>
                    <tr>
                        <th style="width: 70px;">ID</th>
                        <th>SKU</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Available Stock</th>
                        <th>Safety Threshold</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($low_stock_products as $item): ?>
                        <tr data-id="<?php echo $item['id']; ?>" 
                            data-sku="<?php echo htmlspecialchars($item['sku']); ?>"
                            data-name="<?php echo htmlspecialchars($item['name']); ?>"
                            data-stock="<?php echo $item['quantity']; ?>">
                            <td class="cell-id">#<?php echo $item['id']; ?></td>
                            <td class="cell-sku"><?php echo htmlspecialchars($item['sku']); ?></td>
                            <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                            <td style="color: var(--text-muted);"><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></td>
                            <td>
                                <strong style="color: #dc2626; font-size: 15px;"><?php echo $item['quantity']; ?></strong> units
                            </td>
                            <td style="color: var(--text-muted);"><?php echo $item['min_stock']; ?> units</td>
                            <td>
                                <?php if ($item['quantity'] == 0): ?>
                                    <span class="badge badge-danger">
                                        <span class="pulse-dot pulse-rose" style="width:5px;height:5px;"></span> OUT OF STOCK
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-warning">
                                        <span class="pulse-dot pulse-amber" style="width:5px;height:5px;"></span> LOW INVENTORY
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="inventory.php" class="btn btn-primary btn-sm">Restock</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- 5. Recent Sales Activity Section -->
<div class="form-card" style="max-width: 100%;">
    <h2>
        <span style="display: flex; align-items: center; gap: 8px;">
            <span>💰</span>
            <span>Recent Sales Orders (<?php echo count($recent_sales); ?>)</span>
        </span>
        <div style="display: flex; gap: 8px;">
            <button type="button" class="btn-export-csv" onclick="exportTableToCSV('dashboardSalesTable', 'recent_sales_orders')">
                📥 Export CSV
            </button>
            <a href="sales.php" class="btn btn-primary btn-sm">+ New Sales Order</a>
        </div>
    </h2>

    <!-- Table Filter Toolbar -->
    <div class="table-filter-bar" data-table="dashboardSalesTable">
        <div class="table-filter-inputs">
            <div class="filter-input-wrapper">
                <span class="filter-icon">🔍</span>
                <input type="text" class="filter-control filter-search" placeholder="Search ID, order #, product, customer, cashier...">
            </div>
            <button type="button" class="btn-filter-reset">↺ Reset</button>
        </div>
        <div class="table-filter-meta">
            <span class="filter-count-badge">Showing <?php echo count($recent_sales); ?> of <?php echo count($recent_sales); ?></span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="app-table" id="dashboardSalesTable">
            <thead>
                <tr>
                    <th style="width: 70px;">ID</th>
                    <th>Order #</th>
                    <th>Product</th>
                    <th>Customer</th>
                    <th>Quantity</th>
                    <th>Amount</th>
                    <th>Cashier</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_sales)): ?>
                    <tr class="empty-state-row">
                        <td colspan="8" style="padding: 24px; text-align: center; color: var(--text-muted);">
                            No sales transactions recorded yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_sales as $sale): ?>
                        <tr data-id="<?php echo $sale['id']; ?>" 
                            data-name="<?php echo htmlspecialchars($sale['product_name'] ?? ''); ?>" 
                            data-amount="<?php echo $sale['total_amount']; ?>">
                            <td class="cell-id">#<?php echo $sale['id']; ?></td>
                            <td><strong style="color: #0284c7;">#SL-<?php echo str_pad($sale['id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($sale['product_name'] ?? 'Deleted Product'); ?></td>
                            <td><?php echo htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer'); ?></td>
                            <td><span class="badge badge-info"><?php echo $sale['quantity']; ?> pcs</span></td>
                            <td class="cell-price">$<?php echo number_format($sale['total_amount'], 2); ?></td>
                            <td style="color: var(--text-muted); font-size: 13px;">
                                👤 <?php echo htmlspecialchars($sale['cashier'] ?? 'Staff'); ?>
                            </td>
                            <td style="color: var(--text-muted); font-size: 12.5px;">
                                <?php echo date('M d, Y H:i', strtotime($sale['sale_date'])); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart Initialization Script with Dynamic Theme Adaptation -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    let turnoverChart = null;
    let stockChart = null;

    function isDarkMode() {
        return (document.documentElement.getAttribute('data-theme') || 'dark') === 'dark';
    }

    function getAccentPrimary() {
        const accent = document.documentElement.getAttribute('data-accent') || 'indigo';
        const map = {
            emerald: { bg: 'rgba(52, 211, 153, 0.85)', border: '#10b981' },
            sunset: { bg: 'rgba(251, 113, 133, 0.85)', border: '#f43f5e' },
            nordic: { bg: 'rgba(56, 189, 248, 0.85)', border: '#0ea5e9' },
            indigo: { bg: 'rgba(129, 140, 248, 0.85)', border: '#6366f1' }
        };
        return map[accent] || map.indigo;
    }

    function getGridColor() {
        return isDarkMode() ? 'rgba(255, 255, 255, 0.08)' : 'rgba(226, 232, 240, 0.8)';
    }

    function getTextColor() {
        return isDarkMode() ? '#94a3b8' : '#64748b';
    }

    function getCardBgColor() {
        return isDarkMode() ? '#0f172a' : '#ffffff';
    }

    // 1. Initialize Monthly Turnover Chart
    const turnoverCanvas = document.getElementById('monthlyTurnoverChart');
    if (turnoverCanvas && window.Chart) {
        const labels = <?php echo json_encode(array_values($chart_months)); ?>;
        const salesData = <?php echo json_encode(array_values($chart_sales)); ?>;
        const purchaseData = <?php echo json_encode(array_values($chart_purchases)); ?>;
        const prime = getAccentPrimary();

        turnoverChart = new Chart(turnoverCanvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Sales Revenue ($)',
                        data: salesData,
                        backgroundColor: prime.bg,
                        borderColor: prime.border,
                        borderWidth: 1.5,
                        borderRadius: 6,
                    },
                    {
                        label: 'Procurement Cost ($)',
                        data: purchaseData,
                        backgroundColor: 'rgba(56, 189, 248, 0.85)',
                        borderColor: '#0284c7',
                        borderWidth: 1.5,
                        borderRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': $' + Number(context.raw).toLocaleString(undefined, {minimumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: getTextColor() }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: getTextColor(),
                            callback: function(value) { return '$' + value; }
                        },
                        grid: {
                            color: getGridColor()
                        }
                    }
                }
            }
        });
    }

    // 2. Initialize Stock Health Donut Chart
    const stockCanvas = document.getElementById('stockHealthChart');
    if (stockCanvas && window.Chart) {
        const healthyCount = <?php echo (int)$healthy_stock_count; ?>;
        const lowCount = <?php echo (int)$low_stock_count; ?>;
        const outCount = <?php echo (int)$out_of_stock_count; ?>;

        stockChart = new Chart(stockCanvas, {
            type: 'doughnut',
            data: {
                labels: ['Healthy', 'Low Stock', 'Out of Stock'],
                datasets: [{
                    data: [healthyCount, lowCount, outCount],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 3,
                    borderColor: getCardBgColor(),
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    // Dynamic Live Theme Refresh on Change Event
    window.addEventListener('themeChanged', () => {
        const gridCol = getGridColor();
        const textCol = getTextColor();
        const cardBg = getCardBgColor();
        const prime = getAccentPrimary();

        if (turnoverChart) {
            turnoverChart.options.scales.x.ticks.color = textCol;
            turnoverChart.options.scales.y.ticks.color = textCol;
            turnoverChart.options.scales.y.grid.color = gridCol;
            turnoverChart.data.datasets[0].backgroundColor = prime.bg;
            turnoverChart.data.datasets[0].borderColor = prime.border;
            turnoverChart.update();
        }

        if (stockChart) {
            stockChart.data.datasets[0].borderColor = cardBg;
            stockChart.update();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
