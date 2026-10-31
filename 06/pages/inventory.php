<?php
require_once __DIR__ . '/../config/db.php';
check_login();

// Handle Manual Stock Adjustment
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $pdo !== null) {
    if (!verify_csrf()) {
        set_flash_message('error', 'Security token mismatch. Please try again.');
        header("Location: inventory.php");
        exit;
    }

    $product_id = intval($_POST['product_id'] ?? 0);
    $type       = $_POST['type'] ?? 'in'; // 'in', 'out', 'adjustment'
    $qty        = intval($_POST['quantity'] ?? 0);
    $reason     = trim($_POST['reason'] ?? '');

    if (!in_array($type, ['in', 'out', 'adjustment'], true)) {
        $type = 'in';
    }

    if ($product_id <= 0 || $qty <= 0) {
        set_flash_message('error', 'Please select a valid product and enter a quantity greater than zero.');
    } else {
        try {
            $pdo->beginTransaction();

            // Fetch current quantity with row lock
            $stmt = $pdo->prepare("SELECT name, sku, quantity FROM products WHERE id = ? FOR UPDATE");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();

            if (!$product) {
                throw new Exception("The selected product could not be found.");
            }

            $current_qty = (int)$product['quantity'];

            if ($type === 'in') {
                $new_qty = $current_qty + $qty;
                $log_reason = !empty($reason) ? $reason : "Manual Stock-In (+{$qty})";
            } elseif ($type === 'out') {
                if ($current_qty < $qty) {
                    throw new Exception("Insufficient stock for '{$product['name']}'. Current available quantity is {$current_qty}, cannot deduct {$qty}.");
                }
                $new_qty = $current_qty - $qty;
                $log_reason = !empty($reason) ? $reason : "Manual Stock-Out (-{$qty})";
            } else { // Direct adjustment / physical count reset
                $new_qty = $qty;
                $diff = $new_qty - $current_qty;
                $log_reason = !empty($reason) ? $reason : "Inventory Count Reset from {$current_qty} to {$new_qty} (" . ($diff >= 0 ? "+$diff" : "$diff") . ")";
            }

            // Update product stock
            $stmt = $pdo->prepare("UPDATE products SET quantity = ? WHERE id = ?");
            $stmt->execute([$new_qty, $product_id]);

            // Insert inventory audit log with user tracking
            $user_id = $_SESSION['user_id'] ?? null;
            $stmt = $pdo->prepare("INSERT INTO inventory_logs (user_id, product_id, type, quantity, reason) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $type, $qty, $log_reason]);

            $pdo->commit();

            log_activity($pdo, 'Inventory Adjustment', 'Inventory', "Updated '{$product['name']}' stock ({$type}, qty: {$qty})");
            set_flash_message('success', "Stock updated for '{$product['name']}'. New inventory balance: {$new_qty} units.");
            header("Location: inventory.php");
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash_message('error', $e->getMessage());
            header("Location: inventory.php");
            exit;
        }
    }
}

// Fetch Products for dropdown and status table
$products = [];
if ($pdo !== null) {
    $stmt = $pdo->query("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id ASC");
    $products = $stmt->fetchAll();
}

// Fetch Inventory Logs with User and Product details
$logs = [];
if ($pdo !== null) {
    $stmt = $pdo->query("SELECT l.*, p.name AS product_name, p.sku, u.username, u.full_name 
                         FROM inventory_logs l 
                         LEFT JOIN products p ON l.product_id = p.id 
                         LEFT JOIN users u ON l.user_id = u.id 
                         ORDER BY l.id DESC");
    $logs = $stmt->fetchAll();
}

$page_title = 'Stock Flow - Inventory Control';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-title-row">
    <div>
        <h1>Inventory Control & Audit</h1>
        <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">Execute stock movements, view live stock health, and inspect audit logs.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <button type="button" id="toggleInvFormBtn" class="btn btn-primary btn-sm" onclick="toggleFormCard('inventoryFormCard', 'toggleInvFormBtn')">
            <span>⚡ Stock Movement Entry</span>
        </button>
    </div>
</div>

<!-- Stock Movement Form Card -->
<div class="form-card collapsible-form-container collapsed" id="inventoryFormCard" style="max-width: 750px; display: none;">
    <h2>
        <span>⚡ Stock Movement Entry</span>
    </h2>
    <form method="POST" action="inventory.php" class="app-form">
        <?php echo csrf_field(); ?>

        <div class="form-row form-row-2">
            <div class="form-group">
                <label class="form-label">Target Product *</label>
                <select name="product_id" class="form-control" required>
                    <option value="">-- Choose Product --</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?php echo $p['id']; ?>">
                            #<?php echo $p['id']; ?> - <?php echo htmlspecialchars($p['name']); ?> [SKU: <?php echo htmlspecialchars($p['sku']); ?> | Current: <?php echo $p['quantity']; ?>]
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Movement Type *</label>
                <select name="type" class="form-control" required>
                    <option value="in">➕ Stock In (Arrival / Restock)</option>
                    <option value="out">➖ Stock Out (Damage / Disposal / Loss)</option>
                    <option value="adjustment">⚙️ Direct Reset (Physical Count Reconciliation)</option>
                </select>
            </div>
        </div>

        <div class="form-row form-row-2">
            <div class="form-group">
                <label class="form-label">Quantity *</label>
                <input type="number" name="quantity" class="form-control" placeholder="Enter units count..." min="1" required>
            </div>

            <div class="form-group">
                <label class="form-label">Audit Reason / Notes</label>
                <input type="text" name="reason" class="form-control" placeholder="e.g. Periodic stock audit, Damaged during transit">
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="align-self: flex-start;">
            Submit Stock Movement
        </button>
    </form>
</div>

<!-- Current Stock Status Overview -->
<div class="form-card" style="max-width: 100%;">
    <h2>
        <span>📑 Current Stock Status & Health</span>
        <button type="button" class="btn-export-csv" onclick="exportTableToCSV('stockStatusTable', 'stock_status_inventory')">
            📥 Export CSV
        </button>
    </h2>

    <!-- Table Filter Toolbar for Stock Status -->
    <div class="table-filter-bar" data-table="stockStatusTable">
        <div class="table-filter-inputs">
            <div class="filter-input-wrapper">
                <span class="filter-icon">🔍</span>
                <input type="text" class="filter-control filter-search" placeholder="Search ID, SKU, product name...">
            </div>

            <select class="filter-control filter-select" data-filter="status">
                <option value="">All Health Statuses</option>
                <option value="healthy">Healthy (In Stock)</option>
                <option value="low_stock">Low Stock (Warning)</option>
                <option value="out_of_stock">Out of Stock (Critical)</option>
            </select>

            <select class="filter-control filter-sort">
                <option value="id-asc">Sort: ID (1 → N)</option>
                <option value="id-desc">Sort: ID (N → 1)</option>
                <option value="name-asc">Sort: Product Name (A-Z)</option>
                <option value="stock-asc">Sort: Stock Level (Low → High)</option>
                <option value="stock-desc">Sort: Stock Level (High → Low)</option>
                <option value="price-desc">Sort: Price (High → Low)</option>
            </select>

            <button type="button" class="btn-filter-reset">↺ Reset</button>
        </div>
        <div class="table-filter-meta">
            <span class="filter-count-badge">Showing <?php echo count($products); ?> of <?php echo count($products); ?></span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="app-table" id="stockStatusTable">
            <thead>
                <tr>
                    <th style="width: 70px;">ID</th>
                    <th>SKU</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock Level</th>
                    <th>Threshold</th>
                    <th>Health Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr class="empty-state-row">
                        <td colspan="8" style="padding: 24px; text-align: center; color: var(--text-muted);">No products found in system.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $prod): 
                        $status_key = ($prod['quantity'] == 0) ? 'out_of_stock' : (($prod['quantity'] <= $prod['min_stock']) ? 'low_stock' : 'healthy');
                    ?>
                        <tr data-id="<?php echo $prod['id']; ?>" 
                            data-sku="<?php echo htmlspecialchars($prod['sku']); ?>"
                            data-name="<?php echo htmlspecialchars($prod['name']); ?>" 
                            data-status="<?php echo $status_key; ?>" 
                            data-stock="<?php echo $prod['quantity']; ?>" 
                            data-price="<?php echo $prod['price']; ?>">
                            <td class="cell-id">#<?php echo $prod['id']; ?></td>
                            <td class="cell-sku"><?php echo htmlspecialchars($prod['sku']); ?></td>
                            <td><strong><?php echo htmlspecialchars($prod['name']); ?></strong></td>
                            <td style="color: var(--text-muted);"><?php echo htmlspecialchars($prod['category_name'] ?? 'Uncategorized'); ?></td>
                            <td class="cell-price">$<?php echo number_format($prod['price'], 2); ?></td>
                            <td>
                                <strong style="font-size: 15px; <?php echo ($prod['quantity'] <= $prod['min_stock']) ? 'color: #dc2626;' : ''; ?>">
                                    <?php echo $prod['quantity']; ?>
                                </strong>
                            </td>
                            <td style="color: var(--text-muted);"><?php echo $prod['min_stock']; ?> units</td>
                            <td>
                                <?php if ($prod['quantity'] == 0): ?>
                                    <span class="badge badge-danger">
                                        <span class="pulse-dot pulse-rose" style="width:5px;height:5px;"></span> OUT OF STOCK
                                    </span>
                                <?php elseif ($prod['quantity'] <= $prod['min_stock']): ?>
                                    <span class="badge badge-warning">
                                        <span class="pulse-dot pulse-amber" style="width:5px;height:5px;"></span> LOW STOCK
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-success">
                                        <span class="pulse-dot pulse-emerald" style="width:5px;height:5px;"></span> HEALTHY
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

<!-- Historical Inventory Logs -->
<div class="form-card" style="max-width: 100%;">
    <h2>
        <span>📋 Recent Stock Audit Logs (<?php echo count($logs); ?>)</span>
        <button type="button" class="btn-export-csv" onclick="exportTableToCSV('inventoryLogsTable', 'inventory_audit_logs')">
            📥 Export CSV
        </button>
    </h2>

    <!-- Table Filter Toolbar for Inventory Logs -->
    <div class="table-filter-bar" data-table="inventoryLogsTable">
        <div class="table-filter-inputs">
            <div class="filter-input-wrapper">
                <span class="filter-icon">🔍</span>
                <input type="text" class="filter-control filter-search" placeholder="Search ID, product, SKU, reason, user...">
            </div>

            <select class="filter-control filter-select" data-filter="type">
                <option value="">All Movement Types</option>
                <option value="in">Stock In (+)</option>
                <option value="out">Stock Out (-)</option>
                <option value="adjustment">Reset / Adjustment</option>
            </select>

            <select class="filter-control filter-sort">
                <option value="id-desc">Sort: ID (Newest First)</option>
                <option value="id-asc">Sort: ID (Oldest First)</option>
                <option value="stock-desc">Sort: Quantity (High → Low)</option>
                <option value="stock-asc">Sort: Quantity (Low → High)</option>
            </select>

            <button type="button" class="btn-filter-reset">↺ Reset</button>
        </div>
        <div class="table-filter-meta">
            <span class="filter-count-badge">Showing <?php echo count($logs); ?> of <?php echo count($logs); ?></span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="app-table" id="inventoryLogsTable">
            <thead>
                <tr>
                    <th style="width: 70px;">ID</th>
                    <th>Product</th>
                    <th>Movement</th>
                    <th>Quantity</th>
                    <th>Reason / Notes</th>
                    <th>Logged By</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr class="empty-state-row">
                        <td colspan="7" style="padding: 24px; text-align: center; color: var(--text-muted);">No inventory logs recorded.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr data-id="<?php echo $log['id']; ?>" 
                            data-name="<?php echo htmlspecialchars($log['product_name'] ?? ''); ?>" 
                            data-sku="<?php echo htmlspecialchars($log['sku'] ?? ''); ?>"
                            data-type="<?php echo htmlspecialchars(strtolower($log['type'])); ?>" 
                            data-stock="<?php echo $log['quantity']; ?>">
                            <td class="cell-id">#<?php echo $log['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($log['product_name'] ?? 'Product #' . $log['product_id']); ?></strong>
                                <span class="cell-sku" style="font-size: 11px; display: block;"><?php echo htmlspecialchars($log['sku'] ?? 'N/A'); ?></span>
                            </td>
                            <td>
                                <?php if ($log['type'] === 'in'): ?>
                                    <span class="badge badge-success">
                                        <span class="pulse-dot pulse-emerald" style="width:4px;height:4px;"></span> IN (+)
                                    </span>
                                <?php elseif ($log['type'] === 'out'): ?>
                                    <span class="badge badge-danger">
                                        <span class="pulse-dot pulse-rose" style="width:4px;height:4px;"></span> OUT (-)
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-info">
                                        <span class="pulse-dot pulse-blue" style="width:4px;height:4px;"></span> RESET
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><strong style="font-size: 14.5px;"><?php echo $log['quantity']; ?></strong> pcs</td>
                            <td style="color: var(--text-secondary);"><?php echo htmlspecialchars($log['reason'] ?? '-'); ?></td>
                            <td style="color: #4f46e5; font-size: 13px; font-weight: 600;">
                                👤 <?php echo htmlspecialchars($log['full_name'] ?? $log['username'] ?? 'System'); ?>
                            </td>
                            <td style="color: var(--text-muted); font-size: 12.5px;">
                                <?php 
                                    $time_str = !empty($log['created_at']) ? $log['created_at'] : 'now';
                                    echo date('M d, Y H:i', strtotime($time_str)); 
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
