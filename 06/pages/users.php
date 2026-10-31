<?php
require_once __DIR__ . '/../config/db.php';

// Strict server-side RBAC enforcement: Admin only!
check_role('admin');

$edit_user = null;

// Handle Form Submissions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $pdo !== null) {
    if (!verify_csrf()) {
        set_flash_message('error', 'Security token mismatch. Please try again.');
        header("Location: users.php");
        exit;
    }

    $action = $_POST['action'] ?? '';

    // 1. ADD NEW USER
    if ($action === 'add') {
        $username  = trim($_POST['username'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $role      = $_POST['role'] ?? 'staff';
        $status    = $_POST['status'] ?? 'active';

        // Validate allowed values
        if (!in_array($role, ['admin', 'manager', 'staff'], true)) {
            $role = 'staff';
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        if (empty($username) || empty($password) || empty($full_name) || empty($email)) {
            set_flash_message('error', 'All fields (Username, Full Name, Email, Password) are required.');
        } elseif (strlen($password) < 6) {
            set_flash_message('error', 'Password must be at least 6 characters long.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash_message('error', 'Please provide a valid email address.');
        } else {
            try {
                // Check if username already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    set_flash_message('error', "Username '{$username}' is already in use. Please choose another.");
                } else {
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role, status) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $hashed_password, $full_name, $email, $role, $status]);

                    log_activity($pdo, 'User Created', 'Users', "Created account for '{$username}' ({$role})");
                    set_flash_message('success', "User '{$username}' created successfully!");
                    header("Location: users.php");
                    exit;
                }
            } catch (Exception $e) {
                set_flash_message('error', 'Database error: ' . $e->getMessage());
            }
        }
    }

    // 2. EDIT USER
    elseif ($action === 'edit') {
        $id        = intval($_POST['id'] ?? 0);
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $role      = $_POST['role'] ?? 'staff';
        $status    = $_POST['status'] ?? 'active';
        $password  = $_POST['new_password'] ?? '';

        if (!in_array($role, ['admin', 'manager', 'staff'], true)) $role = 'staff';
        if (!in_array($status, ['active', 'inactive'], true)) $status = 'active';

        // Prevent admin from deactivating or demoting themselves
        if ($id === (int)$_SESSION['user_id']) {
            if ($status !== 'active') {
                set_flash_message('error', 'Safety protection: You cannot deactivate your own admin account.');
                header("Location: users.php");
                exit;
            }
            if ($role !== 'admin') {
                set_flash_message('error', 'Safety protection: You cannot remove admin role from your own account.');
                header("Location: users.php");
                exit;
            }
        }

        if (empty($full_name) || empty($email)) {
            set_flash_message('error', 'Full Name and Email are required.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash_message('error', 'Please provide a valid email address.');
        } else {
            try {
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        set_flash_message('error', 'New password must be at least 6 characters long.');
                        header("Location: users.php?edit=" . $id);
                        exit;
                    }
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, status = ?, password = ? WHERE id = ?");
                    $stmt->execute([$full_name, $email, $role, $status, $hashed_password, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, status = ? WHERE id = ?");
                    $stmt->execute([$full_name, $email, $role, $status, $id]);
                }

                // If editing self, update active session name and email
                if ($id === (int)$_SESSION['user_id']) {
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['email'] = $email;
                }

                log_activity($pdo, 'User Updated', 'Users', "Updated user ID #{$id} ({$full_name})");
                set_flash_message('success', 'User profile updated successfully!');
                header("Location: users.php");
                exit;
            } catch (Exception $e) {
                set_flash_message('error', 'Database error: ' . $e->getMessage());
            }
        }
    }

    // 3. TOGGLE STATUS (Activate / Deactivate)
    elseif ($action === 'toggle_status') {
        $id = intval($_POST['id'] ?? 0);
        if ($id === (int)$_SESSION['user_id']) {
            set_flash_message('error', 'You cannot deactivate your own active account.');
        } else {
            try {
                $stmt = $pdo->prepare("SELECT username, status FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $target = $stmt->fetch();

                if ($target) {
                    $new_status = ($target['status'] === 'active') ? 'inactive' : 'active';
                    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                    $stmt->execute([$new_status, $id]);

                    log_activity($pdo, 'Status Changed', 'Users', "Changed status of '{$target['username']}' to {$new_status}");
                    set_flash_message('success', "Account '{$target['username']}' is now {$new_status}.");
                }
            } catch (Exception $e) {
                set_flash_message('error', 'Database error: ' . $e->getMessage());
            }
        }
        header("Location: users.php");
        exit;
    }

    // 4. DELETE USER
    elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id === (int)$_SESSION['user_id']) {
            set_flash_message('error', 'Safety protection: You cannot delete your own account.');
        } else {
            try {
                $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $target = $stmt->fetch();

                if ($target) {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$id]);

                    log_activity($pdo, 'User Deleted', 'Users', "Deleted user account '{$target['username']}'");
                    set_flash_message('success', "User '{$target['username']}' was deleted successfully.");
                }
            } catch (Exception $e) {
                set_flash_message('error', 'Cannot delete user: ' . $e->getMessage());
            }
        }
        header("Location: users.php");
        exit;
    }
}

// Fetch user for edit mode if requested
if (isset($_GET['edit']) && $pdo !== null) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_user = $stmt->fetch();
}

// Fetch all users
$users = [];
if ($pdo !== null) {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY id ASC");
    $users = $stmt->fetchAll();
}

$page_title = 'Stock Flow - User Management';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-title-row">
    <div>
        <h1>User Management</h1>
        <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">Administer application accounts, role permissions, and access status.</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <button type="button" id="toggleUserFormBtn" class="btn btn-primary btn-sm" onclick="toggleFormCard('userFormCard', 'toggleUserFormBtn')">
            <span>➕ Create New User</span>
        </button>
    </div>
</div>

<!-- Add / Edit User Form Card -->
<div class="form-card collapsible-form-container <?php echo $edit_user ? '' : 'collapsed'; ?>" id="userFormCard" style="max-width: 800px; <?php echo $edit_user ? '' : 'display: none;'; ?>">
    <h2>
        <span><?php echo $edit_user ? '✏️ Edit User Account' : '➕ Create New User Account'; ?></span>
        <?php if ($edit_user): ?>
            <a href="users.php" class="btn btn-secondary btn-sm">Cancel Edit</a>
        <?php endif; ?>
    </h2>
        <?php if ($edit_user): ?>
            <a href="users.php" class="btn btn-secondary btn-sm">Cancel Edit</a>
        <?php endif; ?>
    </h2>

    <form method="POST" action="users.php" class="app-form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="<?php echo $edit_user ? 'edit' : 'add'; ?>">
        <?php if ($edit_user): ?>
            <input type="hidden" name="id" value="<?php echo $edit_user['id']; ?>">
        <?php endif; ?>

        <div class="form-row form-row-2">
            <div class="form-group">
                <label class="form-label">Username</label>
                <?php if ($edit_user): ?>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($edit_user['username']); ?>" disabled style="background: #e2e8f0; cursor: not-allowed;">
                    <small style="color: #64748b; font-size: 11px;">Username cannot be modified once created.</small>
                <?php else: ?>
                    <input type="text" name="username" class="form-control" placeholder="e.g. jsmith" required>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" placeholder="e.g. John Smith" value="<?php echo htmlspecialchars($edit_user['full_name'] ?? ''); ?>" required>
            </div>
        </div>

        <div class="form-row form-row-2">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="e.g. john@stockflow.com" value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <?php echo $edit_user ? 'Reset Password (leave empty to keep current)' : 'Password (min. 6 characters)'; ?>
                </label>
                <input type="password" name="<?php echo $edit_user ? 'new_password' : 'password'; ?>" class="form-control" placeholder="••••••••" <?php echo $edit_user ? '' : 'required minlength="6"'; ?>>
            </div>
        </div>

        <div class="form-row form-row-2">
            <div class="form-group">
                <label class="form-label">System Role</label>
                <select name="role" class="form-control" required <?php echo ($edit_user && $edit_user['id'] === (int)$_SESSION['user_id']) ? 'disabled' : ''; ?>>
                    <option value="staff" <?php echo (($edit_user['role'] ?? 'staff') === 'staff') ? 'selected' : ''; ?>>
                        📦 Staff (Operational: View catalog, sales entry, stock in/out)
                    </option>
                    <option value="manager" <?php echo (($edit_user['role'] ?? '') === 'manager') ? 'selected' : ''; ?>>
                        👔 Manager (Operational + Catalog edit, purchase management, reports)
                    </option>
                    <option value="admin" <?php echo (($edit_user['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>
                        👑 Admin (Full system control, users, settings, and logs)
                    </option>
                </select>
                <?php if ($edit_user && $edit_user['id'] === (int)$_SESSION['user_id']): ?>
                    <input type="hidden" name="role" value="admin">
                    <small style="color: #64748b; font-size: 11px;">You cannot change your own role.</small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label">Account Status</label>
                <select name="status" class="form-control" required <?php echo ($edit_user && $edit_user['id'] === (int)$_SESSION['user_id']) ? 'disabled' : ''; ?>>
                    <option value="active" <?php echo (($edit_user['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active (Can log in)</option>
                    <option value="inactive" <?php echo (($edit_user['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive (Login blocked)</option>
                </select>
                <?php if ($edit_user && $edit_user['id'] === (int)$_SESSION['user_id']): ?>
                    <input type="hidden" name="status" value="active">
                    <small style="color: #64748b; font-size: 11px;">You cannot deactivate your own account.</small>
                <?php endif; ?>
            </div>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 8px;">
            <button type="submit" class="btn btn-primary">
                <?php echo $edit_user ? '💾 Update User Account' : '➕ Create User Account'; ?>
            </button>
            <?php if ($edit_user): ?>
                <a href="users.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Users List Table Card -->
<div class="form-card" style="max-width: 100%;">
    <h2>
        <span>🔐 Registered Users (<?php echo count($users); ?>)</span>
        <button type="button" class="btn-export-csv" onclick="exportTableToCSV('usersTable', 'users_list')">
            📥 Export CSV
        </button>
    </h2>

    <!-- Table Filter Toolbar -->
    <div class="table-filter-bar" data-table="usersTable">
        <div class="table-filter-inputs">
            <div class="filter-input-wrapper">
                <span class="filter-icon">🔍</span>
                <input type="text" class="filter-control filter-search" placeholder="Search ID, username, name, email...">
            </div>

            <select class="filter-control filter-select" data-filter="role">
                <option value="">All Roles</option>
                <option value="admin">Admin</option>
                <option value="manager">Manager</option>
                <option value="staff">Staff</option>
            </select>

            <select class="filter-control filter-select" data-filter="status">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>

            <select class="filter-control filter-sort">
                <option value="id-asc">Sort: ID (1 → N)</option>
                <option value="id-desc">Sort: ID (N → 1)</option>
                <option value="name-asc">Sort: Name (A-Z)</option>
            </select>

            <button type="button" class="btn-filter-reset">↺ Reset</button>
        </div>
        <div class="table-filter-meta">
            <span class="filter-count-badge">Showing <?php echo count($users); ?> of <?php echo count($users); ?></span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="app-table" id="usersTable">
            <thead>
                <tr>
                    <th style="width: 70px;">ID</th>
                    <th>User</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr class="empty-state-row">
                        <td colspan="8" style="padding: 24px; text-align: center; color: var(--text-muted);">No users found in database.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr data-id="<?php echo $u['id']; ?>" 
                            data-name="<?php echo htmlspecialchars($u['full_name']); ?>" 
                            data-role="<?php echo htmlspecialchars(strtolower($u['role'])); ?>" 
                            data-status="<?php echo htmlspecialchars(strtolower($u['status'] ?? 'active')); ?>">
                            <td class="cell-id">#<?php echo $u['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($u['username']); ?></strong>
                                <?php if ($u['id'] === (int)$_SESSION['user_id']): ?>
                                    <span style="font-size: 10px; background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; margin-left: 4px; font-weight: 700;">YOU</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                            <td style="color: var(--text-secondary);"><?php echo htmlspecialchars($u['email']); ?></td>
                            <td>
                                <span class="role-badge role-<?php echo htmlspecialchars($u['role']); ?>">
                                    <?php echo strtoupper(htmlspecialchars($u['role'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (($u['status'] ?? 'active') === 'active'): ?>
                                    <span class="badge badge-success">
                                        <span class="pulse-dot pulse-emerald" style="width:5px;height:5px;"></span> ACTIVE
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-danger">
                                        <span class="pulse-dot pulse-rose" style="width:5px;height:5px;"></span> INACTIVE
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="color: #64748b; font-size: 13px;">
                                <?php echo !empty($u['created_at']) ? date('M d, Y', strtotime($u['created_at'])) : '-'; ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="users.php?edit=<?php echo $u['id']; ?>" class="btn btn-warning btn-sm" title="Edit User">
                                        Edit
                                    </a>

                                    <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                        <!-- Toggle Status Form -->
                                        <form method="POST" action="users.php" style="display: inline;" onsubmit="return confirm('Change status for user <?php echo htmlspecialchars($u['username']); ?>?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                            <?php if (($u['status'] ?? 'active') === 'active'): ?>
                                                <button type="submit" class="btn btn-secondary btn-sm" title="Deactivate Account" style="color: #b45309;">
                                                    Deactivate
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="btn btn-success btn-sm" title="Activate Account">
                                                    Activate
                                                </button>
                                            <?php endif; ?>
                                        </form>

                                        <!-- Delete Form -->
                                        <form method="POST" action="users.php" style="display: inline;" onsubmit="return confirm('Permanently delete user <?php echo htmlspecialchars($u['username']); ?>?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete User">
                                                Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
