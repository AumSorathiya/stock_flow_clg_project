<?php
require_once __DIR__ . '/../config/db.php';
check_login();

$can_manage = has_role(['admin', 'manager']);
$edit_cust = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $pdo !== null) {
    if (!verify_csrf()) {
        set_flash_message('error', 'Security token mismatch. Please try again.');
        header("Location: customers.php");
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        if ($action === 'edit' && !$can_manage) {
            set_flash_message('error', 'Permission Denied: Only Managers and Administrators can modify existing customer profiles.');
            header("Location: customers.php");
            exit;
        }

        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($name)) {
            set_flash_message('error', 'Customer Full Name is required.');
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash_message('error', 'Please provide a valid email address.');
        } else {
            try {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, address) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $email, $phone, $address]);

                    log_activity($pdo, 'Customer Added', 'Customers', "Registered customer '{$name}'");
                    set_flash_message('success', "Customer '{$name}' registered successfully!");
                    header("Location: customers.php");
                    exit;
                } elseif ($action === 'edit') {
                    $id = intval($_POST['id'] ?? 0);
                    $stmt = $pdo->prepare("UPDATE customers SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $phone, $address, $id]);

                    log_activity($pdo, 'Customer Updated', 'Customers', "Updated customer #{$id} ({$name})");
                    set_flash_message('success', "Customer '{$name}' updated successfully!");
                    header("Location: customers.php");
                    exit;
                }
            } catch (Exception $e) {
                set_flash_message('error', 'Database Error: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'delete') {
        if (!$can_manage) {
            set_flash_message('error', 'Permission Denied: Staff accounts cannot delete customers.');
            header("Location: customers.php");
            exit;
        }

        $id = intval($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("SELECT name FROM customers WHERE id = ?");
            $stmt->execute([$id]);
            $cust = $stmt->fetch();

            if ($cust) {
                $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
                $stmt->execute([$id]);

                log_activity($pdo, 'Customer Deleted', 'Customers', "Deleted customer '{$cust['name']}'");
                set_flash_message('success', "Customer '{$cust['name']}' was removed.");
            }
        } catch (Exception $e) {
            set_flash_message('error', 'Error deleting customer: ' . $e->getMessage());
        }
        header("Location: customers.php");
        exit;
    }
}

if (isset($_GET['edit']) && $pdo !== null && $can_manage) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
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

$page_title = 'Stock Flow - Customer Directory';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-title-row">
    <div>
        <h1>Customer Directory</h1>
        <p style="color: #64748b; font-size: 14px; margin-top: 4px;">Maintain client contact records and monitor cumulative sales activity.</p>
    </div>
</div>

<!-- Add / Edit Customer Form Card -->
<div class="form-card" style="max-width: 750px;">
    <h2>
        <span><?php echo $edit_cust ? '✏️ Edit Customer Profile' : '➕ Register New Customer'; ?></span>
        <?php if ($edit_cust): ?>
            <a href="customers.php" class="btn btn-secondary btn-sm">Cancel Edit</a>
        <?php endif; ?>
    </h2>

    <form method="POST" action="customers.php" class="app-form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="<?php echo $edit_cust ? 'edit' : 'add'; ?>">
        <?php if ($edit_cust): ?>
            <input type="hidden" name="id" value="<?php echo $edit_cust['id']; ?>">
        <?php endif; ?>

        <div class="form-row form-row-2">
            <div class="form-group">
                <label class="form-label">Full Name / Business *</label>
                <input type="text" name="name" class="form-control" placeholder="e.g. John Smith or Acme Corp" value="<?php echo htmlspecialchars($edit_cust['name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="e.g. john@example.com" value="<?php echo htmlspecialchars($edit_cust['email'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-row form-row-2">
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" placeholder="e.g. (555) 019-2831" value="<?php echo htmlspecialchars($edit_cust['phone'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Billing / Delivery Address</label>
                <input type="text" name="address" class="form-control" placeholder="e.g. 123 Main St, New York, NY" value="<?php echo htmlspecialchars($edit_cust['address'] ?? ''); ?>">
            </div>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">
                <?php echo $edit_cust ? '💾 Save Customer Changes' : '➕ Register Customer'; ?>
            </button>
            <?php if ($edit_cust): ?>
                <a href="customers.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Customer List Table Card -->
<div class="form-card" style="max-width: 100%;">
    <h2>Registered Customers (<?php echo count($customers); ?>)</h2>
    <div class="table-responsive">
        <table class="app-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Orders</th>
                    <th>Total Spent</th>
                    <?php if ($can_manage): ?>
                        <th style="text-align: right;">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="<?php echo $can_manage ? '8' : '7'; ?>" style="padding: 20px; text-align: center; color: #64748b;">No customers recorded yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                        <tr>
                            <td>#<?php echo $c['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($c['name']); ?></strong></td>
                            <td style="color: #64748b;"><?php echo htmlspecialchars($c['email'] ?? '-'); ?></td>
                            <td style="color: #64748b;"><?php echo htmlspecialchars($c['phone'] ?? '-'); ?></td>
                            <td style="color: #64748b; font-size: 13px;"><?php echo htmlspecialchars($c['address'] ?? '-'); ?></td>
                            <td>
                                <span class="badge badge-info"><?php echo $c['total_orders']; ?> orders</span>
                            </td>
                            <td style="color: #059669; font-weight: 700;">$<?php echo number_format($c['total_spent'], 2); ?></td>
                            <?php if ($can_manage): ?>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <a href="customers.php?edit=<?php echo $c['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <form method="POST" action="customers.php" style="display: inline;" onsubmit="return confirm('Delete customer <?php echo htmlspecialchars(addslashes($c['name'])); ?>?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
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
