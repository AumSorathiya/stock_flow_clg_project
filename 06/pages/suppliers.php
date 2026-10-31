<?php
require_once __DIR__ . '/../config/db.php';
check_login();

$can_modify = has_role(['admin', 'manager']);
$edit_sup = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $pdo !== null) {
    if (!verify_csrf()) {
        set_flash_message('error', 'Security token mismatch. Please try again.');
        header("Location: suppliers.php");
        exit;
    }

    $action = $_POST['action'] ?? '';

    if (!$can_modify) {
        set_flash_message('error', 'Permission Denied: Staff accounts have read-only access to supplier records.');
        header("Location: suppliers.php");
        exit;
    }

    if ($action === 'add' || $action === 'edit') {
        $name    = trim($_POST['name'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($name)) {
            set_flash_message('error', 'Supplier Contact Person Name is required.');
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash_message('error', 'Please enter a valid email address.');
        } else {
            try {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("INSERT INTO suppliers (name, company, email, phone, address) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $company, $email, $phone, $address]);

                    log_activity($pdo, 'Supplier Added', 'Suppliers', "Added vendor '{$company}' ({$name})");
                    set_flash_message('success', "Supplier '{$name}' registered successfully!");
                    header("Location: suppliers.php");
                    exit;
                } elseif ($action === 'edit') {
                    $id = intval($_POST['id'] ?? 0);
                    $stmt = $pdo->prepare("UPDATE suppliers SET name = ?, company = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                    $stmt->execute([$name, $company, $email, $phone, $address, $id]);

                    log_activity($pdo, 'Supplier Updated', 'Suppliers', "Updated vendor ID #{$id} ({$name})");
                    set_flash_message('success', "Supplier '{$name}' updated successfully!");
                    header("Location: suppliers.php");
                    exit;
                }
            } catch (Exception $e) {
                set_flash_message('error', 'Database Error: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("SELECT name, company FROM suppliers WHERE id = ?");
            $stmt->execute([$id]);
            $sup = $stmt->fetch();

            if ($sup) {
                $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
                $stmt->execute([$id]);

                log_activity($pdo, 'Supplier Deleted', 'Suppliers', "Deleted vendor '{$sup['name']}'");
                set_flash_message('success', "Supplier record was removed.");
            }
        } catch (Exception $e) {
            set_flash_message('error', 'Error deleting supplier: ' . $e->getMessage());
        }
        header("Location: suppliers.php");
        exit;
    }
}

if (isset($_GET['edit']) && $pdo !== null && $can_modify) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_sup = $stmt->fetch();
}

$suppliers = [];
if ($pdo !== null) {
    $stmt = $pdo->query("SELECT s.*, COUNT(pu.id) AS total_orders, COALESCE(SUM(pu.total_amount), 0) AS total_purchased 
                         FROM suppliers s 
                         LEFT JOIN purchases pu ON s.id = pu.supplier_id 
                         GROUP BY s.id, s.name, s.company, s.email, s.phone, s.address, s.created_at 
                         ORDER BY s.id ASC");
    $suppliers = $stmt->fetchAll();
}

$page_title = 'Stock Flow - Supplier Directory';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-title-row">
    <div>
        <h1>Supplier & Vendor Directory</h1>
        <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">Maintain vendor trade accounts, points of contact, and procurement history.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <?php if ($can_modify): ?>
            <button type="button" id="toggleSupFormBtn" class="btn btn-primary btn-sm" onclick="toggleFormCard('supplierFormCard', 'toggleSupFormBtn')">
                <span>➕ Register Supplier</span>
            </button>
        <?php else: ?>
            <span class="badge badge-secondary" style="font-size: 12px; padding: 6px 12px;">Staff View: Read-Only</span>
        <?php endif; ?>
    </div>
</div>

<?php if ($can_modify): ?>
<div class="form-card collapsible-form-container <?php echo $edit_sup ? '' : 'collapsed'; ?>" id="supplierFormCard" style="max-width: 750px; <?php echo $edit_sup ? '' : 'display: none;'; ?>">
    <h2>
        <span><?php echo $edit_sup ? '✏️ Edit Supplier Record' : '➕ Register Inbound Supplier'; ?></span>
        <?php if ($edit_sup): ?>
            <a href="suppliers.php" class="btn btn-secondary btn-sm">Cancel Edit</a>
        <?php endif; ?>
    </h2>

    <form method="POST" action="suppliers.php" class="app-form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="<?php echo $edit_sup ? 'edit' : 'add'; ?>">
        <?php if ($edit_sup): ?>
            <input type="hidden" name="id" value="<?php echo $edit_sup['id']; ?>">
        <?php endif; ?>

        <div class="form-row form-row-2">
            <div class="form-group">
                <label class="form-label">Contact Person *</label>
                <input type="text" name="name" class="form-control" placeholder="e.g. Sarah Jenkins" value="<?php echo htmlspecialchars($edit_sup['name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Company / Entity Name</label>
                <input type="text" name="company" class="form-control" placeholder="e.g. Apex Electronics Ltd" value="<?php echo htmlspecialchars($edit_sup['company'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-row form-row-2">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="e.g. sales@vendor.com" value="<?php echo htmlspecialchars($edit_sup['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" placeholder="e.g. 1-800-555-0100" value="<?php echo htmlspecialchars($edit_sup['phone'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Office / Warehouse Address</label>
            <input type="text" name="address" class="form-control" placeholder="e.g. 50 Industrial Way, Columbus, OH" value="<?php echo htmlspecialchars($edit_sup['address'] ?? ''); ?>">
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">
                <?php echo $edit_sup ? '💾 Update Supplier Record' : '➕ Register Supplier'; ?>
            </button>
            <?php if ($edit_sup): ?>
                <a href="suppliers.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="form-card" style="max-width: 100%;">
    <h2>
        <span>🏬 Registered Vendors (<?php echo count($suppliers); ?>)</span>
        <button type="button" class="btn-export-csv" onclick="exportTableToCSV('suppliersTable', 'suppliers_directory')">
            📥 Export CSV
        </button>
    </h2>

    <!-- Table Filter Toolbar -->
    <div class="table-filter-bar" data-table="suppliersTable">
        <div class="table-filter-inputs">
            <div class="filter-input-wrapper">
                <span class="filter-icon">🔍</span>
                <input type="text" class="filter-control filter-search" placeholder="Search ID, contact, company, email, phone...">
            </div>

            <select class="filter-control filter-select" data-filter="hasorders">
                <option value="">All Vendor Activity</option>
                <option value="yes">Has Orders (> 0)</option>
                <option value="no">New / No Orders (0)</option>
            </select>

            <select class="filter-control filter-sort">
                <option value="id-asc">Sort: ID (1 → N)</option>
                <option value="id-desc">Sort: ID (N → 1)</option>
                <option value="name-asc">Sort: Contact Name (A-Z)</option>
                <option value="amount-desc">Sort: Procurement Spend (High → Low)</option>
                <option value="stock-desc">Sort: Orders Count (High → Low)</option>
            </select>

            <button type="button" class="btn-filter-reset">↺ Reset</button>
        </div>
        <div class="table-filter-meta">
            <span class="filter-count-badge">Showing <?php echo count($suppliers); ?> of <?php echo count($suppliers); ?></span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="app-table" id="suppliersTable">
            <thead>
                <tr>
                    <th style="width: 70px;">ID</th>
                    <th>Contact Person</th>
                    <th>Company</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Orders</th>
                    <th>Procurement Spend</th>
                    <?php if ($can_modify): ?>
                        <th style="text-align: right;">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($suppliers)): ?>
                    <tr class="empty-state-row">
                        <td colspan="<?php echo $can_modify ? '8' : '7'; ?>" style="padding: 24px; text-align: center; color: var(--text-muted);">No suppliers registered yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($suppliers as $s): ?>
                        <tr data-id="<?php echo $s['id']; ?>" 
                            data-name="<?php echo htmlspecialchars($s['name']); ?>" 
                            data-company="<?php echo htmlspecialchars($s['company'] ?? ''); ?>" 
                            data-hasorders="<?php echo ($s['total_orders'] > 0 ? 'yes' : 'no'); ?>" 
                            data-stock="<?php echo $s['total_orders']; ?>" 
                            data-amount="<?php echo $s['total_purchased']; ?>">
                            <td class="cell-id">#<?php echo $s['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($s['name']); ?></strong></td>
                            <td>
                                <strong style="color: #0284c7;"><?php echo htmlspecialchars($s['company'] ?? '-'); ?></strong>
                            </td>
                            <td style="color: var(--text-muted);"><?php echo htmlspecialchars($s['email'] ?? '-'); ?></td>
                            <td style="color: var(--text-muted);"><?php echo htmlspecialchars($s['phone'] ?? '-'); ?></td>
                            <td>
                                <span class="badge badge-info"><?php echo $s['total_orders']; ?> orders</span>
                            </td>
                            <td class="cell-price" style="color: #0284c7;">$<?php echo number_format($s['total_purchased'], 2); ?></td>
                            <?php if ($can_modify): ?>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <a href="suppliers.php?edit=<?php echo $s['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <form method="POST" action="suppliers.php" style="display: inline;" onsubmit="return confirm('Delete supplier <?php echo htmlspecialchars(addslashes($s['name'])); ?>?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
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
