<?php
require_once __DIR__ . '/../config/db.php';
check_login();

// Handle Record New Sale
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $pdo !== null) {
    if (!verify_csrf()) {
        set_flash_message('error', 'Security token mismatch. Please try again.');
        header("Location: sales.php");
        exit;
    }

    $product_id  = intval($_POST['product_id'] ?? 0);
    $customer_id = !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
    $quantity    = intval($_POST['quantity'] ?? 0);
    $unit_price  = floatval($_POST['unit_price'] ?? 0);

    if ($product_id <= 0 || $quantity <= 0 || $unit_price <= 0) {
        set_flash_message('error', 'Please choose a product and enter a valid quantity (> 0) and selling price (> 0).');
    } else {
        try {
            $pdo->beginTransaction();

            // Row-level locking to prevent race conditions and negative inventory
            $stmt = $pdo->prepare("SELECT name, sku, quantity FROM products WHERE id = ? FOR UPDATE");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();

            if (!$product) {
                throw new Exception("The selected product does not exist.");
            }

            if ($product['quantity'] < $quantity) {
                throw new Exception("Insufficient stock for '{$product['name']}'. Available balance is only {$product['quantity']} units.");
            }

            $total_amount = round($quantity * $unit_price, 2);
            $user_id = $_SESSION['user_id'] ?? null;

            // 1. Insert Sales Record
            $stmt = $pdo->prepare("INSERT INTO sales (user_id, product_id, customer_id, quantity, unit_price, total_amount) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $customer_id, $quantity, $unit_price, $total_amount]);
            $sale_id = $pdo->lastInsertId();

            // 2. Deduct product quantity from database
            $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
            $stmt->execute([$quantity, $product_id]);

            // 3. Log stock out in inventory_logs
            $stmt = $pdo->prepare("INSERT INTO inventory_logs (user_id, product_id, type, quantity, reason) VALUES (?, ?, 'out', ?, ?)");
            $stmt->execute([$user_id, $product_id, $quantity, "Sale Order #SL-" . str_pad($sale_id, 4, '0', STR_PAD_LEFT)]);

            $pdo->commit();

            log_activity($pdo, 'Sale Completed', 'Sales', "Processed Sale #SL-{$sale_id} for '{$product['name']}' (\${$total_amount})");
            set_flash_message('success', "Sale #SL-" . str_pad($sale_id, 4, '0', STR_PAD_LEFT) . " completed successfully! Total billed: $" . number_format($total_amount, 2));
            header("Location: sales.php");
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash_message('error', $e->getMessage());
            header("Location: sales.php");
            exit;
        }
    }
}

// Fetch products in stock & customers for form
$products = [];
$customers = [];
$sales_list = [];

if ($pdo !== null) {
    $stmt = $pdo->query("SELECT * FROM products WHERE quantity > 0 ORDER BY id ASC");
    $products = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT * FROM customers ORDER BY id ASC");
    $customers = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT s.*, p.name AS product_name, p.sku, c.name AS customer_name, u.username, u.full_name AS cashier_name 
                         FROM sales s 
                         LEFT JOIN products p ON s.product_id = p.id 
                         LEFT JOIN customers c ON s.customer_id = c.id 
                         LEFT JOIN users u ON s.user_id = u.id
                         ORDER BY s.id DESC");
    $sales_list = $stmt->fetchAll();
}

$page_title = 'Stock Flow - Sales Transactions';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-title-row">
    <div>
        <h1>Sales Transactions</h1>
        <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">Record customer orders with real-time stock deduction and inventory synchronization.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <button type="button" id="toggleSalesFormBtn" class="btn btn-primary btn-sm" onclick="toggleFormCard('salesFormCard', 'toggleSalesFormBtn')">
            <span>➕ Record New Sale</span>
        </button>
    </div>
</div>

<!-- New Sale Order Entry Card -->
<div class="form-card collapsible-form-container collapsed" id="salesFormCard" style="max-width: 750px; display: none;">
    <h2>
        <span>➕ Record New Sales Order</span>
    </h2>
    <form method="POST" action="sales.php" class="app-form">
        <?php echo csrf_field(); ?>

        <div class="form-row form-row-2">
            <div class="form-group">
                <label class="form-label">Product to Sell *</label>
                <select name="product_id" id="sales_product_select" class="form-control" required>
                    <option value="" data-price="0">-- Select In-Stock Product --</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['price']; ?>">
                            #<?php echo $p['id']; ?> - <?php echo htmlspecialchars($p['name']); ?> ($<?php echo number_format($p['price'], 2); ?> | Avail: <?php echo $p['quantity']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Customer</label>
                <select name="customer_id" class="form-control">
                    <option value="">-- Walk-in / Guest Customer --</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?php echo $c['id']; ?>">#<?php echo $c['id']; ?> - <?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row form-row-2">
            <div class="form-group">
                <label class="form-label">Quantity *</label>
                <input type="number" name="quantity" id="sales_quantity_input" class="form-control" placeholder="1" min="1" value="1" required>
            </div>

            <div class="form-group">
                <label class="form-label">Unit Price ($) *</label>
                <input type="number" step="0.01" min="0.01" name="unit_price" id="unit_price_input" class="form-control" placeholder="0.00" required>
            </div>
        </div>

        <!-- Live Total Price Preview -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-weight: 600; color: #475569;">Calculated Order Total:</span>
            <span id="sales_total_display" style="font-size: 22px; font-weight: 700; color: #059669;">$0.00</span>
        </div>

        <button type="submit" class="btn btn-primary" style="align-self: flex-start;">
            ✓ Complete & Deduct Stock
        </button>
    </form>
</div>

<!-- Sales History Ledger -->
<div class="form-card" style="max-width: 100%;">
    <h2>
        <span>💰 Sales Order History (<?php echo count($sales_list); ?>)</span>
        <button type="button" class="btn-export-csv" onclick="exportTableToCSV('salesTable', 'sales_ledger')">
            📥 Export CSV
        </button>
    </h2>

    <!-- Table Filter Toolbar -->
    <div class="table-filter-bar" data-table="salesTable">
        <div class="table-filter-inputs">
            <div class="filter-input-wrapper">
                <span class="filter-icon">🔍</span>
                <input type="text" class="filter-control filter-search" placeholder="Search ID, invoice #, product, customer, cashier...">
            </div>

            <select class="filter-control filter-select" data-filter="customer">
                <option value="">All Customers</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?php echo htmlspecialchars(strtolower($c['name'])); ?>">
                        <?php echo htmlspecialchars($c['name']); ?>
                    </option>
                <?php endforeach; ?>
                <option value="walk-in customer">Walk-in Customer</option>
            </select>

            <select class="filter-control filter-sort">
                <option value="id-desc">Sort: ID (Newest First)</option>
                <option value="id-asc">Sort: ID (Oldest First)</option>
                <option value="amount-desc">Sort: Total Billed (High → Low)</option>
                <option value="amount-asc">Sort: Total Billed (Low → High)</option>
                <option value="stock-desc">Sort: Quantity (High → Low)</option>
            </select>

            <button type="button" class="btn-filter-reset">↺ Reset</button>
        </div>
        <div class="table-filter-meta">
            <span class="filter-count-badge">Showing <?php echo count($sales_list); ?> of <?php echo count($sales_list); ?></span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="app-table" id="salesTable">
            <thead>
                <tr>
                    <th style="width: 70px;">ID</th>
                    <th>Invoice #</th>
                    <th>Product Name</th>
                    <th>Customer</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total Billed</th>
                    <th>Cashier</th>
                    <th>Sale Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sales_list)): ?>
                    <tr class="empty-state-row">
                        <td colspan="9" style="padding: 24px; text-align: center; color: var(--text-muted);">No sales transactions recorded yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sales_list as $s): ?>
                        <tr data-id="<?php echo $s['id']; ?>" 
                            data-name="<?php echo htmlspecialchars($s['product_name'] ?? ''); ?>" 
                            data-customer="<?php echo htmlspecialchars(strtolower($s['customer_name'] ?? 'walk-in customer')); ?>" 
                            data-amount="<?php echo $s['total_amount']; ?>" 
                            data-stock="<?php echo $s['quantity']; ?>">
                            <td class="cell-id">#<?php echo $s['id']; ?></td>
                            <td>
                                <strong style="color: #0284c7; font-family: var(--font-mono);">#SL-<?php echo str_pad($s['id'], 4, '0', STR_PAD_LEFT); ?></strong>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($s['product_name'] ?? 'Deleted Product'); ?></strong>
                                <span class="cell-sku" style="font-size: 11px; display: block;"><?php echo htmlspecialchars($s['sku'] ?? 'N/A'); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($s['customer_name'] ?? 'Walk-in Customer'); ?></td>
                            <td><span class="badge badge-info"><?php echo $s['quantity']; ?> pcs</span></td>
                            <td style="color: var(--text-secondary);">$<?php echo number_format($s['unit_price'], 2); ?></td>
                            <td class="cell-price">$<?php echo number_format($s['total_amount'], 2); ?></td>
                            <td style="color: var(--text-muted); font-size: 13px;">
                                👤 <?php echo htmlspecialchars($s['cashier_name'] ?? $s['username'] ?? 'Staff'); ?>
                            </td>
                            <td style="color: var(--text-muted); font-size: 12.5px;"><?php echo date('M d, Y H:i', strtotime($s['sale_date'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
