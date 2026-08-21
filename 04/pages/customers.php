<?php
$page_title = 'Stock Flow - Customers';
require_once __DIR__ . '/../includes/header.php';

$msg = "";
$edit_cust = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo !== null) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');

        if (empty($name)) {
            $msg = '<div style="color: #ef4444; margin-bottom: 15px;">Customer Name is required.</div>';
        } else {
            try {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, address) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $email, $phone, $address]);
                    $msg = '<div style="color: #10b981; margin-bottom: 15px;">Customer added successfully!</div>';
                } else if ($action === 'edit') {
                    $id = intval($_POST['id']);
                    $stmt = $pdo->prepare("UPDATE customers SET name=?, email=?, phone=?, address=? WHERE id=?");
                    $stmt->execute([$name, $email, $phone, $address, $id]);
                    $msg = '<div style="color: #10b981; margin-bottom: 15px;">Customer updated successfully!</div>';
                }
            } catch (Exception $e) {
                $msg = '<div style="color: #ef4444; margin-bottom: 15px;">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    } else if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM customers WHERE id=?");
            $stmt->execute([$id]);
            $msg = '<div style="color: #10b981; margin-bottom: 15px;">Customer deleted successfully!</div>';
        } catch (Exception $e) {
            $msg = '<div style="color: #ef4444; margin-bottom: 15px;">Error deleting customer: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

if (isset($_GET['edit']) && $pdo !== null) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id=?");
    $stmt->execute([$edit_id]);
    $edit_cust = $stmt->fetch();
}

$customers = [];
if ($pdo !== null) {
    $stmt = $pdo->query("SELECT c.*, COUNT(s.id) AS total_orders, COALESCE(SUM(s.total_amount), 0) AS total_spent 
                         FROM customers c 
                         LEFT JOIN sales s ON c.id = s.customer_id 
                         GROUP BY c.id 
                         ORDER BY c.id DESC");
    $customers = $stmt->fetchAll();
}
?>

<h1>Customer Directory</h1>

<?php echo $msg; ?>

<div class="form-card" style="max-width: 600px;">
    <h2><?php echo $edit_cust ? 'Edit Customer' : 'Add New Customer'; ?></h2>
    <br>
    <form method="POST" action="customers.php" id="todoForm">
        <input type="hidden" name="action" value="<?php echo $edit_cust ? 'edit' : 'add'; ?>">
        <?php if ($edit_cust): ?>
            <input type="hidden" name="id" value="<?php echo $edit_cust['id']; ?>">
        <?php endif; ?>

        <input type="text" name="name" placeholder="Customer Name" value="<?php echo htmlspecialchars($edit_cust['name'] ?? ''); ?>" required>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <input type="email" name="email" placeholder="Email Address" value="<?php echo htmlspecialchars($edit_cust['email'] ?? ''); ?>">
            <input type="text" name="phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($edit_cust['phone'] ?? ''); ?>">
        </div>

        <textarea name="address" placeholder="Address..." rows="2" style="width: 100%; padding: 10px; border-radius: 8px; border: 1.5px solid #e2e8f0; font-family: inherit;"><?php echo htmlspecialchars($edit_cust['address'] ?? ''); ?></textarea>

        <div style="display: flex; gap: 10px;">
            <button type="submit" id="submit"><?php echo $edit_cust ? 'Update Customer' : 'Add Customer'; ?></button>
            <?php if ($edit_cust): ?>
                <a href="customers.php" style="padding: 10px 18px; background: #64748b; color: #fff; border-radius: 8px; font-weight: 600; text-decoration: none;">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<h2>Customer List</h2>
<br>

<div class="form-card" style="max-width: 100%;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="border-bottom: 2px solid #e2e8f0; color: #64748b;">
                <th style="padding: 10px;">ID</th>
                <th style="padding: 10px;">Name</th>
                <th style="padding: 10px;">Email</th>
                <th style="padding: 10px;">Phone</th>
                <th style="padding: 10px;">Orders</th>
                <th style="padding: 10px;">Total Spent</th>
                <th style="padding: 10px; text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="7" style="padding: 15px; color: #64748b;">No customers added yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($customers as $c): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 10px;">#<?php echo $c['id']; ?></td>
                        <td style="padding: 10px; font-weight: 600;"><?php echo htmlspecialchars($c['name']); ?></td>
                        <td style="padding: 10px; color: #64748b;"><?php echo htmlspecialchars($c['email'] ?? '-'); ?></td>
                        <td style="padding: 10px; color: #64748b;"><?php echo htmlspecialchars($c['phone'] ?? '-'); ?></td>
                        <td style="padding: 10px; font-weight: 600; color: #3b82f6;"><?php echo $c['total_orders']; ?></td>
                        <td style="padding: 10px; color: #10b981; font-weight: 700;">$<?php echo number_format($c['total_spent'], 2); ?></td>
                        <td style="padding: 10px; text-align: right;">
                            <div class="btn" style="justify-content: flex-end;">
                                <a href="customers.php?edit=<?php echo $c['id']; ?>" class="update" style="padding: 6px 12px; border-radius: 6px; text-decoration: none; display: inline-block;">Edit</a>
                                <form method="POST" action="customers.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
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
