<?php
require_once __DIR__ . '/config/db.php';

$error = "";

if (is_logged_in()) {
    header("Location: pages/dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = sanitize($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Pls enter the username and password";
    } else {
        if ($pdo !== null) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'] ?? $user['username'];
                    header("Location: pages/dashboard.php");
                    exit;
                } else {
                    $error = "Invalid username or password";
                }
            } catch (Exception $e) {
                // Fallback check if DB query error
                if ($username === "admin" && $password === "admin") {
                    $_SESSION['user_id'] = 1;
                    $_SESSION['username'] = "admin";
                    header("Location: pages/dashboard.php");
                    exit;
                } else {
                    $error = "Database authentication error: " . $e->getMessage();
                }
            }
        } else {
            // Default fallback if database connection is pending setup
            if ($username === "admin" && $password === "admin") {
                $_SESSION['user_id'] = 1;
                $_SESSION['username'] = "admin";
                header("Location: pages/dashboard.php");
                exit;
            } else {
                $error = "Database connection error. Use admin / admin fallback.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Flow - Login</title>
    <link rel="stylesheet" type="text/css" href="style/loginstyle.css">
</head>
<body>
    <div id="header">
        <h1>📊 Stock Flow</h1>
        <p>Welcome to the Stock Flow Dashboard!</p>
        <div class="login-form">
            <h2>Login</h2>
            <?php if (!empty($error)): ?>
                <div style="color: #ef4444; background: #fee2e2; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="index.php" class="loginForm">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" value="login">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
