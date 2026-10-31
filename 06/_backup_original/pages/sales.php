<?php
$page_title = 'Stock Flow - Sales Transactions';
require_once __DIR__ . '/../includes/header.php';

$msg = "";

// Handle Record New Sale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo !== null) {
    $product_id = intval($_POST['product_id'] ?? 0);
    $customer_id = !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
    $quantity = intval($_POST['quantity'] ?? 0);
    $unit_price = floatval($_POST['unit_price'] ?? 0);

    if ($product_id <= 0 || $quantity <= 0 || $unit_price <= 0) {
        $msg = '<div style="color: #ef4444; margin-bottom: 15px;">Please select product, valid quantity (> 0), and price.</div>';
    } else {
        try {
            $pdo->beginTransaction();

            // Verify stock level
            $stmt = $pdo->prepare("SELECT name, quantity FROM products WHERE id=?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();

            if (!$product) {
                throw new Exception("Selected product does not exist.");
            }

            if ($product['quantity'] < $quantity) {
                throw new Exception("Insufficient stock for '" . $product['name'] . "'. Available stock: " . $product['quantity']);
            }

            $total_amount = $quantity * $unit_price;

            // 1. Insert Sales Record
            $stmt = $pdo->prepare("INSERT INTO sales (product_id, customer_id, quantity, unit_price, total_amount) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$product_id, $customer_id, $quantity, $unit_price, $total_amount]);

            // 2. Deduct product quantity from database
            $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id=?");
            $stmt->execute([$quantity, $product_id]);

            // 3. Log stock out in inventory_logs
            $stmt = $pdo->prepare("INSERT INTO inventory_logs (product_id, type, quantity, reason) VALUES (?, 'out', ?, ?)");
            $stmt->execute([$product_id, $quantity, "Sale transaction"]);

            $pdo->commit();
            $msg = '<div style="color: #10b981; margin-bottom: 15px;">Sale recorded successfully! Total: $' . number_format($total_amount, 2) . '</div>';
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $msg = '<div style="color: #ef4444; margin-bottom: 15px;">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// Fetch products & customers for form
$products = [];
$customers = [];
$sales_list = [];

if ($pdo !== null) {
    $stmt = $pdo->query("SELECT * FROM products WHERE quantity > 0 ORDER BY name ASC");
    $products = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT * FROM customers ORDER BY name ASC");
    $customers = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT s.*, p.name AS product_name, p.sku, c.name AS customer_name 
                         FROM sales s 
                         JOIN products p ON s.product_id = p.id 
                         LEFT JOIN customers c ON s.customer_id = c.id 
                         ORDER BY s.id DESC");
    $sales_list = $stmt->fetchAll();
}
?>

<h1>Sales</h1>

<?php echo $msg; ?>

<div class="form-card" style="max-width: 650px;">
    <h2>Record New Sale</h2>
    <br>
    <form method="POST" action="sales.php" id="todoForm">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
            <select name="product_id" id="sales_product_select" required style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1.5px solid #e2e8f0; font-family: inherit; font-size: 14px;" onchange="updatePriceFromSelect(this)">
                <option value="" data-price="0">-- Select Product --</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['price']; ?>">
                        <?php echo htmlspecialchars($p['name']); ?> ($<?php echo number_format($p['price'], 2); ?> | Stock: <?php echo $p['quantity']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="customer_id" style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1.5px solid #e2e8f0; font-family: inherit; font-size: 14px;">
                <option value="">-- Guest / Select Customer --</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <input type="number" name="quantity" placeholder="Quantity" min="1" required>
            <input type="number" step="0.01" name="unit_price" id="unit_price_input" placeholder="Unit Price ($)" required>
        </div>

        <button type="submit" id="submit">Complete Sale</button>
    </form>
</div>

<h2>Sales History</h2>
<br>

<div class="form-card" style="max-width: 100%;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="border-bottom: 2px solid #e2e8f0; color: #64748b;">
                <th style="padding: 10px;">ID</th>
                <th style="padding: 10px;">Product Name</th>
                <th style="padding: 10px;">Customer</th>
                <th style="padding: 10px;">Qty</th>
                <th style="padding: 10px;">Unit Price</th>
                <th style="padding: 10px;">Total Amount</th>
                <th style="padding: 10px;">Sale Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($sales_list)): ?>
                <tr>
                    <td colspan="7" style="padding: 15px; color: #64748b;">No sales transactions recorded yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($sales_list as $s): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 10px;">#<?php echo $s['id']; ?></td>
                        <td style="padding: 10px; font-weight: 600;"><?php echo htmlspecialchars($s['product_name']); ?></td>
                        <td style="padding: 10px; color: #64748b;"><?php echo htmlspecialchars($s['customer_name'] ?? 'Walk-in Customer'); ?></td>
                        <td style="padding: 10px;"><?php echo $s['quantity']; ?></td>
                        <td style="padding: 10px;">$<?php echo number_format($s['unit_price'], 2); ?></td>
                        <td style="padding: 10px; color: #10b981; font-weight: 700;">$<?php echo number_format($s['total_amount'], 2); ?></td>
                        <td style="padding: 10px; color: #64748b;"><?php echo date('M d, Y H:i', strtotime($s['sale_date'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function updatePriceFromSelect(selectElem) {
    var selectedOption = selectElem.options[selectElem.selectedIndex];
    var price = selectedOption.getAttribute('data-price');
    if (price && price > 0) {
        document.getElementById('unit_price_input').value = price;
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
