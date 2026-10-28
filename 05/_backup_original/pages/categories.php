<?php
$page_title = 'Stock Flow - Categories';
require_once __DIR__ . '/../includes/header.php';

$msg = "";
$edit_cat = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo !== null) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');

        if (empty($name)) {
            $msg = '<div style="color: #ef4444; margin-bottom: 15px;">Category Name is required.</div>';
        } else {
            try {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                    $stmt->execute([$name, $description]);
                    $msg = '<div style="color: #10b981; margin-bottom: 15px;">Category added successfully!</div>';
                } else if ($action === 'edit') {
                    $id = intval($_POST['id']);
                    $stmt = $pdo->prepare("UPDATE categories SET name=?, description=? WHERE id=?");
                    $stmt->execute([$name, $description, $id]);
                    $msg = '<div style="color: #10b981; margin-bottom: 15px;">Category updated successfully!</div>';
                }
            } catch (Exception $e) {
                $msg = '<div style="color: #ef4444; margin-bottom: 15px;">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    } else if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id=?");
            $stmt->execute([$id]);
            $msg = '<div style="color: #10b981; margin-bottom: 15px;">Category deleted successfully!</div>';
        } catch (Exception $e) {
            $msg = '<div style="color: #ef4444; margin-bottom: 15px;">Error deleting category: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

if (isset($_GET['edit']) && $pdo !== null) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id=?");
    $stmt->execute([$edit_id]);
    $edit_cat = $stmt->fetch();
}

$categories = [];
if ($pdo !== null) {
    $stmt = $pdo->query("SELECT c.*, COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY c.id DESC");
    $categories = $stmt->fetchAll();
}
?>

<h1>Categories</h1>

<?php echo $msg; ?>

<div class="form-card" style="max-width: 600px;">
    <h2><?php echo $edit_cat ? 'Edit Category' : 'Add New Category'; ?></h2>
    <br>
    <form method="POST" action="categories.php" id="todoForm">
        <input type="hidden" name="action" value="<?php echo $edit_cat ? 'edit' : 'add'; ?>">
        <?php if ($edit_cat): ?>
            <input type="hidden" name="id" value="<?php echo $edit_cat['id']; ?>">
        <?php endif; ?>

        <input type="text" name="name" placeholder="Category Name" value="<?php echo htmlspecialchars($edit_cat['name'] ?? ''); ?>" required>
        <textarea name="description" placeholder="Category Description..." rows="2" style="width: 100%; padding: 10px; border-radius: 8px; border: 1.5px solid #e2e8f0; font-family: inherit;"><?php echo htmlspecialchars($edit_cat['description'] ?? ''); ?></textarea>

        <div style="display: flex; gap: 10px;">
            <button type="submit" id="submit"><?php echo $edit_cat ? 'Update Category' : 'Add Category'; ?></button>
            <?php if ($edit_cat): ?>
                <a href="categories.php" style="padding: 10px 18px; background: #64748b; color: #fff; border-radius: 8px; font-weight: 600; text-decoration: none;">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<h2>Category List</h2>
<br>

<div class="form-card" style="max-width: 100%;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="border-bottom: 2px solid #e2e8f0; color: #64748b;">
                <th style="padding: 10px;">ID</th>
                <th style="padding: 10px;">Name</th>
                <th style="padding: 10px;">Description</th>
                <th style="padding: 10px;">Products Count</th>
                <th style="padding: 10px; text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($categories)): ?>
                <tr>
                    <td colspan="5" style="padding: 15px; color: #64748b;">No categories created yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($categories as $cat): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 10px;">#<?php echo $cat['id']; ?></td>
                        <td style="padding: 10px; font-weight: 600;"><?php echo htmlspecialchars($cat['name']); ?></td>
                        <td style="padding: 10px; color: #64748b;"><?php echo htmlspecialchars($cat['description'] ?? '-'); ?></td>
                        <td style="padding: 10px; font-weight: 600; color: #0284c7;"><?php echo $cat['product_count']; ?></td>
                        <td style="padding: 10px; text-align: right;">
                            <div class="btn" style="justify-content: flex-end;">
                                <a href="categories.php?edit=<?php echo $cat['id']; ?>" class="update" style="padding: 6px 12px; border-radius: 6px; text-decoration: none; display: inline-block;">Edit</a>
                                <form method="POST" action="categories.php" style="display: inline;" onsubmit="return confirm('Deleting a category will unassign associated products. Proceed?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
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
