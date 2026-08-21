<?php
$page_title = 'Stock Flow - Purchase Orders';
require_once __DIR__ . '/../includes/header.php';

$msg = "";

// Handle Record New Purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo !== null) {
    $product_id = intval($_POST['product_id'] ?? 0);
    $supplier_id = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null;
    $quantity = intval($_POST['quantity'] ?? 0);
    $unit_cost = floatval($_POST['unit_cost'] ?? 0);

    if ($product_id <= 0 || $quantity <= 0 || $unit_cost < 0) {
        $msg = '<div style="color: #ef4444; margin-bottom: 15px;">Please select product, valid quantity (> 0), and cost.</div>';
    } else {
        try {
            $pdo->beginTransaction();

            $total_amount = $quantity * $unit_cost;

            // 1. Insert Purchase Record
            $stmt = $pdo->prepare("INSERT INTO purchases (product_id, supplier_id, quantity, unit_cost, total_amount) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$product_id, $supplier_id, $quantity, $unit_cost, $total_amount]);

            // 2. Increase product stock quantity
            $stmt = $pdo->prepare("UPDATE products SET quantity = quantity + ?, cost = ? WHERE id=?");
            $stmt->execute([$quantity, $unit_cost, $product_id]);

            // 3. Log stock in in inventory_logs
            $stmt = $pdo->prepare("INSERT INTO inventory_logs (product_id, type, quantity, reason) VALUES (?, 'in', ?, ?)");
            $stmt->execute([$product_id, $quantity, "Purchase Restock"]);

            $pdo->commit();
            $msg = '<div style="color: #10b981; margin-bottom: 15px;">Purchase order recorded successfully! Total: $' . number_format($total_amount, 2) . '</div>';
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $msg = '<div style="color: #ef4444; margin-bottom: 15px;">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// Fetch products & suppliers
$products = [];
$suppliers = [];
$purchases_list = [];

if ($pdo !== null) {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY name ASC");
    $products = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT * FROM suppliers ORDER BY name ASC");
    $suppliers = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT pu.*, p.name AS product_name, p.sku, s.name AS supplier_name, s.company 
                         FROM purchases pu 
                         JOIN products p ON pu.product_id = p.id 
                         LEFT JOIN suppliers s ON pu.supplier_id = s.id 
                         ORDER BY pu.id DESC");
    $purchases_list = $stmt->fetchAll();
}
?>

<h1>Purchases</h1>

<?php echo $msg; ?>

<div class="form-card" style="max-width: 650px;">
    <h2>Record Purchase Order</h2>
    <br>
    <form method="POST" action="purchases.php" id="todoForm">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
            <select name="product_id" id="purchase_product_select" required style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1.5px solid #e2e8f0; font-family: inherit; font-size: 14px;" onchange="updateCostFromSelect(this)">
                <option value="" data-cost="0">-- Select Product --</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?php echo $p['id']; ?>" data-cost="<?php echo $p['cost']; ?>">
                        <?php echo htmlspecialchars($p['name']); ?> (Current Stock: <?php echo $p['quantity']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="supplier_id" style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1.5px solid #e2e8f0; font-family: inherit; font-size: 14px;">
                <option value="">-- Select Supplier --</option>
                <?php foreach ($suppliers as $sup): ?>
                    <option value="<?php echo $sup['id']; ?>">
                        <?php echo htmlspecialchars($sup['name']); ?> (<?php echo htmlspecialchars($sup['company'] ?? 'Supplier'); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <input type="number" name="quantity" placeholder="Quantity Purchased" min="1" required>
            <input type="number" step="0.01" name="unit_cost" id="unit_cost_input" placeholder="Unit Cost ($)" required>
        </div>

        <button type="submit" id="submit">Record Purchase Order</button>
    </form>
</div>

<h2>Purchase History</h2>
<br>

<div class="form-card" style="max-width: 100%;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="border-bottom: 2px solid #e2e8f0; color: #64748b;">
                <th style="padding: 10px;">ID</th>
                <th style="padding: 10px;">Product Name</th>
                <th style="padding: 10px;">Supplier</th>
                <th style="padding: 10px;">Qty</th>
                <th style="padding: 10px;">Unit Cost</th>
                <th style="padding: 10px;">Total Cost</th>
                <th style="padding: 10px;">Purchase Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($purchases_list)): ?>
                <tr>
                    <td colspan="7" style="padding: 15px; color: #64748b;">No purchase transactions recorded.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($purchases_list as $pu): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 10px;">#<?php echo $pu['id']; ?></td>
                        <td style="padding: 10px; font-weight: 600;"><?php echo htmlspecialchars($pu['product_name']); ?></td>
                        <td style="padding: 10px; color: #64748b;"><?php echo htmlspecialchars($pu['supplier_name'] ?? 'General Supplier'); ?></td>
                        <td style="padding: 10px; font-weight: 600; color: #3b82f6;"><?php echo $pu['quantity']; ?></td>
                        <td style="padding: 10px;">$<?php echo number_format($pu['unit_cost'], 2); ?></td>
                        <td style="padding: 10px; font-weight: 700; color: #1e293b;">$<?php echo number_format($pu['total_amount'], 2); ?></td>
                        <td style="padding: 10px; color: #64748b;"><?php echo date('M d, Y H:i', strtotime($pu['purchase_date'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function updateCostFromSelect(selectElem) {
    var selectedOption = selectElem.options[selectElem.selectedIndex];
    var cost = selectedOption.getAttribute('data-cost');
    if (cost && cost > 0) {
        document.getElementById('unit_cost_input').value = cost;
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
