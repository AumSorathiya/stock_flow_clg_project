<?php
require_once __DIR__ . '/../config/db.php';
check_login();

$can_modify = has_role(['admin', 'manager']);
$edit_product = null;

// Process Form Submissions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $pdo !== null) {
    if (!verify_csrf()) {
        set_flash_message('error', 'Security token mismatch. Please try again.');
        header("Location: products.php");
        exit;
    }

    $action = $_POST['action'] ?? '';

    // Authorization check for catalog changes
    if (!$can_modify) {
        set_flash_message('error', 'Permission Denied: Staff accounts have read-only access to the product catalog.');
        header("Location: products.php");
        exit;
    }

    if ($action === 'add' || $action === 'edit') {
        $sku         = strtoupper(trim($_POST['sku'] ?? ''));
        $name        = trim($_POST['name'] ?? '');
        $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $price       = floatval($_POST['price'] ?? 0);
        $cost        = floatval($_POST['cost'] ?? 0);
        $quantity    = intval($_POST['quantity'] ?? 0);
        $min_stock   = intval($_POST['min_stock'] ?? 5);
        $description = trim($_POST['description'] ?? '');

        if (empty($sku) || empty($name)) {
            set_flash_message('error', 'SKU and Product Name are mandatory fields.');
        } elseif ($price < 0 || $cost < 0 || $quantity < 0 || $min_stock < 0) {
            set_flash_message('error', 'Prices, costs, quantities, and thresholds cannot be negative.');
        } else {
            try {
                if ($action === 'add') {
                    // Check duplicate SKU
                    $chk = $pdo->prepare("SELECT id FROM products WHERE sku = ?");
                    $chk->execute([$sku]);
                    if ($chk->fetch()) {
                        set_flash_message('error', "SKU '{$sku}' already exists. Please specify a unique SKU.");
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO products (sku, name, category_id, price, cost, quantity, min_stock, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$sku, $name, $category_id, $price, $cost, $quantity, $min_stock, $description]);

                        $new_id = $pdo->lastInsertId();
                        // If initial quantity > 0, log an inventory entry
                        if ($quantity > 0) {
                            $user_id = $_SESSION['user_id'] ?? null;
                            $log_stmt = $pdo->prepare("INSERT INTO inventory_logs (user_id, product_id, type, quantity, reason) VALUES (?, ?, 'in', ?, 'Initial stock upon product creation')");
                            $log_stmt->execute([$user_id, $new_id, $quantity]);
                        }

                        log_activity($pdo, 'Product Added', 'Catalog', "Added product '{$name}' (SKU: {$sku})");
                        set_flash_message('success', "Product '{$name}' added to catalog successfully!");
                        header("Location: products.php");
                        exit;
                    }
                } elseif ($action === 'edit') {
                    $id = intval($_POST['id'] ?? 0);

                    // Check duplicate SKU on other products
                    $chk = $pdo->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
                    $chk->execute([$sku, $id]);
                    if ($chk->fetch()) {
                        set_flash_message('error', "SKU '{$sku}' is already assigned to another product.");
                    } else {
                        $stmt = $pdo->prepare("UPDATE products SET sku=?, name=?, category_id=?, price=?, cost=?, quantity=?, min_stock=?, description=? WHERE id=?");
                        $stmt->execute([$sku, $name, $category_id, $price, $cost, $quantity, $min_stock, $description, $id]);

                        log_activity($pdo, 'Product Updated', 'Catalog', "Updated product #{$id} ({$name})");
                        set_flash_message('success', "Product '{$name}' updated successfully!");
                        header("Location: products.php");
                        exit;
                    }
                }
            } catch (Exception $e) {
                set_flash_message('error', 'Database Error: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        try {
            // Check for related sales or purchases
            $s_chk = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE product_id = ?");
            $s_chk->execute([$id]);
            $s_count = $s_chk->fetchColumn();

            $p_chk = $pdo->prepare("SELECT COUNT(*) FROM purchases WHERE product_id = ?");
            $p_chk->execute([$id]);
            $p_count = $p_chk->fetchColumn();

            if ($s_count > 0 || $p_count > 0) {
                set_flash_message('error', "Cannot delete product: It is linked to {$s_count} sales order(s) and {$p_count} purchase(s). To keep financial records consistent, adjust stock to 0 instead of deleting.");
            } else {
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$id]);

                log_activity($pdo, 'Product Deleted', 'Catalog', "Deleted product ID #{$id}");
                set_flash_message('success', "Product deleted successfully from catalog.");
            }
        } catch (Exception $e) {
            set_flash_message('error', 'Error deleting product: ' . $e->getMessage());
        }
        header("Location: products.php");
        exit;
    }
}

// Fetch for edit mode
if (isset($_GET['edit']) && $pdo !== null && $can_modify) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_product = $stmt->fetch();
}

// Fetch all categories for dropdown
$categories = [];
if ($pdo !== null) {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll();
}

// Fetch all products
$products = [];
if ($pdo !== null) {
    $stmt = $pdo->query("SELECT p.*, c.name AS category_name 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         ORDER BY p.id ASC");
    $products = $stmt->fetchAll();
}

$page_title = 'Stock Flow - Product Catalog';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-title-row">
    <div>
        <h1>Product Catalog</h1>
        <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">Manage inventory items, pricing, costs, and minimum threshold alerts.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <?php if ($can_modify): ?>
            <button type="button" id="toggleProductFormBtn" class="btn btn-primary btn-sm" onclick="toggleFormCard('productFormCard', 'toggleProductFormBtn')">
                <span>➕ Add New Product</span>
            </button>
        <?php else: ?>
            <span class="badge badge-secondary" style="font-size: 12px; padding: 6px 12px;">Staff View: Read-Only</span>
        <?php endif; ?>
    </div>
</div>

<!-- Add / Edit Form Card (Admin & Manager Only) -->
<?php if ($can_modify): ?>
<div class="form-card collapsible-form-container <?php echo $edit_product ? '' : 'collapsed'; ?>" id="productFormCard" style="max-width: 800px; <?php echo $edit_product ? '' : 'display: none;'; ?>">
    <h2>
        <span><?php echo $edit_product ? '✏️ Edit Product Details' : '➕ Register New Product'; ?></span>
        <?php if ($edit_product): ?>
            <a href="products.php" class="btn btn-secondary btn-sm">Cancel Edit</a>
        <?php endif; ?>
    </h2>

    <form method="POST" action="products.php" class="app-form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="<?php echo $edit_product ? 'edit' : 'add'; ?>">
        <?php if ($edit_product): ?>
            <input type="hidden" name="id" value="<?php echo $edit_product['id']; ?>">
        <?php endif; ?>

        <div class="form-row form-row-2">
            <div class="form-group">
                <label class="form-label">SKU (Unique Code) *</label>
                <input type="text" name="sku" class="form-control" placeholder="e.g. PROD-101" value="<?php echo htmlspecialchars($edit_product['sku'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Product Name *</label>
                <input type="text" name="name" class="form-control" placeholder="e.g. Wireless Ergonomic Mouse" value="<?php echo htmlspecialchars($edit_product['name'] ?? ''); ?>" required>
            </div>
        </div>

        <div class="form-row form-row-3">
            <div class="form-group">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-control">
                    <option value="">-- Uncategorized --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($edit_product && $edit_product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                            #<?php echo $cat['id']; ?> - <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Selling Price ($) *</label>
                <input type="number" step="0.01" min="0" name="price" class="form-control" placeholder="0.00" value="<?php echo htmlspecialchars($edit_product['price'] ?? '0.00'); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Unit Cost ($)</label>
                <input type="number" step="0.01" min="0" name="cost" class="form-control" placeholder="0.00" value="<?php echo htmlspecialchars($edit_product['cost'] ?? '0.00'); ?>">
            </div>
        </div>

        <div class="form-row form-row-2">
            <div class="form-group">
                <label class="form-label">Current Stock Quantity *</label>
                <input type="number" min="0" name="quantity" class="form-control" placeholder="0" value="<?php echo htmlspecialchars($edit_product['quantity'] ?? '0'); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Safety Alert Threshold (Min Stock) *</label>
                <input type="number" min="0" name="min_stock" class="form-control" placeholder="5" value="<?php echo htmlspecialchars($edit_product['min_stock'] ?? '5'); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Product Description</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Brief technical specifications or packaging notes..."><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">
                <?php echo $edit_product ? '💾 Save Product Changes' : '➕ Save Product'; ?>
            </button>
            <?php if ($edit_product): ?>
                <a href="products.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Products List Table Card -->
<div class="form-card" style="max-width: 100%;">
    <h2>
        <span>📦 Products in Catalog (<?php echo count($products); ?>)</span>
        <button type="button" class="btn-export-csv" onclick="exportTableToCSV('productsTable', 'products_catalog')">
            📥 Export CSV
        </button>
    </h2>

    <!-- Table Filter Toolbar -->
    <div class="table-filter-bar" data-table="productsTable">
        <div class="table-filter-inputs">
            <div class="filter-input-wrapper">
                <span class="filter-icon">🔍</span>
                <input type="text" class="filter-control filter-search" placeholder="Search ID, SKU, product name...">
            </div>

            <select class="filter-control filter-select" data-filter="category">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars(strtolower($cat['name'])); ?>">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
                <option value="uncategorized">Uncategorized</option>
            </select>

            <select class="filter-control filter-select" data-filter="status">
                <option value="">All Stock Health</option>
                <option value="in_stock">In Stock (Healthy)</option>
                <option value="low_stock">Low Stock (Alert)</option>
                <option value="out_of_stock">Out of Stock</option>
            </select>

            <select class="filter-control filter-sort">
                <option value="id-asc">Sort: ID (1 → N)</option>
                <option value="id-desc">Sort: ID (N → 1)</option>
                <option value="name-asc">Sort: Name (A-Z)</option>
                <option value="price-asc">Sort: Price (Low → High)</option>
                <option value="price-desc">Sort: Price (High → Low)</option>
                <option value="stock-asc">Sort: Stock (Low → High)</option>
                <option value="stock-desc">Sort: Stock (High → Low)</option>
            </select>

            <button type="button" class="btn-filter-reset">↺ Reset</button>
        </div>
        <div class="table-filter-meta">
            <span class="filter-count-badge">Showing <?php echo count($products); ?> of <?php echo count($products); ?></span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="app-table" id="productsTable">
            <thead>
                <tr>
                    <th style="width: 70px;">ID</th>
                    <th>SKU</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Selling Price</th>
                    <th>Cost Price</th>
                    <th>In Stock</th>
                    <th>Status</th>
                    <?php if ($can_modify): ?>
                        <th style="text-align: right;">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr class="empty-state-row">
                        <td colspan="<?php echo $can_modify ? '9' : '8'; ?>" style="padding: 24px; text-align: center; color: var(--text-muted);">
                            No products found in the catalog.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $prod): 
                        $status_key = ($prod['quantity'] == 0) ? 'out_of_stock' : (($prod['quantity'] <= $prod['min_stock']) ? 'low_stock' : 'in_stock');
                    ?>
                        <tr data-id="<?php echo $prod['id']; ?>" 
                            data-name="<?php echo htmlspecialchars($prod['name']); ?>" 
                            data-sku="<?php echo htmlspecialchars($prod['sku']); ?>"
                            data-category="<?php echo htmlspecialchars(strtolower($prod['category_name'] ?? 'uncategorized')); ?>" 
                            data-status="<?php echo $status_key; ?>" 
                            data-price="<?php echo $prod['price']; ?>" 
                            data-cost="<?php echo $prod['cost']; ?>" 
                            data-stock="<?php echo $prod['quantity']; ?>">
                            <td class="cell-id">#<?php echo $prod['id']; ?></td>
                            <td class="cell-sku"><?php echo htmlspecialchars($prod['sku']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($prod['name']); ?></strong>
                                <?php if (!empty($prod['description'])): ?>
                                    <div style="font-size: 12px; color: var(--text-muted);"><?php echo htmlspecialchars(substr($prod['description'], 0, 60)) . (strlen($prod['description']) > 60 ? '...' : ''); ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="color: var(--text-muted);"><?php echo htmlspecialchars($prod['category_name'] ?? 'Uncategorized'); ?></td>
                            <td class="cell-price">$<?php echo number_format($prod['price'], 2); ?></td>
                            <td style="color: var(--text-secondary);">$<?php echo number_format($prod['cost'], 2); ?></td>
                            <td>
                                <strong style="font-size: 15px; <?php echo ($prod['quantity'] <= $prod['min_stock']) ? 'color: #dc2626;' : ''; ?>">
                                    <?php echo $prod['quantity']; ?>
                                </strong>
                                <span style="font-size: 11px; color: var(--text-light);">/ min <?php echo $prod['min_stock']; ?></span>
                            </td>
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
                                        <span class="pulse-dot pulse-emerald" style="width:5px;height:5px;"></span> IN STOCK
                                    </span>
                                <?php endif; ?>
                            </td>
                            <?php if ($can_modify): ?>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <a href="products.php?edit=<?php echo $prod['id']; ?>" class="btn btn-warning btn-sm" title="Edit Product">
                                            Edit
                                        </a>
                                        <form method="POST" action="products.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove <?php echo htmlspecialchars(addslashes($prod['name'])); ?>?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $prod['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete Product">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
