<?php
require_once __DIR__ . '/config/db.php';

$error = "";

// If already authenticated, redirect straight to dashboard
if (is_logged_in()) {
    header("Location: pages/dashboard.php");
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        if ($pdo !== null) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user) {
                    // Check account active status
                    if (isset($user['status']) && $user['status'] === 'inactive') {
                        $error = "This account is inactive. Please contact your system administrator.";
                    } else {
                        // Support standard password_verify and fallback plain check
                        $pwd_matches = password_verify($password, $user['password']) || ($password === $user['password']);
                        
                        if ($pwd_matches) {
                            // If password was legacy plain text, automatically upgrade to bcrypt hash
                            if (!password_verify($password, $user['password'])) {
                                $new_hash = password_hash($password, PASSWORD_BCRYPT);
                                $up_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                                $up_stmt->execute([$new_hash, $user['id']]);
                            }

                            // Regenerate session ID to prevent session fixation attacks
                            session_regenerate_id(true);

                            $_SESSION['user_id']   = (int)$user['id'];
                            $_SESSION['username']  = $user['username'];
                            $_SESSION['full_name'] = $user['full_name'] ?? $user['username'];
                            $_SESSION['role']      = $user['role'] ?? 'staff';
                            $_SESSION['email']     = $user['email'] ?? '';

                            log_activity($pdo, 'Login', 'Auth', "User '{$user['username']}' logged in as " . strtoupper($user['role']));

                            header("Location: pages/dashboard.php");
                            exit;
                        } else {
                            $error = "Invalid username or password.";
                        }
                    }
                } else {
                    $error = "Invalid username or password.";
                }
            } catch (Exception $e) {
                $error = "Database query error: " . $e->getMessage();
            }
        } else {
            $error = "Database connection error. Please ensure MySQL is running in XAMPP (Port 3306 or 3000).";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Flow - Sign In</title>
    <link rel="stylesheet" type="text/css" href="style/loginstyle.css">
</head>
<body>
    <div id="header">
        <h1>📊 Stock Flow</h1>
        <p>Enterprise Inventory Management System</p>

        <div class="login-form">
            <h2>Sign In</h2>

            <?php echo render_flash(); ?>

            <?php if (!empty($error)): ?>
                <div style="color: #991b1b; background: #fee2e2; border: 1px solid #fecaca; padding: 12px 14px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; line-height: 1.4;">
                    ⚠️ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php" class="loginForm">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Username</label>
                    <input type="text" id="usernameInput" name="username" placeholder="e.g. admin, manager, staff" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required autofocus>
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;">Password</label>
                    <input type="password" id="passwordInput" name="password" placeholder="••••••••" required>
                </div>

                <button type="submit">Sign In</button>
            </form>

            <!-- Quick Demo Credentials Box for Evaluation -->
            <div style="margin-top: 24px; padding: 14px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px;">
                <p style="font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 8px; text-align: center;">
                    ⚡ Quick Demo Accounts (Click to Fill)
                </p>
                <div style="display: flex; gap: 6px; justify-content: center; flex-wrap: wrap;">
                    <button type="button" onclick="fillDemo('admin', 'admin123')" style="margin: 0; padding: 6px 10px; font-size: 12px; background: #7c3aed; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                        👑 Admin
                    </button>
                    <button type="button" onclick="fillDemo('manager', 'manager123')" style="margin: 0; padding: 6px 10px; font-size: 12px; background: #0284c7; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                        👔 Manager
                    </button>
                    <button type="button" onclick="fillDemo('staff', 'staff123')" style="margin: 0; padding: 6px 10px; font-size: 12px; background: #059669; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                        📦 Staff
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function fillDemo(user, pass) {
        document.getElementById('usernameInput').value = user;
        document.getElementById('passwordInput').value = pass;
    }
    </script>
</body>
</html>
