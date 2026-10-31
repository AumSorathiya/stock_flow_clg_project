<?php
$page_title = 'Stock Flow - Products';
require_once __DIR__ . '/../includes/header.php';

$msg = "";
$edit_product = null;

// Process Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo !== null) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $sku = sanitize($_POST['sku'] ?? '');
        $name = sanitize($_POST['name'] ?? '');
        $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $price = floatval($_POST['price'] ?? 0);
        $cost = floatval($_POST['cost'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 0);
        $min_stock = intval($_POST['min_stock'] ?? 5);
        $description = sanitize($_POST['description'] ?? '');

        if (empty($sku) || empty($name)) {
            $msg = '<div style="color: #ef4444; margin-bottom: 15px;">Please fill in SKU and Product Name.</div>';
        } else {
            try {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("INSERT INTO products (sku, name, category_id, price, cost, quantity, min_stock, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$sku, $name, $category_id, $price, $cost, $quantity, $min_stock, $description]);
                    $msg = '<div style="color: #10b981; margin-bottom: 15px;">Product added successfully!</div>';
                } else if ($action === 'edit') {
                    $id = intval($_POST['id']);
                    $stmt = $pdo->prepare("UPDATE products SET sku=?, name=?, category_id=?, price=?, cost=?, quantity=?, min_stock=?, description=? WHERE id=?");
                    $stmt->execute([$sku, $name, $category_id, $price, $cost, $quantity, $min_stock, $description, $id]);
                    $msg = '<div style="color: #10b981; margin-bottom: 15px;">Product updated successfully!</div>';
                }
            } catch (Exception $e) {
                $msg = '<div style="color: #ef4444; margin-bottom: 15px;">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    } else if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
            $stmt->execute([$id]);
            $msg = '<div style="color: #10b981; margin-bottom: 15px;">Product deleted successfully!</div>';
        } catch (Exception $e) {
            $msg = '<div style="color: #ef4444; margin-bottom: 15px;">Error deleting product: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// Check Edit Mode
if (isset($_GET['edit']) && $pdo !== null) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([$edit_id]);
    $edit_product = $stmt->fetch();
}

// Fetch All Categories for Dropdown
$categories = [];
if ($pdo !== null) {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll();
}

// Fetch All Products
$products = [];
if ($pdo !== null) {
    $stmt = $pdo->query("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
    $products = $stmt->fetchAll();
}
?>

<h1>Products</h1>

<?php echo $msg; ?>

<div class="form-card" style="max-width: 700px;">
    <h2><?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></h2>
    <br>
    <form method="POST" action="products.php" id="todoForm">
        <input type="hidden" name="action" value="<?php echo $edit_product ? 'edit' : 'add'; ?>">
        <?php if ($edit_product): ?>
            <input type="hidden" name="id" value="<?php echo $edit_product['id']; ?>">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <input type="text" name="sku" placeholder="SKU (e.g. PROD-001)" value="<?php echo htmlspecialchars($edit_product['sku'] ?? ''); ?>" required>
            <input type="text" name="name" placeholder="Product Name" value="<?php echo htmlspecialchars($edit_product['name'] ?? ''); ?>" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <select name="category_id" style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1.5px solid #e2e8f0; font-family: inherit; font-size: 14px;">
                <option value="">-- Select Category --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo ($edit_product && $edit_product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="number" step="0.01" name="price" placeholder="Selling Price ($)" value="<?php echo htmlspecialchars($edit_product['price'] ?? ''); ?>" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
            <input type="number" step="0.01" name="cost" placeholder="Cost Price ($)" value="<?php echo htmlspecialchars($edit_product['cost'] ?? ''); ?>">
            <input type="number" name="quantity" placeholder="Stock Qty" value="<?php echo htmlspecialchars($edit_product['quantity'] ?? '0'); ?>" required>
            <input type="number" name="min_stock" placeholder="Min Stock Alert" value="<?php echo htmlspecialchars($edit_product['min_stock'] ?? '5'); ?>" required>
        </div>

        <textarea name="description" placeholder="Product Description..." rows="2" style="width: 100%; padding: 10px; border-radius: 8px; border: 1.5px solid #e2e8f0; font-family: inherit;"><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>

        <div style="display: flex; gap: 10px;">
            <button type="submit" id="submit"><?php echo $edit_product ? 'Update Product' : 'Add Product'; ?></button>
            <?php if ($edit_product): ?>
                <a href="products.php" style="padding: 10px 18px; background: #64748b; color: #fff; border-radius: 8px; font-weight: 600; text-decoration: none;">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<h2>Product Catalog</h2>
<br>

<div class="form-card" style="max-width: 100%;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="border-bottom: 2px solid #e2e8f0; color: #64748b;">
                <th style="padding: 10px;">SKU</th>
                <th style="padding: 10px;">Name</th>
                <th style="padding: 10px;">Category</th>
                <th style="padding: 10px;">Price</th>
                <th style="padding: 10px;">Stock</th>
                <th style="padding: 10px; text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="6" style="padding: 15px; color: #64748b;">No products found in database. Add your first product above!</td>
                </tr>
            <?php else: ?>
                <?php foreach ($products as $prod): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 10px; font-weight: 600; color: #38bdf8;"><?php echo htmlspecialchars($prod['sku']); ?></td>
                        <td style="padding: 10px; font-weight: 600;"><?php echo htmlspecialchars($prod['name']); ?></td>
                        <td style="padding: 10px; color: #64748b;"><?php echo htmlspecialchars($prod['category_name'] ?? 'Uncategorized'); ?></td>
                        <td style="padding: 10px; color: #10b981; font-weight: 700;">$<?php echo number_format($prod['price'], 2); ?></td>
                        <td style="padding: 10px; <?php echo ($prod['quantity'] <= $prod['min_stock']) ? 'color: #ef4444; font-weight: 700;' : ''; ?>">
                            <?php echo $prod['quantity']; ?>
                        </td>
                        <td style="padding: 10px; text-align: right;">
                            <div class="btn" style="justify-content: flex-end;">
                                <a href="products.php?edit=<?php echo $prod['id']; ?>" class="update" style="padding: 6px 12px; border-radius: 6px; text-decoration: none; display: inline-block;">Edit</a>
                                <form method="POST" action="products.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $prod['id']; ?>">
                                    <button type="submit" class="delete">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
