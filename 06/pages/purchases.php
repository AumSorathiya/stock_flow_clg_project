<?php
require_once __DIR__ . '/../config/db.php';
check_login();

// Handle Record New Purchase
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $pdo !== null) {
    if (!verify_csrf()) {
        set_flash_message('error', 'Security token mismatch. Please try again.');
        header("Location: purchases.php");
        exit;
    }

    $product_id  = intval($_POST['product_id'] ?? 0);
    $supplier_id = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null;
    $quantity    = intval($_POST['quantity'] ?? 0);
    $unit_cost   = floatval($_POST['unit_cost'] ?? 0);

    if ($product_id <= 0 || $quantity <= 0 || $unit_cost < 0) {
        set_flash_message('error', 'Please select a product and enter a valid quantity (> 0) and cost (>= 0).');
    } else {
        try {
            $pdo->beginTransaction();

            // Fetch product
            $stmt = $pdo->prepare("SELECT name, sku, quantity FROM products WHERE id = ? FOR UPDATE");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();

            if (!$product) {
                throw new Exception("The selected product could not be found.");
            }

            $total_amount = round($quantity * $unit_cost, 2);
            $user_id = $_SESSION['user_id'] ?? null;

            // 1. Insert Purchase Record
            $stmt = $pdo->prepare("INSERT INTO purchases (user_id, product_id, supplier_id, quantity, unit_cost, total_amount) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $supplier_id, $quantity, $unit_cost, $total_amount]);
            $purchase_id = $pdo->lastInsertId();

            // 2. Increase product stock quantity and update latest cost
            $stmt = $pdo->prepare("UPDATE products SET quantity = quantity + ?, cost = ? WHERE id = ?");
            $stmt->execute([$quantity, $unit_cost, $product_id]);

            // 3. Log stock in in inventory_logs
            $stmt = $pdo->prepare("INSERT INTO inventory_logs (user_id, product_id, type, quantity, reason) VALUES (?, ?, 'in', ?, ?)");
            $stmt->execute([$user_id, $product_id, $quantity, "Purchase Order #PO-" . str_pad($purchase_id, 4, '0', STR_PAD_LEFT)]);

            $pdo->commit();

            log_activity($pdo, 'Purchase Recorded', 'Purchases', "Recorded Purchase #PO-{$purchase_id} for '{$product['name']}' (\${$total_amount})");
            set_flash_message('success', "Purchase #PO-" . str_pad($purchase_id, 4, '0', STR_PAD_LEFT) . " logged! Added {$quantity} units to '{$product['name']}'. Total: $" . number_format($total_amount, 2));
            header("Location: purchases.php");
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash_message('error', $e->getMessage());
            header("Location: purchases.php");
            exit;
        }
    }
}

// Fetch products & suppliers
$products = [];
$suppliers = [];
$purchases_list = [];

if ($pdo !== null) {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id ASC");
    $products = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT * FROM suppliers ORDER BY id ASC");
    $suppliers = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT pu.*, p.name AS product_name, p.sku, s.name AS supplier_name, s.company, u.username, u.full_name AS buyer_name 
                         FROM purchases pu 
                         LEFT JOIN products p ON pu.product_id = p.id 
                         LEFT JOIN suppliers s ON pu.supplier_id = s.id 
                         LEFT JOIN users u ON pu.user_id = u.id
                         ORDER BY pu.id DESC");
    $purchases_list = $stmt->fetchAll();
}

$page_title = 'Stock Flow - Purchase Orders';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-title-row">
    <div>
        <h1>Purchase Orders & Restock</h1>
        <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">Log procurement batches with automated inventory restock and cost price updates.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <button type="button" id="togglePurchaseFormBtn" class="btn btn-primary btn-sm" onclick="toggleFormCard('purchaseFormCard', 'togglePurchaseFormBtn')">
            <span>➕ New Purchase Order</span>
        </button>
    </div>
</div>

<!-- Purchase Order Entry Card -->
<div class="form-card collapsible-form-container collapsed" id="purchaseFormCard" style="max-width: 750px; display: none;">
    <h2>
        <span>➕ Record Inbound Purchase Order</span>
    </h2>
    <form method="POST" action="purchases.php" class="app-form">
        <?php echo csrf_field(); ?>

        <div class="form-row form-row-2">
            <div class="form-group">
                <label class="form-label">Product to Restock *</label>
                <select name="product_id" id="purchase_product_select" class="form-control" required>
                    <option value="" data-cost="0">-- Select Product --</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?php echo $p['id']; ?>" data-cost="<?php echo $p['cost']; ?>">
                            #<?php echo $p['id']; ?> - <?php echo htmlspecialchars($p['name']); ?> (Current Stock: <?php echo $p['quantity']; ?> | Cost: $<?php echo number_format($p['cost'], 2); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Vendor / Supplier</label>
                <select name="supplier_id" class="form-control">
                    <option value="">-- General / Walk-in Supplier --</option>
                    <?php foreach ($suppliers as $sup): ?>
                        <option value="<?php echo $sup['id']; ?>">
                            #<?php echo $sup['id']; ?> - <?php echo htmlspecialchars($sup['name']); ?><?php echo !empty($sup['company']) ? ' (' . htmlspecialchars($sup['company']) . ')' : ''; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row form-row-2">
            <div class="form-group">
                <label class="form-label">Units Quantity Purchased *</label>
                <input type="number" name="quantity" id="purchase_quantity_input" class="form-control" placeholder="1" min="1" value="1" required>
            </div>

            <div class="form-group">
                <label class="form-label">Unit Cost ($) *</label>
                <input type="number" step="0.01" min="0" name="unit_cost" id="unit_cost_input" class="form-control" placeholder="0.00" required>
            </div>
        </div>

        <!-- Live Total Cost Preview -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-weight: 600; color: #475569;">Total Procurement Expenditure:</span>
            <span id="purchase_total_display" style="font-size: 22px; font-weight: 700; color: #0284c7;">$0.00</span>
        </div>

        <button type="submit" class="btn btn-primary" style="align-self: flex-start;">
            ✓ Confirm Order & Replenish Stock
        </button>
    </form>
</div>

<!-- Purchase History Ledger -->
<div class="form-card" style="max-width: 100%;">
    <h2>
        <span>🛍️ Purchase History (<?php echo count($purchases_list); ?>)</span>
        <button type="button" class="btn-export-csv" onclick="exportTableToCSV('purchasesTable', 'purchases_ledger')">
            📥 Export CSV
        </button>
    </h2>

    <!-- Table Filter Toolbar -->
    <div class="table-filter-bar" data-table="purchasesTable">
        <div class="table-filter-inputs">
            <div class="filter-input-wrapper">
                <span class="filter-icon">🔍</span>
                <input type="text" class="filter-control filter-search" placeholder="Search ID, PO #, product, supplier, recorded by...">
            </div>

            <select class="filter-control filter-select" data-filter="supplier">
                <option value="">All Suppliers</option>
                <?php foreach ($suppliers as $sup): ?>
                    <option value="<?php echo htmlspecialchars(strtolower($sup['name'])); ?>">
                        <?php echo htmlspecialchars($sup['name']); ?>
                    </option>
                <?php endforeach; ?>
                <option value="general supplier">General Supplier</option>
            </select>

            <select class="filter-control filter-sort">
                <option value="id-desc">Sort: ID (Newest First)</option>
                <option value="id-asc">Sort: ID (Oldest First)</option>
                <option value="amount-desc">Sort: Total Expense (High → Low)</option>
                <option value="amount-asc">Sort: Total Expense (Low → High)</option>
                <option value="stock-desc">Sort: Quantity (High → Low)</option>
            </select>

            <button type="button" class="btn-filter-reset">↺ Reset</button>
        </div>
        <div class="table-filter-meta">
            <span class="filter-count-badge">Showing <?php echo count($purchases_list); ?> of <?php echo count($purchases_list); ?></span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="app-table" id="purchasesTable">
            <thead>
                <tr>
                    <th style="width: 70px;">ID</th>
                    <th>PO #</th>
                    <th>Product</th>
                    <th>Supplier / Vendor</th>
                    <th>Quantity</th>
                    <th>Unit Cost</th>
                    <th>Total Expense</th>
                    <th>Recorded By</th>
                    <th>Purchase Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($purchases_list)): ?>
                    <tr class="empty-state-row">
                        <td colspan="9" style="padding: 24px; text-align: center; color: var(--text-muted);">No purchase orders recorded yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($purchases_list as $pu): ?>
                        <tr data-id="<?php echo $pu['id']; ?>" 
                            data-name="<?php echo htmlspecialchars($pu['product_name'] ?? ''); ?>" 
                            data-supplier="<?php echo htmlspecialchars(strtolower($pu['supplier_name'] ?? 'general supplier')); ?>" 
                            data-amount="<?php echo $pu['total_amount']; ?>" 
                            data-stock="<?php echo $pu['quantity']; ?>">
                            <td class="cell-id">#<?php echo $pu['id']; ?></td>
                            <td>
                                <strong style="color: #0284c7; font-family: var(--font-mono);">#PO-<?php echo str_pad($pu['id'], 4, '0', STR_PAD_LEFT); ?></strong>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($pu['product_name'] ?? 'Deleted Product'); ?></strong>
                                <span class="cell-sku" style="font-size: 11px; display: block;"><?php echo htmlspecialchars($pu['sku'] ?? 'N/A'); ?></span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($pu['supplier_name'] ?? 'General Supplier'); ?></strong>
                                <?php if (!empty($pu['company'])): ?>
                                    <span style="font-size: 11px; color: var(--text-muted); display: block;"><?php echo htmlspecialchars($pu['company']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-info"><?php echo $pu['quantity']; ?> pcs</span></td>
                            <td style="color: var(--text-secondary);">$<?php echo number_format($pu['unit_cost'], 2); ?></td>
                            <td class="cell-price" style="color: #0284c7;">$<?php echo number_format($pu['total_amount'], 2); ?></td>
                            <td style="color: var(--text-muted); font-size: 13px;">
                                👤 <?php echo htmlspecialchars($pu['buyer_name'] ?? $pu['username'] ?? 'Staff'); ?>
                            </td>
                            <td style="color: var(--text-muted); font-size: 12.5px;"><?php echo date('M d, Y H:i', strtotime($pu['purchase_date'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
