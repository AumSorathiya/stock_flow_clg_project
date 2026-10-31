<?php
$page_title = 'Stock Flow - System Settings';
require_once __DIR__ . '/../includes/header.php';

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo !== null) {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $full_name = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $new_password = $_POST['new_password'] ?? '';

        try {
            $user_id = $_SESSION['user_id'] ?? 1;

            if (!empty($new_password)) {
                $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=?, password=? WHERE id=?");
                $stmt->execute([$full_name, $email, $hashed, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=? WHERE id=?");
                $stmt->execute([$full_name, $email, $user_id]);
            }

            $_SESSION['full_name'] = $full_name;
            $msg = '<div style="color: #10b981; margin-bottom: 15px;">Profile updated successfully!</div>';
        } catch (Exception $e) {
            $msg = '<div style="color: #ef4444; margin-bottom: 15px;">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// Fetch current user details
$user = null;
if ($pdo !== null) {
    $user_id = $_SESSION['user_id'] ?? 1;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
}
?>

<h1>Settings</h1>

<?php echo $msg; ?>

<div class="form-card" style="max-width: 600px;">
    <h2>Account Profile Settings</h2>
    <br>
    <form method="POST" action="settings.php" id="todoForm">
        <input type="hidden" name="action" value="update_profile">

        <label style="font-size: 13px; font-weight: 600; color: #64748b;">Username</label>
        <input type="text" value="<?php echo htmlspecialchars($_SESSION['username'] ?? 'admin'); ?>" disabled style="background: #e2e8f0;">

        <label style="font-size: 13px; font-weight: 600; color: #64748b;">Full Name</label>
        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? $_SESSION['full_name'] ?? 'System Administrator'); ?>" required>

        <label style="font-size: 13px; font-weight: 600; color: #64748b;">Email Address</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? 'admin@stockflow.com'); ?>" required>

        <label style="font-size: 13px; font-weight: 600; color: #64748b;">New Password (leave blank to keep current password)</label>
        <input type="password" name="new_password" placeholder="Enter new password...">

        <button type="submit" id="submit">Save Changes</button>
    </form>
</div>

<div class="form-card" style="max-width: 600px;">
    <h2>System Details</h2>
    <br>
    <ul style="list-style: none; line-height: 2;">
        <li><strong>Application Name:</strong> Stock Flow Inventory Management System</li>
        <li><strong>Database Driver:</strong> MySQL PDO</li>
        <li><strong>Database Status:</strong> <?php echo ($pdo !== null) ? '<span style="color: #10b981; font-weight: 700;">Connected</span>' : '<span style="color: #ef4444; font-weight: 700;">Disconnected</span>'; ?></li>
        <li><strong>Database Name:</strong> <code>stock_flow</code></li>
        <li><strong>SQL Schema File:</strong> <code>db/schema.sql</code></li>
        <li><strong>SQL Seed Data File:</strong> <code>db/seed.sql</code></li>
    </ul>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
