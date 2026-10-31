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
    <title>Stock Flow - Enterprise Sign In</title>
    <!-- Instant Zero-Flicker Theme & Cyber Accent Initialization -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('sf_theme') || 'dark';
            const savedAccent = localStorage.getItem('sf_accent') || 'indigo';
            document.documentElement.setAttribute('data-theme', savedTheme);
            document.documentElement.setAttribute('data-accent', savedAccent);
        })();
    </script>
    <link rel="stylesheet" type="text/css" href="style/loginstyle.css">
</head>
<body>
    <!-- Top Corner Theme & Cyber Accent Studio -->
    <div class="auth-top-actions">
        <div class="auth-accent-pills">
            <button type="button" class="auth-accent-dot dot-indigo" data-accent="indigo" onclick="setAccentTheme('indigo')" title="Indigo Neon Accent"></button>
            <button type="button" class="auth-accent-dot dot-emerald" data-accent="emerald" onclick="setAccentTheme('emerald')" title="Matrix Emerald Accent"></button>
            <button type="button" class="auth-accent-dot dot-sunset" data-accent="sunset" onclick="setAccentTheme('sunset')" title="Cyber Sunset Accent"></button>
            <button type="button" class="auth-accent-dot dot-nordic" data-accent="nordic" onclick="setAccentTheme('nordic')" title="Nordic Ice Accent"></button>
        </div>
        <button type="button" class="theme-toggle-btn" id="themeToggleBtn" onclick="toggleTheme()" title="Toggle Dark / Light Theme" aria-label="Toggle theme">
            <span id="themeToggleIcon">🌙</span>
            <span id="themeToggleText">Dark</span>
        </button>
    </div>

    <div id="header">
        <div class="brand-badge-top">
            <span>⚡ NextGen Enterprise Suite</span>
        </div>
        <h1>📊 Stock Flow</h1>
        <p>Intelligent Inventory Management & Financial Telemetry</p>

        <div class="login-form">
            <h2>Sign In to Workspace</h2>

            <?php echo render_flash(); ?>

            <?php if (!empty($error)): ?>
                <div style="color: #991b1b; background: #fee2e2; border: 1px solid #fecaca; padding: 12px 14px; border-radius: 10px; margin-bottom: 20px; font-size: 13.5px; line-height: 1.4; display: flex; align-items: center; gap: 8px;">
                    <span>⚠️</span>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php" class="loginForm">
                <div class="form-group-login">
                    <label for="usernameInput">Username or Staff ID</label>
                    <div class="input-with-icon">
                        <span class="field-icon">👤</span>
                        <input type="text" id="usernameInput" name="username" placeholder="e.g. admin, manager, staff" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required autofocus autocomplete="username">
                    </div>
                </div>

                <div class="form-group-login">
                    <label for="passwordInput">Secret Key / Password</label>
                    <div class="input-with-icon">
                        <span class="field-icon">🔒</span>
                        <input type="password" id="passwordInput" name="password" placeholder="••••••••" required autocomplete="current-password">
                        <button type="button" class="password-toggle-btn" id="pwdToggleBtn" onclick="togglePasswordVisibility()" title="Show/Hide Password" aria-label="Toggle password visibility">
                            👁️
                        </button>
                    </div>
                </div>

                <button type="submit">
                    <span>Sign In to System</span>
                    <span>→</span>
                </button>
            </form>

            <!-- Quick Demo Credentials Box for Fast Evaluation -->
            <div class="demo-accounts-box">
                <p>⚡ Quick Demo Accounts (Click to Fill)</p>
                <div class="demo-chips-grid">
                    <button type="button" class="demo-chip-btn admin-chip" onclick="fillDemo('admin', 'admin123')">
                        👑 Admin
                    </button>
                    <button type="button" class="demo-chip-btn manager-chip" onclick="fillDemo('manager', 'manager123')">
                        👔 Manager
                    </button>
                    <button type="button" class="demo-chip-btn staff-chip" onclick="fillDemo('staff', 'staff123')">
                        📦 Staff
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Telemetry Status at Bottom -->
    <div class="auth-footer-status">
        <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #10b981; box-shadow: 0 0 8px #10b981;"></span>
        <span>All Systems Operational • MySQL Port <?php echo htmlspecialchars($active_db_port ?? '3000'); ?> Active</span>
    </div>

    <script>
    let audioCtx = null;
    function playSound(type) {
        try {
            if (!audioCtx && (window.AudioContext || window.webkitAudioContext)) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (!audioCtx) return;
            if (audioCtx.state === 'suspended') audioCtx.resume();
            const now = audioCtx.currentTime;
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);

            if (type === 'click') {
                osc.type = 'sine';
                osc.frequency.setValueAtTime(650, now);
                osc.frequency.exponentialRampToValueAtTime(320, now + 0.04);
                gain.gain.setValueAtTime(0.04, now);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.04);
                osc.start(now);
                osc.stop(now + 0.04);
            } else if (type === 'switch') {
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(440, now);
                osc.frequency.exponentialRampToValueAtTime(880, now + 0.06);
                gain.gain.setValueAtTime(0.05, now);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.06);
                osc.start(now);
                osc.stop(now + 0.06);
            }
        } catch (e) {}
    }

    function initTheme() {
        const savedTheme = localStorage.getItem('sf_theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
        updateThemeToggleUI(savedTheme);
        initAccent();
        initCardSpotlight();
    }

    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('sf_theme', newTheme);
        updateThemeToggleUI(newTheme);
        playSound('switch');
    }

    function updateThemeToggleUI(theme) {
        const icon = document.getElementById('themeToggleIcon');
        const text = document.getElementById('themeToggleText');
        if (icon && text) {
            if (theme === 'light') {
                icon.textContent = '☀️';
                text.textContent = 'Light';
            } else {
                icon.textContent = '🌙';
                text.textContent = 'Dark';
            }
        }
    }

    function initAccent() {
        const savedAccent = localStorage.getItem('sf_accent') || 'indigo';
        document.documentElement.setAttribute('data-accent', savedAccent);
        updateAccentUI(savedAccent);
    }

    function setAccentTheme(accent) {
        document.documentElement.setAttribute('data-accent', accent);
        localStorage.setItem('sf_accent', accent);
        updateAccentUI(accent);
        playSound('switch');
    }

    function updateAccentUI(accent) {
        document.querySelectorAll('.auth-accent-dot').forEach(dot => {
            if (dot.getAttribute('data-accent') === accent) {
                dot.classList.add('active');
            } else {
                dot.classList.remove('active');
            }
        });
    }

    function initCardSpotlight() {
        document.addEventListener('mousemove', (e) => {
            const card = document.getElementById('header');
            if (card) {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                card.style.setProperty('--mouse-x', `${x}px`);
                card.style.setProperty('--mouse-y', `${y}px`);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', initTheme);

    function fillDemo(user, pass) {
        playSound('click');
        const u = document.getElementById('usernameInput');
        const p = document.getElementById('passwordInput');
        u.value = user;
        p.value = pass;
        u.focus();
        
        // Visual flash feedback
        u.style.borderColor = 'var(--primary, #4f46e5)';
        p.style.borderColor = 'var(--primary, #4f46e5)';
        setTimeout(() => {
            u.style.borderColor = '';
            p.style.borderColor = '';
        }, 600);
    }

    function togglePasswordVisibility() {
        playSound('click');
        const p = document.getElementById('passwordInput');
        const btn = document.getElementById('pwdToggleBtn');
        if (p.type === 'password') {
            p.type = 'text';
            btn.innerText = '🙈';
        } else {
            p.type = 'password';
            btn.innerText = '👁️';
        }
    }
    </script>
</body>
</html>
