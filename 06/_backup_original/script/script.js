// ===== Stock Flow - Main Application Script =====
// Fully integrated Inventory Management System with SQL Data Architecture

// ===== Initial State & Local Storage SQL Database Sync =====
const DEFAULT_CATEGORIES = [
    { id: 1, name: "Electronics", description: "Gadgets, devices, and accessories" },
    { id: 2, name: "Furniture", description: "Office and home furniture" },
    { id: 3, name: "Stationery", description: "Office supplies and writing materials" }
];

const DEFAULT_SUPPLIERS = [
    { id: 1, name: "TechSupply Co.", contact_person: "John Smith", email: "john@techsupply.com", phone: "+1-555-0192", address: "100 Tech Way, San Jose, CA" },
    { id: 2, name: "Global Furniture Ltd", contact_person: "Sarah Jenkins", email: "sarah@globalfurniture.com", phone: "+1-555-0143", address: "450 Oak Avenue, Chicago, IL" }
];

const DEFAULT_CUSTOMERS = [
    { id: 1, name: "Acme Corp", email: "contact@acme.com", phone: "+1-555-9012", address: "500 Corporate Blvd" },
    { id: 2, name: "Stark Industries", email: "info@stark.com", phone: "+1-555-3000", address: "10880 Wilshire Blvd" }
];

const DEFAULT_PRODUCTS = [
    { id: 1, sku: "PRD-101", title: "Wireless Mouse", name: "Wireless Mouse", category_id: 1, category_name: "Electronics", supplier_id: 1, supplier_name: "TechSupply Co.", cost_price: 15.00, price: 29.99, sale_price: 29.99, quantity: 45, reorder_level: 10, desc: "Ergonomic wireless mouse" },
    { id: 2, sku: "PRD-102", title: "Mechanical Keyboard", name: "Mechanical Keyboard", category_id: 1, category_name: "Electronics", supplier_id: 1, supplier_name: "TechSupply Co.", cost_price: 45.00, price: 89.99, sale_price: 89.99, quantity: 8, reorder_level: 10, desc: "RGB mechanical keyboard" },
    { id: 3, sku: "PRD-103", title: "Ergonomic Chair", name: "Ergonomic Chair", category_id: 2, category_name: "Furniture", supplier_id: 2, supplier_name: "Global Furniture Ltd", cost_price: 120.00, price: 249.99, sale_price: 249.99, quantity: 12, reorder_level: 5, desc: "Mesh office chair" }
];

const DEFAULT_INVENTORY_LOGS = [
    { id: 1, product_id: 1, product_name: "Wireless Mouse", sku: "PRD-101", type: "IN", quantity: 50, notes: "Initial Stock", created_at: "2026-08-15 10:00" },
    { id: 2, product_id: 2, product_name: "Mechanical Keyboard", sku: "PRD-102", type: "IN", quantity: 10, notes: "Initial Stock", created_at: "2026-08-15 10:30" }
];

const DEFAULT_SALES = [
    { id: 1, customer_id: 1, customer_name: "Acme Corp", total_amount: 149.95, payment_status: "Paid", sale_date: "2026-08-15 11:00" }
];

const DEFAULT_PURCHASES = [
    { id: 1, supplier_id: 1, supplier_name: "TechSupply Co.", total_amount: 1200.00, status: "Completed", purchase_date: "2026-08-14 09:30" }
];

// Helper to get or set LocalStorage Data
function getStoreData(key, defaultVal) {
    const data = localStorage.getItem(key);
    if (!data) {
        localStorage.setItem(key, JSON.stringify(defaultVal));
        return defaultVal;
    }
    return JSON.parse(data);
}

function saveStoreData(key, val) {
    localStorage.setItem(key, JSON.stringify(val));
}

// Application State Arrays
let categoriesArr = getStoreData("sf_categories", DEFAULT_CATEGORIES);
let suppliersArr = getStoreData("sf_suppliers", DEFAULT_SUPPLIERS);
let customersArr = getStoreData("sf_customers", DEFAULT_CUSTOMERS);
let productsArr = getStoreData("sf_products", DEFAULT_PRODUCTS);
let inventoryLogsArr = getStoreData("sf_inventory_logs", DEFAULT_INVENTORY_LOGS);
let salesArr = getStoreData("sf_sales", DEFAULT_SALES);
let purchasesArr = getStoreData("sf_purchases", DEFAULT_PURCHASES);

// User's Coding Style State Variables
let f = false; // Edit mode flag
let editId = null; // Current edit ID

// ===== 1. LOGIN FORM HANDLING =====
const loginForm = document.querySelector(".loginForm");

if (loginForm) {
    loginForm.addEventListener("submit", (e) => {
        e.preventDefault();

        const username = loginForm.username.value.trim();
        const password = loginForm.password.value.trim();

        if (username == "" || password == "") {
            alert("Pls enter the username and password");
            return;
        } else if (username == "admin" && password == "admin") {
            alert("Login Succesful");
            window.location.href = "pages/dashbord.html";
        } else {
            alert("error");
        }
    });
}

// ===== 2. DASHBOARD CONTROLLER =====
function initDashboard() {
    const totalSalesEl = document.getElementById("statTotalSales");
    const totalPurchasesEl = document.getElementById("statTotalPurchases");
    const totalCustomersEl = document.getElementById("statTotalCustomers");
    const totalSuppliersEl = document.getElementById("statTotalSuppliers");
    const lowStockCountEl = document.getElementById("statLowStock");

    if (totalSalesEl) {
        let salesSum = salesArr.reduce((acc, curr) => acc + (parseFloat(curr.total_amount) || 0), 0);
        totalSalesEl.innerText = "$" + salesSum.toFixed(2);
    }
    if (totalPurchasesEl) {
        let purSum = purchasesArr.reduce((acc, curr) => acc + (parseFloat(curr.total_amount) || 0), 0);
        totalPurchasesEl.innerText = "$" + purSum.toFixed(2);
    }
    if (totalCustomersEl) {
        totalCustomersEl.innerText = customersArr.length;
    }
    if (totalSuppliersEl) {
        totalSuppliersEl.innerText = suppliersArr.length;
    }
    if (lowStockCountEl) {
        let lowCount = productsArr.filter(p => p.quantity <= p.reorder_level).length;
        lowStockCountEl.innerText = lowCount;
    }

    // Populate Recent Sales Table on Dashboard
    const dashSalesTable = document.getElementById("dashSalesTable");
    if (dashSalesTable) {
        dashSalesTable.innerHTML = "";
        salesArr.slice(0, 5).forEach((item) => {
            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td>#SL-${item.id}</td>
                <td>${item.customer_name || "Walk-in"}</td>
                <td>$${parseFloat(item.total_amount).toFixed(2)}</td>
                <td><span class="badge badge-success">${item.payment_status}</span></td>
            `;
            dashSalesTable.appendChild(tr);
        });
    }

    // Populate Low Stock Alert Table
    const lowStockTable = document.getElementById("dashLowStockTable");
    if (lowStockTable) {
        lowStockTable.innerHTML = "";
        const lowProds = productsArr.filter(p => p.quantity <= p.reorder_level);
        if (lowProds.length === 0) {
            lowStockTable.innerHTML = `<tr><td colspan="4" style="text-align:center;">No low stock alerts</td></tr>`;
        } else {
            lowProds.forEach((p) => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>${p.sku || "PRD-" + p.id}</td>
                    <td>${p.name || p.title}</td>
                    <td><span class="badge badge-danger">${p.quantity} left</span></td>
                    <td>${p.reorder_level}</td>
                `;
                lowStockTable.appendChild(tr);
            });
        }
    }
}

// ===== 3. PRODUCTS MANAGEMENT CONTROLLER =====
const productForm = document.getElementById("productForm");
const productTableBody = document.getElementById("productTableBody");
const productSubmitBtn = document.querySelector("#productSubmitBtn");

function populateCategorySupplierDropdowns() {
    const catSelect = document.getElementById("productCategory");
    const suppSelect = document.getElementById("productSupplier");

    if (catSelect) {
        catSelect.innerHTML = `<option value="">Select Category</option>`;
        categoriesArr.forEach(c => {
            catSelect.innerHTML += `<option value="${c.id}">${c.name}</option>`;
        });
    }
    if (suppSelect) {
        suppSelect.innerHTML = `<option value="">Select Supplier</option>`;
        suppliersArr.forEach(s => {
            suppSelect.innerHTML += `<option value="${s.id}">${s.name}</option>`;
        });
    }
}

function showProducts(data) {
    if (!productTableBody) return;
    productTableBody.innerHTML = "";

    data.forEach((val) => {
        const tr = document.createElement("tr");
        const isLow = val.quantity <= val.reorder_level;
        const badgeClass = isLow ? "badge-danger" : "badge-success";

        tr.innerHTML = `
            <td>${val.sku || "PRD-" + val.id}</td>
            <td><strong>${val.name || val.title}</strong></td>
            <td>${val.category_name || "Uncategorized"}</td>
            <td>$${parseFloat(val.cost_price || 0).toFixed(2)}</td>
            <td>$${parseFloat(val.sale_price || val.price || 0).toFixed(2)}</td>
            <td><span class="badge ${badgeClass}">${val.quantity}</span></td>
            <td>
                <div class="btn">
                    <button class="update" onclick="editProduct(${val.id})">Edit</button>
                    <button class="delete" onclick="deleteProduct(${val.id})">Delete</button>
                </div>
            </td>
        `;
        productTableBody.appendChild(tr);
    });
}

function deleteProduct(id) {
    if (confirm("Are you sure you want to delete this product?")) {
        productsArr = productsArr.filter(p => p.id !== id);
        saveStoreData("sf_products", productsArr);
        showProducts(productsArr);
    }
}

function editProduct(id) {
    const prod = productsArr.find(p => p.id === id);
    if (!prod) return;

    if (document.getElementById("productSku")) document.getElementById("productSku").value = prod.sku || "";
    if (document.getElementById("productName")) document.getElementById("productName").value = prod.name || prod.title;
    if (document.getElementById("productCategory")) document.getElementById("productCategory").value = prod.category_id || "";
    if (document.getElementById("productSupplier")) document.getElementById("productSupplier").value = prod.supplier_id || "";
    if (document.getElementById("productCost")) document.getElementById("productCost").value = prod.cost_price || "";
    if (document.getElementById("productPrice")) document.getElementById("productPrice").value = prod.sale_price || prod.price || "";
    if (document.getElementById("productQty")) document.getElementById("productQty").value = prod.quantity || "";
    if (document.getElementById("productReorder")) document.getElementById("productReorder").value = prod.reorder_level || "";

    editId = id;
    f = true;
    if (productSubmitBtn) productSubmitBtn.innerText = "Update Product";
}

if (productForm) {
    populateCategorySupplierDropdowns();
    showProducts(productsArr);

    productForm.addEventListener("submit", (e) => {
        e.preventDefault();

        const sku = document.getElementById("productSku").value.trim();
        const name = document.getElementById("productName").value.trim();
        const catId = parseInt(document.getElementById("productCategory").value) || null;
        const suppId = parseInt(document.getElementById("productSupplier").value) || null;
        const cost = parseFloat(document.getElementById("productCost").value) || 0;
        const price = parseFloat(document.getElementById("productPrice").value) || 0;
        const qty = parseInt(document.getElementById("productQty").value) || 0;
        const reorder = parseInt(document.getElementById("productReorder").value) || 10;

        if (name == "") {
            alert("Please enter a product name.");
            return;
        }

        const catObj = categoriesArr.find(c => c.id === catId);
        const suppObj = suppliersArr.find(s => s.id === suppId);

        if (f) {
            productsArr = productsArr.map(p => {
                if (p.id === editId) {
                    return {
                        ...p,
                        sku: sku || p.sku,
                        name: name,
                        title: name,
                        category_id: catId,
                        category_name: catObj ? catObj.name : "Uncategorized",
                        supplier_id: suppId,
                        supplier_name: suppObj ? suppObj.name : "N/A",
                        cost_price: cost,
                        sale_price: price,
                        price: price,
                        quantity: qty,
                        reorder_level: reorder
                    };
                }
                return p;
            });
            f = false;
            editId = null;
            if (productSubmitBtn) productSubmitBtn.innerText = "Add Product";
            alert("Product Updated Successfully");
        } else {
            const newProd = {
                id: Date.now(),
                sku: sku || "PRD-" + Math.floor(100 + Math.random() * 900),
                name: name,
                title: name,
                category_id: catId,
                category_name: catObj ? catObj.name : "Uncategorized",
                supplier_id: suppId,
                supplier_name: suppObj ? suppObj.name : "N/A",
                cost_price: cost,
                sale_price: price,
                price: price,
                quantity: qty,
                reorder_level: reorder
            };
            productsArr.push(newProd);
            alert("Product Created Successfully");
        }

        saveStoreData("sf_products", productsArr);
        showProducts(productsArr);
        productForm.reset();
    });
}

// ===== 4. CATEGORIES CONTROLLER =====
const categoryForm = document.getElementById("categoryForm");
const categoryTableBody = document.getElementById("categoryTableBody");

function showCategories(data) {
    if (!categoryTableBody) return;
    categoryTableBody.innerHTML = "";

    data.forEach((cat) => {
        const prodCount = productsArr.filter(p => p.category_id === cat.id).length;
        const tr = document.createElement("tr");
        tr.innerHTML = `
            <td>#CAT-${cat.id}</td>
            <td><strong>${cat.name}</strong></td>
            <td>${cat.description || "No description"}</td>
            <td><span class="badge badge-info">${prodCount} Products</span></td>
            <td>
                <div class="btn">
                    <button class="delete" onclick="deleteCategory(${cat.id})">Delete</button>
                </div>
            </td>
        `;
        categoryTableBody.appendChild(tr);
    });
}

function deleteCategory(id) {
    if (confirm("Are you sure you want to delete this category?")) {
        categoriesArr = categoriesArr.filter(c => c.id !== id);
        saveStoreData("sf_categories", categoriesArr);
        showCategories(categoriesArr);
    }
}

if (categoryForm) {
    showCategories(categoriesArr);

    categoryForm.addEventListener("submit", (e) => {
        e.preventDefault();
        const name = document.getElementById("catName").value.trim();
        const desc = document.getElementById("catDesc").value.trim();

        if (name == "") {
            alert("Please enter a category name");
            return;
        }

        categoriesArr.push({ id: Date.now(), name: name, description: desc });
        saveStoreData("sf_categories", categoriesArr);
        showCategories(categoriesArr);
        categoryForm.reset();
        alert("Category Added Successfully");
    });
}

// ===== 5. INVENTORY STOCK ADJUSTMENT CONTROLLER =====
const inventoryForm = document.getElementById("inventoryForm");
const inventoryTableBody = document.getElementById("inventoryTableBody");

function populateInventoryProductSelect() {
    const select = document.getElementById("inventoryProductSelect");
    if (!select) return;

    select.innerHTML = `<option value="">Select Product</option>`;
    productsArr.forEach(p => {
        select.innerHTML += `<option value="${p.id}">${p.name || p.title} (Stock: ${p.quantity})</option>`;
    });
}

function showInventoryLogs(data) {
    if (!inventoryTableBody) return;
    inventoryTableBody.innerHTML = "";

    data.forEach((log) => {
        const badgeClass = log.type === "IN" ? "badge-success" : "badge-warning";
        const tr = document.createElement("tr");
        tr.innerHTML = `
            <td>#LOG-${log.id}</td>
            <td><strong>${log.product_name}</strong></td>
            <td><span class="badge ${badgeClass}">${log.type}</span></td>
            <td>${log.quantity}</td>
            <td>${log.notes || "-"}</td>
            <td>${log.created_at || "Just now"}</td>
        `;
        inventoryTableBody.appendChild(tr);
    });
}

if (inventoryForm) {
    populateInventoryProductSelect();
    showInventoryLogs(inventoryLogsArr);

    inventoryForm.addEventListener("submit", (e) => {
        e.preventDefault();
        const prodId = parseInt(document.getElementById("inventoryProductSelect").value);
        const type = document.getElementById("inventoryType").value;
        const qty = parseInt(document.getElementById("inventoryQty").value);
        const notes = document.getElementById("inventoryNotes").value.trim();

        if (!prodId || !qty) {
            alert("Please select a product and enter a valid quantity.");
            return;
        }

        const prod = productsArr.find(p => p.id === prodId);
        if (!prod) return;

        if (type === "OUT" && prod.quantity < qty) {
            alert("Error: Cannot remove more stock than available!");
            return;
        }

        // Adjust product stock
        prod.quantity = type === "IN" ? prod.quantity + qty : prod.quantity - qty;
        saveStoreData("sf_products", productsArr);

        // Add log entry
        const newLog = {
            id: Date.now(),
            product_id: prodId,
            product_name: prod.name || prod.title,
            sku: prod.sku,
            type: type,
            quantity: qty,
            notes: notes,
            created_at: new Date().toLocaleString()
        };
        inventoryLogsArr.unshift(newLog);
        saveStoreData("sf_inventory_logs", inventoryLogsArr);

        showInventoryLogs(inventoryLogsArr);
        populateInventoryProductSelect();
        inventoryForm.reset();
        alert("Stock Adjustment Saved Successfully!");
    });
}

// ===== 6. SALES CONTROLLER =====
const salesForm = document.getElementById("salesForm");
const salesTableBody = document.getElementById("salesTableBody");

function populateSalesDropdowns() {
    const custSelect = document.getElementById("saleCustomerSelect");
    const prodSelect = document.getElementById("saleProductSelect");

    if (custSelect) {
        custSelect.innerHTML = `<option value="">Select Customer</option>`;
        customersArr.forEach(c => custSelect.innerHTML += `<option value="${c.id}">${c.name}</option>`);
    }
    if (prodSelect) {
        prodSelect.innerHTML = `<option value="">Select Product</option>`;
        productsArr.forEach(p => prodSelect.innerHTML += `<option value="${p.id}">${p.name || p.title} ($${p.sale_price || p.price})</option>`);
    }
}

function showSales(data) {
    if (!salesTableBody) return;
    salesTableBody.innerHTML = "";

    data.forEach((s) => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
            <td>#SL-${s.id}</td>
            <td><strong>${s.customer_name}</strong></td>
            <td>$${parseFloat(s.total_amount).toFixed(2)}</td>
            <td><span class="badge badge-success">${s.payment_status}</span></td>
            <td>${s.sale_date || "Today"}</td>
        `;
        salesTableBody.appendChild(tr);
    });
}

if (salesForm) {
    populateSalesDropdowns();
    showSales(salesArr);

    salesForm.addEventListener("submit", (e) => {
        e.preventDefault();
        const custId = parseInt(document.getElementById("saleCustomerSelect").value);
        const prodId = parseInt(document.getElementById("saleProductSelect").value);
        const qty = parseInt(document.getElementById("saleQty").value);

        if (!custId || !prodId || !qty) {
            alert("Please select customer, product, and quantity.");
            return;
        }

        const prod = productsArr.find(p => p.id === prodId);
        const cust = customersArr.find(c => c.id === custId);

        if (prod.quantity < qty) {
            alert("Insufficient product stock for this sale!");
            return;
        }

        const total = (prod.sale_price || prod.price) * qty;

        // Auto-decrement product stock
        prod.quantity -= qty;
        saveStoreData("sf_products", productsArr);

        // Add Sale Record
        const newSale = {
            id: Date.now(),
            customer_id: custId,
            customer_name: cust.name,
            total_amount: total,
            payment_status: "Paid",
            sale_date: new Date().toLocaleString()
        };
        salesArr.unshift(newSale);
        saveStoreData("sf_sales", salesArr);

        // Log Stock Out Movement
        inventoryLogsArr.unshift({
            id: Date.now(),
            product_id: prodId,
            product_name: prod.name || prod.title,
            type: "OUT",
            quantity: qty,
            notes: `Sale #${newSale.id} to ${cust.name}`,
            created_at: new Date().toLocaleString()
        });
        saveStoreData("sf_inventory_logs", inventoryLogsArr);

        showSales(salesArr);
        populateSalesDropdowns();
        salesForm.reset();
        alert("Sale Transaction Recorded Successfully!");
    });
}

// ===== 7. PURCHASES CONTROLLER =====
const purchaseForm = document.getElementById("purchaseForm");
const purchaseTableBody = document.getElementById("purchaseTableBody");

function populatePurchaseDropdowns() {
    const suppSelect = document.getElementById("purchaseSupplierSelect");
    const prodSelect = document.getElementById("purchaseProductSelect");

    if (suppSelect) {
        suppSelect.innerHTML = `<option value="">Select Supplier</option>`;
        suppliersArr.forEach(s => suppSelect.innerHTML += `<option value="${s.id}">${s.name}</option>`);
    }
    if (prodSelect) {
        prodSelect.innerHTML = `<option value="">Select Product</option>`;
        productsArr.forEach(p => prodSelect.innerHTML += `<option value="${p.id}">${p.name || p.title}</option>`);
    }
}

function showPurchases(data) {
    if (!purchaseTableBody) return;
    purchaseTableBody.innerHTML = "";

    data.forEach((p) => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
            <td>#PO-${p.id}</td>
            <td><strong>${p.supplier_name}</strong></td>
            <td>$${parseFloat(p.total_amount).toFixed(2)}</td>
            <td><span class="badge badge-info">${p.status}</span></td>
            <td>${p.purchase_date || "Today"}</td>
        `;
        purchaseTableBody.appendChild(tr);
    });
}

if (purchaseForm) {
    populatePurchaseDropdowns();
    showPurchases(purchasesArr);

    purchaseForm.addEventListener("submit", (e) => {
        e.preventDefault();
        const suppId = parseInt(document.getElementById("purchaseSupplierSelect").value);
        const prodId = parseInt(document.getElementById("purchaseProductSelect").value);
        const qty = parseInt(document.getElementById("purchaseQty").value);
        const cost = parseFloat(document.getElementById("purchaseCost").value);

        if (!suppId || !prodId || !qty || !cost) {
            alert("Please complete all purchase form fields.");
            return;
        }

        const prod = productsArr.find(p => p.id === prodId);
        const supp = suppliersArr.find(s => s.id === suppId);

        const total = cost * qty;

        // Auto-increment product stock
        prod.quantity += qty;
        prod.cost_price = cost;
        saveStoreData("sf_products", productsArr);

        // Add Purchase Record
        const newPur = {
            id: Date.now(),
            supplier_id: suppId,
            supplier_name: supp.name,
            total_amount: total,
            status: "Completed",
            purchase_date: new Date().toLocaleString()
        };
        purchasesArr.unshift(newPur);
        saveStoreData("sf_purchases", purchasesArr);

        // Log Stock In Movement
        inventoryLogsArr.unshift({
            id: Date.now(),
            product_id: prodId,
            product_name: prod.name || prod.title,
            type: "IN",
            quantity: qty,
            notes: `Purchase #${newPur.id} from ${supp.name}`,
            created_at: new Date().toLocaleString()
        });
        saveStoreData("sf_inventory_logs", inventoryLogsArr);

        showPurchases(purchasesArr);
        populatePurchaseDropdowns();
        purchaseForm.reset();
        alert("Purchase Order Recorded & Stock Updated!");
    });
}

// ===== 8. CUSTOMERS CONTROLLER =====
const customerForm = document.getElementById("customerForm");
const customerTableBody = document.getElementById("customerTableBody");

function showCustomers(data) {
    if (!customerTableBody) return;
    customerTableBody.innerHTML = "";

    data.forEach((c) => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
            <td>#CUST-${c.id}</td>
            <td><strong>${c.name}</strong></td>
            <td>${c.email || "-"}</td>
            <td>${c.phone || "-"}</td>
            <td>${c.address || "-"}</td>
            <td>
                <div class="btn">
                    <button class="delete" onclick="deleteCustomer(${c.id})">Delete</button>
                </div>
            </td>
        `;
        customerTableBody.appendChild(tr);
    });
}

function deleteCustomer(id) {
    if (confirm("Delete customer record?")) {
        customersArr = customersArr.filter(c => c.id !== id);
        saveStoreData("sf_customers", customersArr);
        showCustomers(customersArr);
    }
}

if (customerForm) {
    showCustomers(customersArr);

    customerForm.addEventListener("submit", (e) => {
        e.preventDefault();
        const name = document.getElementById("custName").value.trim();
        const email = document.getElementById("custEmail").value.trim();
        const phone = document.getElementById("custPhone").value.trim();
        const address = document.getElementById("custAddress").value.trim();

        if (name == "") {
            alert("Please enter customer name");
            return;
        }

        customersArr.push({ id: Date.now(), name, email, phone, address });
        saveStoreData("sf_customers", customersArr);
        showCustomers(customersArr);
        customerForm.reset();
        alert("Customer Added Successfully");
    });
}

// ===== 9. SUPPLIERS CONTROLLER =====
const supplierForm = document.getElementById("supplierForm");
const supplierTableBody = document.getElementById("supplierTableBody");

function showSuppliers(data) {
    if (!supplierTableBody) return;
    supplierTableBody.innerHTML = "";

    data.forEach((s) => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
            <td>#SUPP-${s.id}</td>
            <td><strong>${s.name}</strong></td>
            <td>${s.contact_person || "-"}</td>
            <td>${s.email || "-"}</td>
            <td>${s.phone || "-"}</td>
            <td>${s.address || "-"}</td>
            <td>
                <div class="btn">
                    <button class="delete" onclick="deleteSupplier(${s.id})">Delete</button>
                </div>
            </td>
        `;
        supplierTableBody.appendChild(tr);
    });
}

function deleteSupplier(id) {
    if (confirm("Delete supplier record?")) {
        suppliersArr = suppliersArr.filter(s => s.id !== id);
        saveStoreData("sf_suppliers", suppliersArr);
        showSuppliers(suppliersArr);
    }
}

if (supplierForm) {
    showSuppliers(suppliersArr);

    supplierForm.addEventListener("submit", (e) => {
        e.preventDefault();
        const name = document.getElementById("suppName").value.trim();
        const contact = document.getElementById("suppContact").value.trim();
        const email = document.getElementById("suppEmail").value.trim();
        const phone = document.getElementById("suppPhone").value.trim();
        const address = document.getElementById("suppAddress").value.trim();

        if (name == "") {
            alert("Please enter supplier name");
            return;
        }

        suppliersArr.push({ id: Date.now(), name, contact_person: contact, email, phone, address });
        saveStoreData("sf_suppliers", suppliersArr);
        showSuppliers(suppliersArr);
        supplierForm.reset();
        alert("Supplier Added Successfully");
    });
}

// ===== 10. REPORTS CONTROLLER =====
function initReports() {
    const totalRevEl = document.getElementById("reportTotalRev");
    const totalExpEl = document.getElementById("reportTotalExp");
    const netProfitEl = document.getElementById("reportNetProfit");
    const stockValEl = document.getElementById("reportStockValue");

    const totalRev = salesArr.reduce((acc, s) => acc + (parseFloat(s.total_amount) || 0), 0);
    const totalExp = purchasesArr.reduce((acc, p) => acc + (parseFloat(p.total_amount) || 0), 0);
    const stockValue = productsArr.reduce((acc, p) => acc + ((p.quantity || 0) * (p.cost_price || 0)), 0);
    const netProfit = totalRev - totalExp;

    if (totalRevEl) totalRevEl.innerText = "$" + totalRev.toFixed(2);
    if (totalExpEl) totalExpEl.innerText = "$" + totalExp.toFixed(2);
    if (netProfitEl) netProfitEl.innerText = "$" + netProfit.toFixed(2);
    if (stockValEl) stockValEl.innerText = "$" + stockValue.toFixed(2);
}

// ===== AUTO-RUN CONTROLLERS ON DOM LOAD =====
document.addEventListener("DOMContentLoaded", () => {
    initDashboard();
    initReports();
});