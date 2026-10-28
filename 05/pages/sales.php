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
    $stmt = $pdo->query("SELECT * FROM products WHERE quantity > 0 ORDER BY name ASC");
    $products = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT * FROM customers ORDER BY name ASC");
    $customers = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT s.*, p.name AS product_name, p.sku, c.name AS customer_name, u.username, u.full_name AS cashier_name 
                         FROM sales s 
                         JOIN products p ON s.product_id = p.id 
                         LEFT JOIN customers c ON s.customer_id = c.id 
                         LEFT JOIN users u ON s.user_id = u.id
                         ORDER BY s.id DESC LIMIT 50");
    $sales_list = $stmt->fetchAll();
}

$page_title = 'Stock Flow - Sales Transactions';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-title-row">
    <div>
        <h1>Sales Transactions</h1>
        <p style="color: #64748b; font-size: 14px; margin-top: 4px;">Record customer orders with real-time stock deduction and inventory synchronization.</p>
    </div>
</div>

<!-- New Sale Order Entry Card -->
<div class="form-card" style="max-width: 750px;">
    <h2>➕ New Sales Order</h2>
    <form method="POST" action="sales.php" class="app-form">
        <?php echo csrf_field(); ?>

        <div class="form-row form-row-2">
            <div class="form-group">
                <label class="form-label">Product to Sell *</label>
                <select name="product_id" id="sales_product_select" class="form-control" required>
                    <option value="" data-price="0">-- Select In-Stock Product --</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['price']; ?>">
                            <?php echo htmlspecialchars($p['name']); ?> ($<?php echo number_format($p['price'], 2); ?> | Avail: <?php echo $p['quantity']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Customer</label>
                <select name="customer_id" class="form-control">
                    <option value="">-- Walk-in / Guest Customer --</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
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
    <h2>Sales Order History (Recent <?php echo count($sales_list); ?>)</h2>
    <div class="table-responsive">
        <table class="app-table">
            <thead>
                <tr>
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
                    <tr>
                        <td colspan="8" style="padding: 20px; text-align: center; color: #64748b;">No sales transactions recorded yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sales_list as $s): ?>
                        <tr>
                            <td><strong>#SL-<?php echo str_pad($s['id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                            <td>
                                <strong><?php echo htmlspecialchars($s['product_name']); ?></strong>
                                <span style="font-size: 11px; color: #64748b; display: block;"><?php echo htmlspecialchars($s['sku']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($s['customer_name'] ?? 'Walk-in Customer'); ?></td>
                            <td><strong><?php echo $s['quantity']; ?></strong></td>
                            <td>$<?php echo number_format($s['unit_price'], 2); ?></td>
                            <td style="color: #059669; font-weight: 700;">$<?php echo number_format($s['total_amount'], 2); ?></td>
                            <td style="color: #64748b; font-size: 13px;">
                                <?php echo htmlspecialchars($s['cashier_name'] ?? $s['username'] ?? 'Staff'); ?>
                            </td>
                            <td style="color: #64748b; font-size: 13px;"><?php echo date('M d, Y H:i', strtotime($s['sale_date'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
