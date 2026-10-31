/**
 * Stock Flow - Enterprise Client-Side Engine & Interactive Toolkit
 */

// 0. Advanced Theme Controller (Light / Dark Switcher & 4 Cyber Accents)
function initTheme() {
    const savedTheme = localStorage.getItem('sf_theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeToggleUI(savedTheme);
    initAccent();
    initDensity();
    initSound();
}

function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme') || 'dark';
    const next = (current === 'dark') ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('sf_theme', next);
    updateThemeToggleUI(next);
    playSound('switch');

    // Broadcast theme change event for charts and telemetry
    const currentAccent = document.documentElement.getAttribute('data-accent') || 'indigo';
    window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: next, accent: currentAccent } }));
}

function updateThemeToggleUI(theme) {
    const icon = document.getElementById('themeToggleIcon');
    const text = document.getElementById('themeToggleText');
    const btn = document.getElementById('themeToggleBtn');
    if (!btn) return;

    if (theme === 'dark') {
        if (icon) icon.innerText = '🌙';
        if (text) text.innerText = 'Dark';
        btn.setAttribute('title', 'Switch to Light Mode');
    } else {
        if (icon) icon.innerText = '☀️';
        if (text) text.innerText = 'Light';
        btn.setAttribute('title', 'Switch to Dark Mode');
    }
}

// 0b. Theme Studio Cyber Accent Selector (Indigo, Emerald, Sunset, Nordic)
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
    
    // Close dropdown menu if open
    const menu = document.getElementById('accentDropdownMenu');
    if (menu) menu.classList.remove('show');

    // Broadcast theme change event for Chart.js
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
    window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: currentTheme, accent } }));
}

function toggleAccentDropdown() {
    const menu = document.getElementById('accentDropdownMenu');
    if (menu) {
        menu.classList.toggle('show');
        playSound('click');
    }
}

function updateAccentUI(accent) {
    const nameEl = document.getElementById('currentAccentName');
    const dotEl = document.getElementById('currentAccentDot');
    const names = {
        indigo: 'Indigo',
        emerald: 'Emerald',
        sunset: 'Sunset',
        nordic: 'Nordic'
    };
    if (nameEl) nameEl.innerText = names[accent] || 'Indigo';
    if (dotEl) {
        dotEl.className = 'accent-dot dot-' + accent;
    }

    // Also update any auth page accent dots
    document.querySelectorAll('.auth-accent-dot').forEach(btn => {
        if (btn.getAttribute('data-accent') === accent) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
}

// Close accent dropdown when clicking outside
document.addEventListener('click', (e) => {
    const picker = document.getElementById('themeAccentPicker');
    const menu = document.getElementById('accentDropdownMenu');
    if (picker && menu && !picker.contains(e.target)) {
        menu.classList.remove('show');
    }
});

// 0c. Web Audio Futuristic Micro-Feedback Engine (Pure synthesized audio, 100% offline)
let audioCtx = null;
function getAudioContext() {
    if (!audioCtx && (window.AudioContext || window.webkitAudioContext)) {
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    }
    return audioCtx;
}

function playSound(type) {
    if (localStorage.getItem('sf_sound') === 'off') return;
    try {
        const ctx = getAudioContext();
        if (!ctx) return;
        if (ctx.state === 'suspended') ctx.resume();

        const now = ctx.currentTime;
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);

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
        } else if (type === 'open') {
            osc.type = 'sine';
            osc.frequency.setValueAtTime(350, now);
            osc.frequency.exponentialRampToValueAtTime(700, now + 0.08);
            gain.gain.setValueAtTime(0.04, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.08);
            osc.start(now);
            osc.stop(now + 0.08);
        } else if (type === 'success') {
            osc.type = 'sine';
            osc.frequency.setValueAtTime(523.25, now);
            osc.frequency.setValueAtTime(659.25, now + 0.05);
            osc.frequency.setValueAtTime(783.99, now + 0.1);
            gain.gain.setValueAtTime(0.05, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.18);
            osc.start(now);
            osc.stop(now + 0.18);
        }
    } catch (e) {
        // Silently ignore audio restrictions
    }
}

function initSound() {
    const saved = localStorage.getItem('sf_sound') || 'on';
    updateSoundUI(saved);
}

function toggleSound() {
    const current = localStorage.getItem('sf_sound') || 'on';
    const next = (current === 'on') ? 'off' : 'on';
    localStorage.setItem('sf_sound', next);
    updateSoundUI(next);
    if (next === 'on') {
        playSound('success');
    }
}

function updateSoundUI(state) {
    const btn = document.getElementById('soundToggleBtn');
    if (!btn) return;
    if (state === 'on') {
        btn.innerHTML = '<span>🔊</span> <span>Sound</span>';
        btn.setAttribute('title', 'Sound FX: ON (Click to Mute)');
    } else {
        btn.innerHTML = '<span>🔇</span> <span>Muted</span>';
        btn.setAttribute('title', 'Sound FX: MUTED (Click to Unmute)');
    }
}

// 0d. Table Density Mode (Comfortable vs Compact)
function initDensity() {
    const saved = localStorage.getItem('sf_density') || 'comfortable';
    document.documentElement.setAttribute('data-density', saved);
    updateDensityUI(saved);
}

function toggleDensity() {
    const current = document.documentElement.getAttribute('data-density') || 'comfortable';
    const next = (current === 'compact') ? 'comfortable' : 'compact';
    document.documentElement.setAttribute('data-density', next);
    localStorage.setItem('sf_density', next);
    updateDensityUI(next);
    playSound('click');
}

function updateDensityUI(density) {
    const btn = document.getElementById('densityToggleBtn');
    if (!btn) return;
    if (density === 'compact') {
        btn.innerHTML = '<span>📐</span> <span>Compact</span>';
        btn.setAttribute('title', 'View Density: Compact (Click for Comfortable)');
    } else {
        btn.innerHTML = '<span>📐</span> <span>Comfy</span>';
        btn.setAttribute('title', 'View Density: Comfortable (Click for Compact)');
    }
}

// 0e. Linear-Style Interactive Card Spotlight (Hover Cursor Follower)
function initCardSpotlight() {
    let ticking = false;
    document.addEventListener('mousemove', (e) => {
        if (!ticking) {
            window.requestAnimationFrame(() => {
                const target = e.target.closest('.card-modern, .stat-card, .table-card, .quick-action-card, .chart-card, #header, .login-form');
                if (target) {
                    const rect = target.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    target.style.setProperty('--mouse-x', `${x}px`);
                    target.style.setProperty('--mouse-y', `${y}px`);
                }
                ticking = false;
            });
            ticking = true;
        }
    });
}

// 0f. Raycast / Linear Style Command Palette (Ctrl + K)
let currentCmdIndex = 0;
function openCommandPalette() {
    const modal = document.getElementById('cmdPaletteModal');
    const input = document.getElementById('cmdPaletteInput');
    if (!modal) return;
    modal.classList.add('active');
    playSound('open');
    if (input) {
        input.value = '';
        filterCommandPalette('');
        setTimeout(() => input.focus(), 50);
    }
}

function closeCommandPalette() {
    const modal = document.getElementById('cmdPaletteModal');
    if (!modal) return;
    modal.classList.remove('active');
    playSound('click');
}

function filterCommandPalette(query) {
    const q = (query || '').toLowerCase().trim();
    const items = document.querySelectorAll('.cmd-item');
    const groups = document.querySelectorAll('.cmd-group-wrapper');

    items.forEach(item => {
        const text = (item.getAttribute('data-search') || item.innerText).toLowerCase();
        if (!q || text.includes(q)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });

    // Hide empty group titles
    groups.forEach(g => {
        const visibleInGroup = g.querySelectorAll('.cmd-item:not([style*="display: none"])').length;
        g.style.display = visibleInGroup > 0 ? 'block' : 'none';
    });

    // Update active highlight index
    currentCmdIndex = 0;
    updateActiveCmdItem();
}

function updateActiveCmdItem() {
    const visibleItems = Array.from(document.querySelectorAll('.cmd-item')).filter(i => i.style.display !== 'none');
    visibleItems.forEach((item, idx) => {
        if (idx === currentCmdIndex) {
            item.classList.add('active');
            item.scrollIntoView({ block: 'nearest' });
        } else {
            item.classList.remove('active');
        }
    });
}

function initCommandPalette() {
    const modal = document.getElementById('cmdPaletteModal');
    const input = document.getElementById('cmdPaletteInput');

    // Global keyboard listener (Ctrl+K, Cmd+K, /, Esc)
    document.addEventListener('keydown', (e) => {
        // Toggle palette with Ctrl+K, Cmd+K or /
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            if (modal && modal.classList.contains('active')) {
                closeCommandPalette();
            } else {
                openCommandPalette();
            }
            return;
        }

        if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
            e.preventDefault();
            openCommandPalette();
            return;
        }

        // Inside Command Palette keyboard controls
        if (modal && modal.classList.contains('active')) {
            if (e.key === 'Escape') {
                e.preventDefault();
                closeCommandPalette();
                return;
            }

            const visibleItems = Array.from(document.querySelectorAll('.cmd-item')).filter(i => i.style.display !== 'none');
            if (visibleItems.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                currentCmdIndex = (currentCmdIndex + 1) % visibleItems.length;
                updateActiveCmdItem();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                currentCmdIndex = (currentCmdIndex - 1 + visibleItems.length) % visibleItems.length;
                updateActiveCmdItem();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const activeItem = visibleItems[currentCmdIndex];
                if (activeItem) {
                    activeItem.click();
                    closeCommandPalette();
                }
            }
        }
    });

    if (input) {
        input.addEventListener('input', (e) => filterCommandPalette(e.target.value));
    }

    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeCommandPalette();
            }
        });
    }
}

// 1. Toggle Mobile Navigation Drawer
function toggleMobileSidebar() {
    const sidebar = document.getElementById('leftsidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (sidebar) {
        sidebar.classList.toggle('open');
    }
    if (backdrop) {
        backdrop.classList.toggle('active');
    }
}

// 2. Real-Time Dynamic Digital Clock
function initLiveClock() {
    const clockEl = document.getElementById('liveClock');
    if (!clockEl) return;

    function updateTime() {
        const now = new Date();
        const dateStr = now.toLocaleDateString(undefined, { 
            month: 'short', 
            day: 'numeric', 
            year: 'numeric' 
        });
        const timeStr = now.toLocaleTimeString(undefined, { 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit', 
            hour12: true 
        });
        clockEl.innerText = `${dateStr} • ${timeStr}`;
    }

    updateTime();
    setInterval(updateTime, 1000);
}

// 3. Universal High-Performance CSV Exporter
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) {
        alert("Table not found: " + tableId);
        return;
    }

    const rows = [];
    const headers = [];
    const headerCols = table.querySelectorAll('thead th');
    const excludedColIndices = [];

    headerCols.forEach((th, idx) => {
        const text = th.innerText.trim();
        const upper = text.toUpperCase();
        if (upper === 'ACTION' || upper === 'ACTIONS') {
            excludedColIndices.push(idx);
        } else {
            headers.push('"' + text.replace(/"/g, '""') + '"');
        }
    });

    if (headers.length > 0) {
        rows.push(headers.join(','));
    }

    // Extract all currently visible data rows
    const trs = table.querySelectorAll('tbody tr');
    trs.forEach((tr) => {
        if (tr.style.display === 'none' || 
            tr.classList.contains('filter-no-results') || 
            tr.classList.contains('empty-state-row')) {
            return;
        }

        const rowData = [];
        const cells = tr.querySelectorAll('td');
        cells.forEach((td, idx) => {
            if (excludedColIndices.includes(idx)) return;
            let val = td.innerText.trim().replace(/\r?\n|\r/g, ' ');
            rowData.push('"' + val.replace(/"/g, '""') + '"');
        });

        if (rowData.length > 0) {
            rows.push(rowData.join(','));
        }
    });

    if (rows.length <= 1) {
        alert("No table records available to export for current view.");
        return;
    }

    const csvContent = "data:text/csv;charset=utf-8,\uFEFF" + rows.join("\r\n");
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", (filename || "stock_flow_export") + "_" + (new Date().toISOString().slice(0, 10)) + ".csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// 4. Smooth Collapsible Form Drawer Toggle
function toggleFormCard(cardId, buttonId) {
    const card = document.getElementById(cardId);
    const btn = document.getElementById(buttonId);
    if (!card) return;

    const isHidden = (window.getComputedStyle(card).display === 'none') || card.classList.contains('collapsed');

    if (isHidden) {
        card.style.display = 'block';
        card.classList.remove('collapsed');
        if (btn) {
            if (!btn.getAttribute('data-original-label')) {
                btn.setAttribute('data-original-label', btn.innerHTML);
            }
            btn.innerHTML = '<span>✕ Close Form</span>';
            btn.classList.add('btn-secondary');
            btn.classList.remove('btn-primary');
        }
        card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } else {
        card.style.display = 'none';
        card.classList.add('collapsed');
        if (btn) {
            btn.innerHTML = btn.getAttribute('data-original-label') || '<span>➕ Add New</span>';
            btn.classList.remove('btn-secondary');
            btn.classList.add('btn-primary');
        }
    }
}

// 5. Auto-calculate live totals for Sales Form
function initSalesCalculator() {
    const prodSelect = document.getElementById('sales_product_select');
    const qtyInput = document.getElementById('sales_quantity_input');
    const priceInput = document.getElementById('unit_price_input');
    const totalDisplay = document.getElementById('sales_total_display');

    function recalculate() {
        if (!priceInput || !qtyInput || !totalDisplay) return;
        const qty = parseFloat(qtyInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        const total = qty * price;
        totalDisplay.innerText = '$' + total.toFixed(2);
    }

    if (prodSelect) {
        prodSelect.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            const price = opt.getAttribute('data-price');
            if (price && priceInput) {
                priceInput.value = parseFloat(price).toFixed(2);
                recalculate();
            }
        });
    }

    if (qtyInput) qtyInput.addEventListener('input', recalculate);
    if (priceInput) priceInput.addEventListener('input', recalculate);
}

// 6. Auto-calculate live totals for Purchases Form
function initPurchaseCalculator() {
    const prodSelect = document.getElementById('purchase_product_select');
    const qtyInput = document.getElementById('purchase_quantity_input');
    const costInput = document.getElementById('unit_cost_input');
    const totalDisplay = document.getElementById('purchase_total_display');

    function recalculate() {
        if (!costInput || !qtyInput || !totalDisplay) return;
        const qty = parseFloat(qtyInput.value) || 0;
        const cost = parseFloat(costInput.value) || 0;
        const total = qty * cost;
        totalDisplay.innerText = '$' + total.toFixed(2);
    }

    if (prodSelect) {
        prodSelect.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            const cost = opt.getAttribute('data-cost');
            if (cost && costInput) {
                costInput.value = parseFloat(cost).toFixed(2);
                recalculate();
            }
        });
    }

    if (qtyInput) qtyInput.addEventListener('input', recalculate);
    if (costInput) costInput.addEventListener('input', recalculate);
}

// 7. Auto-dismiss flash alerts
function initFlashDismissal() {
    const alerts = document.querySelectorAll('.flash-alert');
    alerts.forEach((alert) => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-6px)';
            setTimeout(() => alert.remove(), 400);
        }, 5000);
    });
}

// 8. Universal Interactive Table Filter Engine
function initTableFilters() {
    const filterBars = document.querySelectorAll('.table-filter-bar');

    filterBars.forEach((bar) => {
        const tableId = bar.getAttribute('data-table');
        if (!tableId) return;
        const table = document.getElementById(tableId);
        if (!table) return;

        const tbody = table.querySelector('tbody');
        if (!tbody) return;

        const searchInput = bar.querySelector('.filter-search');
        const selectFilters = bar.querySelectorAll('.filter-select');
        const sortSelect = bar.querySelector('.filter-sort');
        const resetBtn = bar.querySelector('.btn-filter-reset');
        const counterBadge = bar.querySelector('.filter-count-badge');

        const getRows = () => Array.from(tbody.querySelectorAll('tr')).filter(
            tr => !tr.classList.contains('filter-no-results') && !tr.classList.contains('empty-state-row')
        );

        const initialRows = getRows();
        const totalCount = initialRows.length;

        function applyFilters() {
            const rows = getRows();
            if (rows.length === 0) return;

            const searchVal = (searchInput ? searchInput.value : '').toLowerCase().trim();
            const searchClean = searchVal.replace(/^#/, '');

            const activeSelects = [];
            selectFilters.forEach((sel) => {
                const key = sel.getAttribute('data-filter');
                const val = sel.value.toLowerCase().trim();
                if (key && val) {
                    activeSelects.push({ key, val });
                }
            });

            let visibleCount = 0;

            rows.forEach((row) => {
                let matchesSearch = true;
                if (searchVal) {
                    const rowText = row.innerText.toLowerCase();
                    const rowId = (row.getAttribute('data-id') || '').toLowerCase();
                    const rowSku = (row.getAttribute('data-sku') || '').toLowerCase();
                    const rowName = (row.getAttribute('data-name') || '').toLowerCase();

                    matchesSearch = rowText.includes(searchVal) || 
                                    (rowId && (rowId === searchClean || rowId.includes(searchClean))) ||
                                    (rowSku && rowSku.includes(searchVal)) ||
                                    (rowName && rowName.includes(searchVal));
                }

                let matchesSelects = true;
                if (matchesSearch && activeSelects.length > 0) {
                    for (const { key, val } of activeSelects) {
                        const rowAttr = (row.getAttribute(`data-${key}`) || '').toLowerCase();
                        if (rowAttr) {
                            if (rowAttr !== val && !rowAttr.includes(val)) {
                                matchesSelects = false;
                                break;
                            }
                        } else {
                            const rowText = row.innerText.toLowerCase();
                            if (!rowText.includes(val)) {
                                matchesSelects = false;
                                break;
                            }
                        }
                    }
                }

                if (matchesSearch && matchesSelects) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Handle Sorting
            const sortVal = sortSelect ? sortSelect.value : '';
            if (sortVal) {
                const sortedRows = [...rows].sort((a, b) => {
                    if (sortVal === 'id-asc') {
                        const idA = parseInt(a.getAttribute('data-id') || '0', 10);
                        const idB = parseInt(b.getAttribute('data-id') || '0', 10);
                        return idA - idB;
                    }
                    if (sortVal === 'id-desc') {
                        const idA = parseInt(a.getAttribute('data-id') || '0', 10);
                        const idB = parseInt(b.getAttribute('data-id') || '0', 10);
                        return idB - idA;
                    }
                    if (sortVal === 'name-asc') {
                        const nameA = (a.getAttribute('data-name') || a.cells[1]?.innerText || '').toLowerCase();
                        const nameB = (b.getAttribute('data-name') || b.cells[1]?.innerText || '').toLowerCase();
                        return nameA.localeCompare(nameB);
                    }
                    if (sortVal === 'name-desc') {
                        const nameA = (a.getAttribute('data-name') || a.cells[1]?.innerText || '').toLowerCase();
                        const nameB = (b.getAttribute('data-name') || b.cells[1]?.innerText || '').toLowerCase();
                        return nameB.localeCompare(nameA);
                    }
                    if (sortVal === 'price-asc') {
                        const priceA = parseFloat(a.getAttribute('data-price') || '0');
                        const priceB = parseFloat(b.getAttribute('data-price') || '0');
                        return priceA - priceB;
                    }
                    if (sortVal === 'price-desc') {
                        const priceA = parseFloat(a.getAttribute('data-price') || '0');
                        const priceB = parseFloat(b.getAttribute('data-price') || '0');
                        return priceB - priceA;
                    }
                    if (sortVal === 'stock-asc') {
                        const stockA = parseInt(a.getAttribute('data-stock') || '0', 10);
                        const stockB = parseInt(b.getAttribute('data-stock') || '0', 10);
                        return stockA - stockB;
                    }
                    if (sortVal === 'stock-desc') {
                        const stockA = parseInt(a.getAttribute('data-stock') || '0', 10);
                        const stockB = parseInt(b.getAttribute('data-stock') || '0', 10);
                        return stockB - stockA;
                    }
                    if (sortVal === 'amount-desc') {
                        const amtA = parseFloat(a.getAttribute('data-amount') || '0');
                        const amtB = parseFloat(b.getAttribute('data-amount') || '0');
                        return amtB - amtA;
                    }
                    if (sortVal === 'amount-asc') {
                        const amtA = parseFloat(a.getAttribute('data-amount') || '0');
                        const amtB = parseFloat(b.getAttribute('data-amount') || '0');
                        return amtA - amtB;
                    }
                    return 0;
                });

                sortedRows.forEach(r => tbody.appendChild(r));
            }

            // Update Counter Badge
            if (counterBadge) {
                counterBadge.innerText = `Showing ${visibleCount} of ${totalCount}`;
            }

            // Handle No Results Row
            let noResRow = tbody.querySelector('.filter-no-results');
            if (visibleCount === 0 && totalCount > 0) {
                if (!noResRow) {
                    const colCount = table.querySelectorAll('thead th').length || 8;
                    noResRow = document.createElement('tr');
                    noResRow.className = 'filter-no-results';
                    noResRow.innerHTML = `<td colspan="${colCount}" style="padding: 28px; text-align: center; color: #64748b; font-size: 14px;">
                        🔍 No matching records found for the applied filter.
                    </td>`;
                    tbody.appendChild(noResRow);
                } else {
                    noResRow.style.display = '';
                }
            } else if (noResRow) {
                noResRow.style.display = 'none';
            }
        }

        // Attach listeners
        if (searchInput) {
            searchInput.addEventListener('input', applyFilters);
        }
        selectFilters.forEach(sel => sel.addEventListener('change', applyFilters));
        if (sortSelect) {
            sortSelect.addEventListener('change', applyFilters);
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                if (searchInput) searchInput.value = '';
                selectFilters.forEach(sel => sel.selectedIndex = 0);
                if (sortSelect) sortSelect.selectedIndex = 0;
                applyFilters();
            });
        }

        if (counterBadge && totalCount > 0) {
            counterBadge.innerText = `Showing ${totalCount} of ${totalCount}`;
        }
    });
}

// Initialize on DOM Ready
document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initCardSpotlight();
    initCommandPalette();
    initLiveClock();
    initSalesCalculator();
    initPurchaseCalculator();
    initFlashDismissal();
    initTableFilters();
});
