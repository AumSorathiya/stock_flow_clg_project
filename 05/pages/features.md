Here is the complete list of features and functionalities provided by the **Stock Flow Inventory Management System**:

---

### 1. 🔑 Authentication & Session Security
- **Secure SQL Login**: Authenticates users against the `users` MySQL database table.
- **Password Encryption**: Supports secure password verification (`password_hash` / `password_verify`).
- **Route Guarding**: Restricts unauthorized access—users are redirected to `index.php` if not logged in.
- **Session Logout**: Securely clears session cookies and logs users out.

---

### 2. 📊 Executive Dashboard (`pages/dashboard.php`)
- **Live KPI Metrics**: Displays real-time aggregates for:
  - 💵 **Total Sales ($)**
  - 🛒 **Total Purchases ($)**
  - 👥 **Total Customers**
  - 🏬 **Total Suppliers**
- **Low Stock Warnings**: Banner highlighting items at or below their safety stock threshold.
- **Recent Activity Feed**: Summary table of recent sales transactions.

---

### 3. 📦 Product Catalog Management (`pages/products.php`)
- **Product CRUD**: Create, Read, Update, and Delete products in the inventory.
- **SKU & Categorization**: Unique SKU tracking and category linking via dynamic dropdowns.
- **Dual Pricing**: Tracks both **Selling Price ($)** and **Unit Cost ($)**.
- **Stock Thresholds**: Defines **Current Quantity** and **Minimum Stock Alert Thresholds**.

---

### 4. 🏷️ Category Management (`pages/categories.php`)
- **Category CRUD**: Add, edit, or remove product categories.
- **Product Counter**: Shows the total number of active products linked to each category.

---

### 5. 📑 Inventory Control & Audit Logs (`pages/inventory.php`)
- **Manual Stock Adjustments**:
  - **Stock In (+)**: Adds incoming stock/restocks.
  - **Stock Out (-)**: Deducts stock for damage/disposal.
  - **Direct Reset**: Sets exact stock counts manually.
- **Stock Status Badges**: Visual indicators:
  - 🟢 `IN STOCK`
  - 🟡 `LOW STOCK`
  - 🔴 `OUT OF STOCK`
- **Audit Logging**: Keeps timestamped logs of every stock movement with custom reasons.

---

### 6. 💰 Sales Transaction System (`pages/sales.php`)
- **Sales Order Entry**: Select product, customer, quantity, and unit price.
- **Price Auto-Fill**: Automatically populates selling price when a product is selected.
- **Automatic Stock Deduction**: Automatically reduces product stock count in the database upon sale.
- **Sales History Ledger**: Comprehensive table tracking all past sales.

---

### 7. 🛍️ Purchase Orders & Procurement (`pages/purchases.php`)
- **Purchase Order Entry**: Link purchases to specific suppliers with quantity and unit cost.
- **Automatic Stock Restock**: Automatically increases inventory stock when a purchase order is submitted.
- **Purchase History Ledger**: Historical view of all restocking orders.

---

### 8. 👥 Customer Management (`pages/customers.php`)
- **Customer Directory**: Add, update, or remove client contact details (Name, Email, Phone, Address).
- **Spending Analytics**: Tracks total orders placed and cumulative spending ($) per customer.

---

### 9. 🏬 Supplier Management (`pages/suppliers.php`)
- **Supplier Directory**: Add, update, or remove vendor details (Contact Person, Company Name, Phone, Email, Address).
- **Vendor Analytics**: Tracks total purchase orders placed and total procurement cost per supplier.

---

### 10. 📈 Financial Reports & Insights (`pages/reports.php`)
- **Gross Profit Tracking**: Calculates Net Profit (`Total Sales Revenue - Total Purchases Cost`).
- **Inventory Valuation**: Calculates total asset value of stored stock (`SUM(price * quantity)`).
- **Top Selling Products**: Ranks top products by total units sold and revenue generated.

---

### 11. ⚙️ System Settings & Profile (`pages/settings.php`)
- **Profile Settings**: Update Administrator Name and Email.
- **Password Security**: Utility to update the admin account password.
- **Database Status Indicator**: Displays MySQL connection health and system metadata.