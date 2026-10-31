<?php
require_once __DIR__ . '/../config/db.php';
check_login();

// Base URL detection for stylesheet & navigation links
$is_pages_dir = strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false;
$base_prefix = $is_pages_dir ? '../' : './';
$pages_prefix = $is_pages_dir ? '' : 'pages/';

$page_title = isset($page_title) ? $page_title : 'Stock Flow';
$user_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
$user_role = current_user_role();
$active_port = $active_db_port ?? 3000;
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>

    <!-- Immediate Zero-Flicker Initialization for Theme, Cyber Accent & Density -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('sf_theme') || 'dark';
            const savedAccent = localStorage.getItem('sf_accent') || 'indigo';
            const savedDensity = localStorage.getItem('sf_density') || 'comfortable';
            document.documentElement.setAttribute('data-theme', savedTheme);
            document.documentElement.setAttribute('data-accent', savedAccent);
            document.documentElement.setAttribute('data-density', savedDensity);
        })();
    </script>

    <!-- Preconnect Google Fonts for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Application Modern Stylesheet -->
    <link rel="stylesheet" type="text/css" href="<?php echo $base_prefix; ?>style/style.css">

    <!-- Chart.js for Advanced Data Visualizations (with fallback) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
    <div id="header">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div id="rightsidebar">
            <!-- Modern Top App Bar -->
            <div class="top-navbar">
                <div class="top-navbar-left">
                    <button type="button" class="mobile-menu-btn" onclick="toggleMobileSidebar()" aria-label="Toggle navigation">
                        ☰
                    </button>
                    <div class="page-breadcrumb">
                        <span>📊 Stock Flow</span>
                        <span class="crumb-sep">/</span>
                        <strong><?php echo htmlspecialchars(str_replace('Stock Flow - ', '', $page_title)); ?></strong>
                    </div>

                    <!-- Command Palette Spotlight Quick Search Trigger -->
                    <button type="button" class="command-palette-trigger" onclick="openCommandPalette()" title="Open Command Palette (Ctrl + K)">
                        <span>🔍</span>
                        <span>Search...</span>
                        <kbd class="cmd-shortcut-chip">Ctrl K</kbd>
                    </button>
                </div>

                <!-- Center Live Telemetry, Theme Studio & System Controls -->
                <div class="top-navbar-center">
                    <div class="live-clock-pill" title="Live System Timestamp">
                        <span>🕒</span>
                        <span id="liveClock">Syncing time...</span>
                    </div>

                    <div class="live-telemetry-pill" title="MySQL Database Telemetry">
                        <span class="pulse-dot pulse-emerald"></span>
                        <span>Port <?php echo htmlspecialchars($active_port); ?> Live</span>
                    </div>

                    <!-- 🎨 Theme Studio Cyber Accent Selector Dropdown -->
                    <div class="theme-accent-picker" id="themeAccentPicker">
                        <button type="button" class="accent-dropdown-btn" id="accentDropdownBtn" onclick="toggleAccentDropdown()" title="Choose Theme Accent Palette">
                            <span class="accent-dot dot-indigo" id="currentAccentDot"></span>
                            <span id="currentAccentName">Indigo</span>
                            <span style="font-size: 10px; opacity: 0.7;">▾</span>
                        </button>
                        <div class="accent-dropdown-menu" id="accentDropdownMenu">
                            <button type="button" class="accent-opt" onclick="setAccentTheme('indigo')">
                                <span class="accent-dot dot-indigo"></span>
                                <span>🟣 Indigo Neon</span>
                            </button>
                            <button type="button" class="accent-opt" onclick="setAccentTheme('emerald')">
                                <span class="accent-dot dot-emerald"></span>
                                <span>🟢 Matrix Emerald</span>
                            </button>
                            <button type="button" class="accent-opt" onclick="setAccentTheme('sunset')">
                                <span class="accent-dot dot-sunset"></span>
                                <span>🔴 Cyber Sunset</span>
                            </button>
                            <button type="button" class="accent-opt" onclick="setAccentTheme('nordic')">
                                <span class="accent-dot dot-nordic"></span>
                                <span>🔵 Nordic Ice</span>
                            </button>
                        </div>
                    </div>

                    <!-- Interactive Dark / Light Mode Toggle -->
                    <button type="button" class="theme-toggle-btn" id="themeToggleBtn" onclick="toggleTheme()" title="Toggle Dark / Light Theme" aria-label="Toggle theme">
                        <span id="themeToggleIcon">🌙</span>
                        <span id="themeToggleText">Dark</span>
                    </button>

                    <!-- Table Density Mode Toggle -->
                    <button type="button" class="density-toggle-btn" id="densityToggleBtn" onclick="toggleDensity()" title="Toggle View Density" aria-label="Toggle density">
                        <span>📐</span>
                        <span>Comfy</span>
                    </button>

                    <!-- Futuristic Sound FX Toggle -->
                    <button type="button" class="sound-toggle-btn" id="soundToggleBtn" onclick="toggleSound()" title="Toggle Sound FX" aria-label="Toggle sound">
                        <span>🔊</span>
                        <span>Sound</span>
                    </button>
                </div>

                <div class="top-navbar-right">
                    <!-- High-Frequency Quick Action Shortcuts -->
                    <div class="quick-action-btns">
                        <a href="<?php echo $pages_prefix; ?>sales.php" class="btn-quick-action action-sale" title="Create New Sales Order">
                            <span>+</span>
                            <span>Sale</span>
                        </a>
                        <a href="<?php echo $pages_prefix; ?>inventory.php" class="btn-quick-action action-restock" title="Inbound Stock Adjustment">
                            <span>+</span>
                            <span>Restock</span>
                        </a>
                    </div>

                    <div class="top-user-pill">
                        <span><strong><?php echo htmlspecialchars($user_name); ?></strong></span>
                        <span class="role-badge role-<?php echo htmlspecialchars($user_role); ?>">
                            <?php echo strtoupper(htmlspecialchars($user_role)); ?>
                        </span>
                    </div>

                    <a href="<?php echo $base_prefix; ?>logout.php" class="btn-top-logout" title="Sign Out of Session">
                        <span>🚪</span>
                        <span>Sign Out</span>
                    </a>
                </div>
            </div>

            <!-- Global Flash Alert Messages -->
            <?php echo render_flash(); ?>

            <!-- 🔍 Raycast / Linear Style Global Command Palette (Ctrl + K) -->
            <div id="cmdPaletteModal" class="cmd-palette-backdrop">
                <div class="cmd-palette-dialog">
                    <div class="cmd-palette-header">
                        <span class="cmd-palette-icon">⚡</span>
                        <input type="text" id="cmdPaletteInput" class="cmd-palette-input" placeholder="Type a command or jump to page... (e.g. sale, emerald, dark, products)" autocomplete="off">
                        <span class="cmd-esc-badge">ESC</span>
                    </div>
                    <div class="cmd-palette-body" id="cmdPaletteBody">
                        <!-- Quick Actions Group -->
                        <div class="cmd-group-wrapper">
                            <div class="cmd-group-title">⚡ Quick Actions</div>
                            <a href="<?php echo $pages_prefix; ?>sales.php" class="cmd-item" data-search="sale create record invoice order checkout customer">
                                <div class="cmd-item-left">
                                    <span class="cmd-item-icon">💰</span>
                                    <div>
                                        <div class="cmd-item-title">Create Sales Order</div>
                                        <div class="cmd-item-sub">Record customer checkout and invoice</div>
                                    </div>
                                </div>
                                <span class="cmd-item-badge">Action</span>
                            </a>
                            <a href="<?php echo $pages_prefix; ?>inventory.php" class="cmd-item" data-search="inventory restock adjust stock inbound">
                                <div class="cmd-item-left">
                                    <span class="cmd-item-icon">📦</span>
                                    <div>
                                        <div class="cmd-item-title">Inbound Inventory Restock</div>
                                        <div class="cmd-item-sub">Adjust stock counts and safety thresholds</div>
                                    </div>
                                </div>
                                <span class="cmd-item-badge">Action</span>
                            </a>
                            <a href="<?php echo $pages_prefix; ?>products.php" class="cmd-item" data-search="product add new item catalog sku price">
                                <div class="cmd-item-left">
                                    <span class="cmd-item-icon">🏷️</span>
                                    <div>
                                        <div class="cmd-item-title">Catalog New Product</div>
                                        <div class="cmd-item-sub">Add SKU, pricing and category assignment</div>
                                    </div>
                                </div>
                                <span class="cmd-item-badge">Action</span>
                            </a>
                        </div>

                        <!-- Navigation Group -->
                        <div class="cmd-group-wrapper">
                            <div class="cmd-group-title">🧭 Jump to Module</div>
                            <a href="<?php echo $pages_prefix; ?>dashboard.php" class="cmd-item" data-search="dashboard home telemetry metrics overview kpi">
                                <div class="cmd-item-left">
                                    <span class="cmd-item-icon">📊</span>
                                    <div>
                                        <div class="cmd-item-title">Executive Dashboard</div>
                                        <div class="cmd-item-sub">KPI summaries & turnover charts</div>
                                    </div>
                                </div>
                                <span class="cmd-item-badge">Page</span>
                            </a>
                            <a href="<?php echo $pages_prefix; ?>products.php" class="cmd-item" data-search="products catalog inventory items list">
                                <div class="cmd-item-left">
                                    <span class="cmd-item-icon">📦</span>
                                    <div>
                                        <div class="cmd-item-title">Products Catalog</div>
                                        <div class="cmd-item-sub">Full product registry and pricing</div>
                                    </div>
                                </div>
                                <span class="cmd-item-badge">Page</span>
                            </a>
                            <a href="<?php echo $pages_prefix; ?>categories.php" class="cmd-item" data-search="categories tags groups classifications">
                                <div class="cmd-item-left">
                                    <span class="cmd-item-icon">🏷️</span>
                                    <div>
                                        <div class="cmd-item-title">Categories</div>
                                        <div class="cmd-item-sub">Taxonomy and catalog tags</div>
                                    </div>
                                </div>
                                <span class="cmd-item-badge">Page</span>
                            </a>
                            <a href="<?php echo $pages_prefix; ?>inventory.php" class="cmd-item" data-search="inventory audit safety stock levels log">
                                <div class="cmd-item-left">
                                    <span class="cmd-item-icon">📋</span>
                                    <div>
                                        <div class="cmd-item-title">Inventory Control</div>
                                        <div class="cmd-item-sub">Stock ledger & audit log</div>
                                    </div>
                                </div>
                                <span class="cmd-item-badge">Page</span>
                            </a>
                            <a href="<?php echo $pages_prefix; ?>sales.php" class="cmd-item" data-search="sales invoices revenue orders clients checkout">
                                <div class="cmd-item-left">
                                    <span class="cmd-item-icon">💳</span>
                                    <div>
                                        <div class="cmd-item-title">Sales Orders</div>
                                        <div class="cmd-item-sub">Outbound customer transaction ledger</div>
                                    </div>
                                </div>
                                <span class="cmd-item-badge">Page</span>
                            </a>
                            <a href="<?php echo $pages_prefix; ?>purchases.php" class="cmd-item" data-search="purchases procurement expenses vendor po">
                                <div class="cmd-item-left">
                                    <span class="cmd-item-icon">🛒</span>
                                    <div>
                                        <div class="cmd-item-title">Purchase Orders</div>
                                        <div class="cmd-item-sub">Inbound supplier procurement ledger</div>
                                    </div>
                                </div>
                                <span class="cmd-item-badge">Page</span>
                            </a>
                            <a href="<?php echo $pages_prefix; ?>customers.php" class="cmd-item" data-search="customers clients directory crm accounts">
                                <div class="cmd-item-left">
                                    <span class="cmd-item-icon">👥</span>
                                    <div>
                                        <div class="cmd-item-title">Customers CRM</div>
                                        <div class="cmd-item-sub">Client directory and lifetime value</div>
                                    </div>
                                </div>
                                <span class="cmd-item-badge">Page</span>
                            </a>
                            <a href="<?php echo $pages_prefix; ?>suppliers.php" class="cmd-item" data-search="suppliers vendors directory procurement partners">
                                <div class="cmd-item-left">
                                    <span class="cmd-item-icon">🚚</span>
                                    <div>
                                        <div class="cmd-item-title">Suppliers Directory</div>
                                        <div class="cmd-item-sub">Vendor accounts & procurement contacts</div>
                                    </div>
                                </div>
                                <span class="cmd-item-badge">Page</span>
                            </a>
                            <a href="<?php echo $pages_prefix; ?>reports.php" class="cmd-item" data-search="reports financial analytics margins profit print turnover">
                                <div class="cmd-item-left">
                                    <span class="cmd-item-icon">📈</span>
                                    <div>
                                        <div class="cmd-item-title">Financial Reports</div>
                                        <div class="cmd-item-sub">Margins, revenue generators & print mode</div>
                                    </div>
                                </div>
                                <span class="cmd-item-badge">Page</span>
                            </a>
                            <a href="<?php echo $pages_prefix; ?>settings.php" class="cmd-item" data-search="settings system configuration audit logs security">
                                <div class="cmd-item-left">
                                    <span class="cmd-item-icon">⚙️</span>
                                    <div>
                                        <div class="cmd-item-title">System Settings</div>
                                        <div class="cmd-item-sub">Configuration & security audit log</div>
                                    </div>
                                </div>
                                <span class="cmd-item-badge">Page</span>
                            </a>
                        </div>

                        <!-- Theme Studio & Controls Group -->
                        <div class="cmd-group-wrapper">
                            <div class="cmd-group-title">🎨 Theme Studio & Controls</div>
                            <div class="cmd-item" onclick="toggleTheme()" data-search="toggle dark light mode theme obsidian porcelain">
                                <div class="cmd-item-left">
                                    <span class="cmd-item-icon">🌓</span>
                                    <div>
                                        <div class="cmd-item-title">Toggle Dark / Light Theme</div>
                                        <div class="cmd-item-sub">Switch between Deep Cyber and Porcelain mode</div>
                                    </div>
                                </div>
                                <span class="cmd-item-badge">Theme</span>
                            </div>
                            <div class="cmd-item" onclick="setAccentTheme('indigo')" data-search="indigo neon accent violet linear vercel">
                                <div class="cmd-item-left">
                                    <span class="cmd-item-icon">🟣</span>
                                    <div>
                                        <div class="cmd-item-title">Activate Indigo Neon Theme</div>
                                        <div class="cmd-item-sub">Linear & Vercel electric obsidian styling</div>
                                    </div>
                                </div>
                                <span class="cmd-item-badge">Accent</span>
                            </div>
                            <div class="cmd-item" onclick="setAccentTheme('emerald')" data-search="emerald matrix green supabase fintech cyber terminal">
                                <div class="cmd-item-left">
                                    <span class="cmd-item-icon">🟢</span>
                                    <div>
                                        <div class="cmd-item-title">Activate Matrix Emerald Theme</div>
                                        <div class="cmd-item-sub">Supabase high-tech fintech terminal glow</div>
                                    </div>
                                </div>
                                <span class="cmd-item-badge">Accent</span>
                            </div>
                            <div class="cmd-item" onclick="setAccentTheme('sunset')" data-search="sunset tokyo synthwave neon rose pink amber glow">
                                <div class="cmd-item-left">
                                    <span class="cmd-item-icon">🔴</span>
                                    <div>
                                        <div class="cmd-item-title">Activate Cyber Sunset Theme</div>
                                        <div class="cmd-item-sub">Tokyo synthwave neon rose & amber glow</div>
                                    </div>
                                </div>
                                <span class="cmd-item-badge">Accent</span>
                            </div>
                            <div class="cmd-item" onclick="setAccentTheme('nordic')" data-search="nordic ice arctic blue cyan oceanic slate clean">
                                <div class="cmd-item-left">
                                    <span class="cmd-item-icon">🔵</span>
                                    <div>
                                        <div class="cmd-item-title">Activate Nordic Ice Theme</div>
                                        <div class="cmd-item-sub">Minimalist Scandinavian arctic cyan & slate</div>
                                    </div>
                                </div>
                                <span class="cmd-item-badge">Accent</span>
                            </div>
                            <div class="cmd-item" onclick="toggleDensity()" data-search="density compact comfortable table view data dense">
                                <div class="cmd-item-left">
                                    <span class="cmd-item-icon">📐</span>
                                    <div>
                                        <div class="cmd-item-title">Toggle Table Density</div>
                                        <div class="cmd-item-sub">Switch between Comfortable and Compact data view</div>
                                    </div>
                                </div>
                                <span class="cmd-item-badge">Display</span>
                            </div>
                            <div class="cmd-item" onclick="toggleSound()" data-search="sound audio effects mute cyber clicks beeps">
                                <div class="cmd-item-left">
                                    <span class="cmd-item-icon">🔊</span>
                                    <div>
                                        <div class="cmd-item-title">Toggle Futuristic Sound FX</div>
                                        <div class="cmd-item-sub">Synthesized audio micro-feedback</div>
                                    </div>
                                </div>
                                <span class="cmd-item-badge">Audio</span>
                            </div>
                        </div>
                    </div>
                    <div class="cmd-palette-footer">
                        <div class="cmd-hints">
                            <span class="cmd-hint-key"><kbd>↑</kbd><kbd>↓</kbd> Navigate</span>
                            <span class="cmd-hint-key"><kbd>↵</kbd> Select</span>
                            <span class="cmd-hint-key"><kbd>Esc</kbd> Close</span>
                        </div>
                        <span>Stock Flow Enterprise Telemetry</span>
                    </div>
                </div>
            </div>
