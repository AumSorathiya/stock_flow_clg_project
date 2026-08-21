<?php
$page_title = 'Stock Flow - Inventory Management';
require_once __DIR__ . '/../includes/header.php';

$msg = "";

// Handle Manual Stock Adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo !== null) {
    $product_id = intval($_POST['product_id'] ?? 0);
    $type = sanitize($_POST['type'] ?? 'in'); // 'in', 'out', 'adjustment'
    $qty = intval($_POST['quantity'] ?? 0);
    $reason = sanitize($_POST['reason'] ?? '');

    if ($product_id <= 0 || $qty <= 0) {
        $msg = '<div style="color: #ef4444; margin-bottom: 15px;">Please select a valid product and quantity (> 0).</div>';
    } else {
        try {
            $pdo->beginTransaction();

            // Fetch current quantity
            $stmt = $pdo->prepare("SELECT quantity FROM products WHERE id=?");
            $stmt->execute([$product_id]);
            $current_qty = $stmt->fetchColumn();

            if ($current_qty === false) {
                throw new Exception("Product not found.");
            }

            if ($type === 'in') {
                $new_qty = $current_qty + $qty;
            } else if ($type === 'out') {
                if ($current_qty < $qty) {
                    throw new Exception("Cannot deduct $qty items. Current stock is only $current_qty.");
                }
                $new_qty = $current_qty - $qty;
            } else { // 'adjustment' (set exact stock level)
                $new_qty = $qty;
            }

            // Update product stock
            $stmt = $pdo->prepare("UPDATE products SET quantity=? WHERE id=?");
            $stmt->execute([$new_qty, $product_id]);

            // Log entry
            $stmt = $pdo->prepare("INSERT INTO inventory_logs (product_id, type, quantity, reason) VALUES (?, ?, ?, ?)");
            $stmt->execute([$product_id, $type, $qty, $reason]);

            $pdo->commit();
            $msg = '<div style="color: #10b981; margin-bottom: 15px;">Stock updated successfully! New quantity: ' . $new_qty . '</div>';
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $msg = '<div style="color: #ef4444; margin-bottom: 15px;">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// Fetch Products
$products = [];
if ($pdo !== null) {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY name ASC");
    $products = $stmt->fetchAll();
}

// Fetch Inventory Logs
$logs = [];
if ($pdo !== null) {
    $stmt = $pdo->query("SELECT l.*, p.name AS product_name, p.sku FROM inventory_logs l JOIN products p ON l.product_id = p.id ORDER BY l.id DESC LIMIT 20");
    $logs = $stmt->fetchAll();
}
?>

<h1>Inventory Management</h1>

<?php echo $msg; ?>

<!-- Quick Stock Adjustment Form -->
<div class="form-card" style="max-width: 650px;">
    <h2>Stock Adjustment Log</h2>
    <br>
    <form method="POST" action="inventory.php" id="todoForm">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
            <select name="product_id" required style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1.5px solid #e2e8f0; font-family: inherit; font-size: 14px;">
                <option value="">-- Select Product --</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?php echo $p['id']; ?>">
                        <?php echo htmlspecialchars($p['name']); ?> (SKU: <?php echo $p['sku']; ?> | Stock: <?php echo $p['quantity']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="type" required style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1.5px solid #e2e8f0; font-family: inherit; font-size: 14px;">
                <option value="in">➕ Stock In (+)</option>
                <option value="out">➖ Stock Out (-)</option>
                <option value="adjustment">⚙️ Direct Reset</option>
            </select>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px;">
            <input type="number" name="quantity" placeholder="Quantity" min="1" required>
            <input type="text" name="reason" placeholder="Reason (e.g., Stock Restock, Damaged, Restock Adjustment)">
        </div>

        <button type="submit" id="submit">Update Inventory</button>
    </form>
</div>

<h2>Current Stock Status</h2>
<br>

<div class="form-card" style="max-width: 100%;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="border-bottom: 2px solid #e2e8f0; color: #64748b;">
                <th style="padding: 10px;">SKU</th>
                <th style="padding: 10px;">Product Name</th>
                <th style="padding: 10px;">Price</th>
                <th style="padding: 10px;">Current Stock</th>
                <th style="padding: 10px;">Min Level</th>
                <th style="padding: 10px;">Stock Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $prod): ?>
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 10px; font-weight: 600; color: #38bdf8;"><?php echo htmlspecialchars($prod['sku']); ?></td>
                    <td style="padding: 10px; font-weight: 600;"><?php echo htmlspecialchars($prod['name']); ?></td>
                    <td style="padding: 10px;">$<?php echo number_format($prod['price'], 2); ?></td>
                    <td style="padding: 10px; font-weight: 700;"><?php echo $prod['quantity']; ?></td>
                    <td style="padding: 10px; color: #64748b;"><?php echo $prod['min_stock']; ?></td>
                    <td style="padding: 10px;">
                        <?php if ($prod['quantity'] == 0): ?>
                            <span style="background: #fef2f2; color: #ef4444; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700;">OUT OF STOCK</span>
                        <?php elseif ($prod['quantity'] <= $prod['min_stock']): ?>
                            <span style="background: #fffbeb; color: #f59e0b; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700;">LOW STOCK</span>
                        <?php else: ?>
                            <span style="background: #ecfdf5; color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700;">IN STOCK</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<br>
<h2>Recent Inventory Logs</h2>
<br>

<div class="form-card" style="max-width: 100%;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="border-bottom: 2px solid #e2e8f0; color: #64748b;">
                <th style="padding: 10px;">Log ID</th>
                <th style="padding: 10px;">Product</th>
                <th style="padding: 10px;">Type</th>
                <th style="padding: 10px;">Qty</th>
                <th style="padding: 10px;">Reason</th>
                <th style="padding: 10px;">Timestamp</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="6" style="padding: 15px; color: #64748b;">No inventory logs recorded.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 10px;">#<?php echo $log['id']; ?></td>
                        <td style="padding: 10px; font-weight: 600;"><?php echo htmlspecialchars($log['product_name']); ?></td>
                        <td style="padding: 10px;">
                            <?php if ($log['type'] === 'in'): ?>
                                <span style="color: #10b981; font-weight: 700;">STOCK IN</span>
                            <?php elseif ($log['type'] === 'out'): ?>
                                <span style="color: #ef4444; font-weight: 700;">STOCK OUT</span>
                            <?php else: ?>
                                <span style="color: #3b82f6; font-weight: 700;">ADJUSTMENT</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 10px; font-weight: 600;"><?php echo $log['quantity']; ?></td>
                        <td style="padding: 10px; color: #64748b;"><?php echo htmlspecialchars($log['reason'] ?? '-'); ?></td>
                        <td style="padding: 10px; color: #64748b;"><?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
