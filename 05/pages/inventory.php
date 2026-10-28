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
    $stmt = $pdo->query("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.name ASC");
    $products = $stmt->fetchAll();
}

// Fetch Inventory Logs with User and Product details
$logs = [];
if ($pdo !== null) {
    $stmt = $pdo->query("SELECT l.*, p.name AS product_name, p.sku, u.username, u.full_name 
                         FROM inventory_logs l 
                         JOIN products p ON l.product_id = p.id 
                         LEFT JOIN users u ON l.user_id = u.id 
                         ORDER BY l.id DESC LIMIT 30");
    $logs = $stmt->fetchAll();
}

$page_title = 'Stock Flow - Inventory Control';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-title-row">
    <div>
        <h1>Inventory Control & Audit</h1>
        <p style="color: #64748b; font-size: 14px; margin-top: 4px;">Execute stock movements, view live stock health, and inspect audit logs.</p>
    </div>
</div>

<!-- Stock Movement Form Card -->
<div class="form-card" style="max-width: 750px;">
    <h2>⚡ Stock Adjustment Entry</h2>
    <form method="POST" action="inventory.php" class="app-form">
        <?php echo csrf_field(); ?>

        <div class="form-row form-row-2">
            <div class="form-group">
                <label class="form-label">Target Product *</label>
                <select name="product_id" class="form-control" required>
                    <option value="">-- Choose Product --</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?php echo $p['id']; ?>">
                            <?php echo htmlspecialchars($p['name']); ?> [SKU: <?php echo htmlspecialchars($p['sku']); ?> | Current: <?php echo $p['quantity']; ?>]
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
    <h2>Current Stock Status & Health</h2>
    <div class="table-responsive">
        <table class="app-table">
            <thead>
                <tr>
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
                    <tr>
                        <td colspan="7" style="padding: 20px; text-align: center; color: #64748b;">No products found in system.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $prod): ?>
                        <tr>
                            <td style="font-weight: 600; color: #0284c7;"><?php echo htmlspecialchars($prod['sku']); ?></td>
                            <td><strong><?php echo htmlspecialchars($prod['name']); ?></strong></td>
                            <td style="color: #64748b;"><?php echo htmlspecialchars($prod['category_name'] ?? 'Uncategorized'); ?></td>
                            <td>$<?php echo number_format($prod['price'], 2); ?></td>
                            <td>
                                <strong style="font-size: 15px; <?php echo ($prod['quantity'] <= $prod['min_stock']) ? 'color: #dc2626;' : ''; ?>">
                                    <?php echo $prod['quantity']; ?>
                                </strong>
                            </td>
                            <td style="color: #64748b;"><?php echo $prod['min_stock']; ?> units</td>
                            <td>
                                <?php if ($prod['quantity'] == 0): ?>
                                    <span class="badge badge-danger">OUT OF STOCK</span>
                                <?php elseif ($prod['quantity'] <= $prod['min_stock']): ?>
                                    <span class="badge badge-warning">LOW STOCK</span>
                                <?php else: ?>
                                    <span class="badge badge-success">HEALTHY</span>
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
    <h2>Recent Stock Audit Logs</h2>
    <div class="table-responsive">
        <table class="app-table">
            <thead>
                <tr>
                    <th>Log #</th>
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
                    <tr>
                        <td colspan="7" style="padding: 20px; text-align: center; color: #64748b;">No inventory logs recorded.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>#<?php echo $log['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($log['product_name']); ?></strong>
                                <span style="font-size: 11px; color: #64748b; display: block;"><?php echo htmlspecialchars($log['sku']); ?></span>
                            </td>
                            <td>
                                <?php if ($log['type'] === 'in'): ?>
                                    <span class="badge badge-success">STOCK IN (+)</span>
                                <?php elseif ($log['type'] === 'out'): ?>
                                    <span class="badge badge-danger">STOCK OUT (-)</span>
                                <?php else: ?>
                                    <span class="badge badge-info">RESET</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo $log['quantity']; ?></strong></td>
                            <td style="color: #475569;"><?php echo htmlspecialchars($log['reason'] ?? '-'); ?></td>
                            <td style="color: #0284c7; font-size: 13px;">
                                <?php echo htmlspecialchars($log['full_name'] ?? $log['username'] ?? 'System'); ?>
                            </td>
                            <td style="color: #64748b; font-size: 13px;">
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
