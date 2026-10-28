<?php
require_once __DIR__ . '/../config/db.php';

// Strict Server-Side RBAC Enforcement: Admin only!
check_role('admin');

$user_id = $_SESSION['user_id'] ?? 1;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $pdo !== null) {
    if (!verify_csrf()) {
        set_flash_message('error', 'Security token mismatch. Please try again.');
        header("Location: settings.php");
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $full_name    = trim($_POST['full_name'] ?? '');
        $email        = trim($_POST['email'] ?? '');
        $new_password = $_POST['new_password'] ?? '';

        if (empty($full_name) || empty($email)) {
            set_flash_message('error', 'Full Name and Email Address are required.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash_message('error', 'Please enter a valid email address.');
        } else {
            try {
                if (!empty($new_password)) {
                    if (strlen($new_password) < 6) {
                        set_flash_message('error', 'New password must be at least 6 characters long.');
                        header("Location: settings.php");
                        exit;
                    }
                    $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, password = ? WHERE id = ?");
                    $stmt->execute([$full_name, $email, $hashed, $user_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
                    $stmt->execute([$full_name, $email, $user_id]);
                }

                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;

                log_activity($pdo, 'Profile Updated', 'Settings', "Admin '{$_SESSION['username']}' updated profile details");
                set_flash_message('success', 'Profile and account credentials updated successfully!');
                header("Location: settings.php");
                exit;
            } catch (Exception $e) {
                set_flash_message('error', 'Database Error: ' . $e->getMessage());
            }
        }
    }
}

// Fetch current user details
$user = null;
$table_counts = [];
$recent_activities = [];

if ($pdo !== null) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        // Count system entities
        $counts_queries = [
            'Products'   => "SELECT COUNT(*) FROM products",
            'Categories' => "SELECT COUNT(*) FROM categories",
            'Customers'  => "SELECT COUNT(*) FROM customers",
            'Suppliers'  => "SELECT COUNT(*) FROM suppliers",
            'Sales'      => "SELECT COUNT(*) FROM sales",
            'Purchases'  => "SELECT COUNT(*) FROM purchases",
            'Users'      => "SELECT COUNT(*) FROM users",
            'Audit Logs' => "SELECT COUNT(*) FROM inventory_logs"
        ];
        foreach ($counts_queries as $label => $q) {
            $table_counts[$label] = (int)$pdo->query($q)->fetchColumn();
        }

        // Fetch recent system activity logs
        $stmt = $pdo->query("SELECT a.*, u.username, u.role 
                             FROM activity_logs a 
                             LEFT JOIN users u ON a.user_id = u.id 
                             ORDER BY a.id DESC LIMIT 10");
        $recent_activities = $stmt->fetchAll();
    } catch (Exception $e) {
        // continue gracefully
    }
}

$page_title = 'Stock Flow - System Settings';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-title-row">
    <div>
        <h1>System Administration & Settings</h1>
        <p style="color: #64748b; font-size: 14px; margin-top: 4px;">Maintain master administrator profile and inspect system diagnostics.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 24px; margin-bottom: 24px;">
    <!-- Profile Settings Card -->
    <div class="form-card" style="margin-bottom: 0;">
        <h2>👤 Administrator Profile</h2>

        <form method="POST" action="settings.php" class="app-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="update_profile">

            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username'] ?? $_SESSION['username'] ?? 'admin'); ?>" disabled style="background: #e2e8f0; cursor: not-allowed;">
                <small style="color: #64748b; font-size: 11px;">System login handle is permanent.</small>
            </div>

            <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name'] ?? $_SESSION['full_name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Email Address *</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? $_SESSION['email'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Change Password (leave blank to keep current)</label>
                <input type="password" name="new_password" class="form-control" placeholder="Enter new password (min. 6 chars)..." minlength="6">
            </div>

            <button type="submit" class="btn btn-primary" style="align-self: flex-start;">
                💾 Save Profile Changes
            </button>
        </form>
    </div>

    <!-- System Diagnostics Card -->
    <div class="form-card" style="margin-bottom: 0;">
        <h2>🖥️ System Diagnostics</h2>

        <div style="display: flex; flex-direction: column; gap: 12px; font-size: 14px;">
            <div style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9;">
                <span style="color: #64748b;">Application Version:</span>
                <strong>Stock Flow 2.0 (RBAC Edition)</strong>
            </div>

            <div style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9;">
                <span style="color: #64748b;">PHP Runtime:</span>
                <strong><?php echo phpversion(); ?></strong>
            </div>

            <div style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9;">
                <span style="color: #64748b;">Database Connection:</span>
                <div>
                    <?php if ($pdo !== null): ?>
                        <span class="badge badge-success">CONNECTED (Port <?php echo htmlspecialchars($active_db_port ?? '3000'); ?>)</span>
                    <?php else: ?>
                        <span class="badge badge-danger">DISCONNECTED</span>
                    <?php endif; ?>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9;">
                <span style="color: #64748b;">Active Database Name:</span>
                <code>stock_flow</code>
            </div>

            <div style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9;">
                <span style="color: #64748b;">Server OS / Interface:</span>
                <span><?php echo htmlspecialchars(PHP_OS . ' / ' . php_sapi_name()); ?></span>
            </div>

            <div style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9;">
                <span style="color: #64748b;">Security Architecture:</span>
                <span class="badge badge-info">BCRYPT + CSRF Guard</span>
            </div>
        </div>

        <h3 style="font-size: 14px; color: #475569; margin-top: 18px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">
            Database Record Metrics
        </h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
            <?php foreach ($table_counts as $lbl => $cnt): ?>
                <div style="background: #f8fafc; padding: 8px 12px; border-radius: 6px; display: flex; justify-content: space-between; font-size: 13px; border: 1px solid #e2e8f0;">
                    <span style="color: #64748b;"><?php echo $lbl; ?>:</span>
                    <strong><?php echo $cnt; ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Recent Activity Audit Logs -->
<div class="form-card" style="max-width: 100%;">
    <h2>🛡️ Recent System Activity Log (Audit Trail)</h2>
    <div class="table-responsive">
        <table class="app-table">
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Details</th>
                    <th>IP Address</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_activities)): ?>
                    <tr>
                        <td colspan="7" style="padding: 20px; text-align: center; color: #64748b;">No security activity logged yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_activities as $act): ?>
                        <tr>
                            <td>#<?php echo $act['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($act['username'] ?? 'System'); ?></strong>
                                <?php if (!empty($act['role'])): ?>
                                    <span class="role-badge role-<?php echo htmlspecialchars($act['role']); ?>" style="margin-left: 4px;">
                                        <?php echo strtoupper(htmlspecialchars($act['role'])); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($act['action']); ?></span></td>
                            <td style="color: #64748b; font-weight: 600;"><?php echo htmlspecialchars($act['module']); ?></td>
                            <td style="color: #334155; font-size: 13px;"><?php echo htmlspecialchars($act['description']); ?></td>
                            <td style="color: #64748b; font-size: 12px;"><?php echo htmlspecialchars($act['ip_address'] ?? '127.0.0.1'); ?></td>
                            <td style="color: #64748b; font-size: 12px;"><?php echo date('M d, Y H:i:s', strtotime($act['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
