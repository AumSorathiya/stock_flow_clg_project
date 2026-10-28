<?php
require_once __DIR__ . '/../config/db.php';
check_login();

$can_modify = has_role(['admin', 'manager']);
$edit_cat = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $pdo !== null) {
    if (!verify_csrf()) {
        set_flash_message('error', 'Security token mismatch. Please try again.');
        header("Location: categories.php");
        exit;
    }

    $action = $_POST['action'] ?? '';

    if (!$can_modify) {
        set_flash_message('error', 'Permission Denied: Staff accounts have read-only access to categories.');
        header("Location: categories.php");
        exit;
    }

    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            set_flash_message('error', 'Category Name is required.');
        } else {
            try {
                if ($action === 'add') {
                    $chk = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
                    $chk->execute([$name]);
                    if ($chk->fetch()) {
                        set_flash_message('error', "Category '{$name}' already exists.");
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                        $stmt->execute([$name, $description]);

                        log_activity($pdo, 'Category Added', 'Catalog', "Added category '{$name}'");
                        set_flash_message('success', "Category '{$name}' created successfully!");
                        header("Location: categories.php");
                        exit;
                    }
                } elseif ($action === 'edit') {
                    $id = intval($_POST['id'] ?? 0);
                    $chk = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
                    $chk->execute([$name, $id]);
                    if ($chk->fetch()) {
                        set_flash_message('error', "A category named '{$name}' already exists.");
                    } else {
                        $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                        $stmt->execute([$name, $description, $id]);

                        log_activity($pdo, 'Category Updated', 'Catalog', "Updated category #{$id} ({$name})");
                        set_flash_message('success', "Category '{$name}' updated successfully!");
                        header("Location: categories.php");
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
            $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $cat = $stmt->fetch();

            if ($cat) {
                // Delete category; foreign key ON DELETE SET NULL unlinks products safely
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$id]);

                log_activity($pdo, 'Category Deleted', 'Catalog', "Deleted category '{$cat['name']}'");
                set_flash_message('success', "Category '{$cat['name']}' was deleted successfully.");
            }
        } catch (Exception $e) {
            set_flash_message('error', 'Error deleting category: ' . $e->getMessage());
        }
        header("Location: categories.php");
        exit;
    }
}

if (isset($_GET['edit']) && $pdo !== null && $can_modify) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_cat = $stmt->fetch();
}

$categories = [];
if ($pdo !== null) {
    $stmt = $pdo->query("SELECT c.*, COUNT(p.id) AS product_count 
                         FROM categories c 
                         LEFT JOIN products p ON c.id = p.category_id 
                         GROUP BY c.id 
                         ORDER BY c.id DESC");
    $categories = $stmt->fetchAll();
}

$page_title = 'Stock Flow - Categories';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-title-row">
    <div>
        <h1>Category Management</h1>
        <p style="color: #64748b; font-size: 14px; margin-top: 4px;">Classify inventory items into logical product groups.</p>
    </div>
    <?php if (!$can_modify): ?>
        <span class="badge badge-secondary" style="font-size: 12px; padding: 6px 12px;">Staff View: Read-Only</span>
    <?php endif; ?>
</div>

<?php if ($can_modify): ?>
<div class="form-card" style="max-width: 650px;">
    <h2>
        <span><?php echo $edit_cat ? '✏️ Edit Category' : '➕ Create New Category'; ?></span>
        <?php if ($edit_cat): ?>
            <a href="categories.php" class="btn btn-secondary btn-sm">Cancel Edit</a>
        <?php endif; ?>
    </h2>

    <form method="POST" action="categories.php" class="app-form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="<?php echo $edit_cat ? 'edit' : 'add'; ?>">
        <?php if ($edit_cat): ?>
            <input type="hidden" name="id" value="<?php echo $edit_cat['id']; ?>">
        <?php endif; ?>

        <div class="form-group">
            <label class="form-label">Category Name *</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Computer Hardware, Office Furniture" value="<?php echo htmlspecialchars($edit_cat['name'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label">Description (Optional)</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Brief scope or classification guidelines..."><?php echo htmlspecialchars($edit_cat['description'] ?? ''); ?></textarea>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">
                <?php echo $edit_cat ? '💾 Update Category' : '➕ Add Category'; ?>
            </button>
            <?php if ($edit_cat): ?>
                <a href="categories.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="form-card" style="max-width: 100%;">
    <h2>All Categories (<?php echo count($categories); ?>)</h2>
    <div class="table-responsive">
        <table class="app-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Category Name</th>
                    <th>Description</th>
                    <th>Assigned Products</th>
                    <?php if ($can_modify): ?>
                        <th style="text-align: right;">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="<?php echo $can_modify ? '5' : '4'; ?>" style="padding: 20px; text-align: center; color: #64748b;">
                            No categories created yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td>#<?php echo $cat['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                            <td style="color: #64748b;"><?php echo htmlspecialchars($cat['description'] ?? '-'); ?></td>
                            <td>
                                <span class="badge badge-info"><?php echo $cat['product_count']; ?> products</span>
                            </td>
                            <?php if ($can_modify): ?>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <a href="categories.php?edit=<?php echo $cat['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <form method="POST" action="categories.php" style="display: inline;" onsubmit="return confirm('Deleting category <?php echo htmlspecialchars(addslashes($cat['name'])); ?> will unassign its products. Continue?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
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
