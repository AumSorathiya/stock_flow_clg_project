<?php
$page_title = 'Stock Flow - Suppliers';
require_once __DIR__ . '/../includes/header.php';

$msg = "";
$edit_sup = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo !== null) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name = sanitize($_POST['name'] ?? '');
        $company = sanitize($_POST['company'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');

        if (empty($name)) {
            $msg = '<div style="color: #ef4444; margin-bottom: 15px;">Supplier Contact Name is required.</div>';
        } else {
            try {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("INSERT INTO suppliers (name, company, email, phone, address) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $company, $email, $phone, $address]);
                    $msg = '<div style="color: #10b981; margin-bottom: 15px;">Supplier added successfully!</div>';
                } else if ($action === 'edit') {
                    $id = intval($_POST['id']);
                    $stmt = $pdo->prepare("UPDATE suppliers SET name=?, company=?, email=?, phone=?, address=? WHERE id=?");
                    $stmt->execute([$name, $company, $email, $phone, $address, $id]);
                    $msg = '<div style="color: #10b981; margin-bottom: 15px;">Supplier updated successfully!</div>';
                }
            } catch (Exception $e) {
                $msg = '<div style="color: #ef4444; margin-bottom: 15px;">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    } else if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id=?");
            $stmt->execute([$id]);
            $msg = '<div style="color: #10b981; margin-bottom: 15px;">Supplier deleted successfully!</div>';
        } catch (Exception $e) {
            $msg = '<div style="color: #ef4444; margin-bottom: 15px;">Error deleting supplier: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

if (isset($_GET['edit']) && $pdo !== null) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id=?");
    $stmt->execute([$edit_id]);
    $edit_sup = $stmt->fetch();
}

$suppliers = [];
if ($pdo !== null) {
    $stmt = $pdo->query("SELECT s.*, COUNT(pu.id) AS total_orders, COALESCE(SUM(pu.total_amount), 0) AS total_purchased 
                         FROM suppliers s 
                         LEFT JOIN purchases pu ON s.id = pu.supplier_id 
                         GROUP BY s.id 
                         ORDER BY s.id DESC");
    $suppliers = $stmt->fetchAll();
}
?>

<h1>Supplier Directory</h1>

<?php echo $msg; ?>

<div class="form-card" style="max-width: 600px;">
    <h2><?php echo $edit_sup ? 'Edit Supplier' : 'Add New Supplier'; ?></h2>
    <br>
    <form method="POST" action="suppliers.php" id="todoForm">
        <input type="hidden" name="action" value="<?php echo $edit_sup ? 'edit' : 'add'; ?>">
        <?php if ($edit_sup): ?>
            <input type="hidden" name="id" value="<?php echo $edit_sup['id']; ?>">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <input type="text" name="name" placeholder="Contact Name" value="<?php echo htmlspecialchars($edit_sup['name'] ?? ''); ?>" required>
            <input type="text" name="company" placeholder="Company Name" value="<?php echo htmlspecialchars($edit_sup['company'] ?? ''); ?>">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <input type="email" name="email" placeholder="Email Address" value="<?php echo htmlspecialchars($edit_sup['email'] ?? ''); ?>">
            <input type="text" name="phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($edit_sup['phone'] ?? ''); ?>">
        </div>

        <textarea name="address" placeholder="Company Address..." rows="2" style="width: 100%; padding: 10px; border-radius: 8px; border: 1.5px solid #e2e8f0; font-family: inherit;"><?php echo htmlspecialchars($edit_sup['address'] ?? ''); ?></textarea>

        <div style="display: flex; gap: 10px;">
            <button type="submit" id="submit"><?php echo $edit_sup ? 'Update Supplier' : 'Add Supplier'; ?></button>
            <?php if ($edit_sup): ?>
                <a href="suppliers.php" style="padding: 10px 18px; background: #64748b; color: #fff; border-radius: 8px; font-weight: 600; text-decoration: none;">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<h2>Supplier List</h2>
<br>

<div class="form-card" style="max-width: 100%;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="border-bottom: 2px solid #e2e8f0; color: #64748b;">
                <th style="padding: 10px;">ID</th>
                <th style="padding: 10px;">Contact Name</th>
                <th style="padding: 10px;">Company</th>
                <th style="padding: 10px;">Email</th>
                <th style="padding: 10px;">Phone</th>
                <th style="padding: 10px;">Orders</th>
                <th style="padding: 10px;">Total Purchases</th>
                <th style="padding: 10px; text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($suppliers)): ?>
                <tr>
                    <td colspan="8" style="padding: 15px; color: #64748b;">No suppliers added yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($suppliers as $s): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 10px;">#<?php echo $s['id']; ?></td>
                        <td style="padding: 10px; font-weight: 600;"><?php echo htmlspecialchars($s['name']); ?></td>
                        <td style="padding: 10px; color: #38bdf8; font-weight: 600;"><?php echo htmlspecialchars($s['company'] ?? '-'); ?></td>
                        <td style="padding: 10px; color: #64748b;"><?php echo htmlspecialchars($s['email'] ?? '-'); ?></td>
                        <td style="padding: 10px; color: #64748b;"><?php echo htmlspecialchars($s['phone'] ?? '-'); ?></td>
                        <td style="padding: 10px; font-weight: 600; color: #3b82f6;"><?php echo $s['total_orders']; ?></td>
                        <td style="padding: 10px; font-weight: 700; color: #1e293b;">$<?php echo number_format($s['total_purchased'], 2); ?></td>
                        <td style="padding: 10px; text-align: right;">
                            <div class="btn" style="justify-content: flex-end;">
                                <a href="suppliers.php?edit=<?php echo $s['id']; ?>" class="update" style="padding: 6px 12px; border-radius: 6px; text-decoration: none; display: inline-block;">Edit</a>
                                <form method="POST" action="suppliers.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this supplier?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
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
