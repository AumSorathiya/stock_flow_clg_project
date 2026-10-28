/**
 * Stock Flow - Application Scripts & Helpers
 */

// Toggle mobile navigation sidebar
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

// Auto-calculate live totals for Sales Form
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

// Auto-calculate live totals for Purchases Form
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

// Auto-dismiss flash alerts
function initFlashDismissal() {
    const alerts = document.querySelectorAll('.flash-alert');
    alerts.forEach((alert) => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.4s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 400);
        }, 5000);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initSalesCalculator();
    initPurchaseCalculator();
    initFlashDismissal();
});
