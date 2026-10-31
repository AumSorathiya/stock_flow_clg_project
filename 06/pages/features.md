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


---

### 12. 🔍 Universal Instant Search, Filters & Consistent ID Tracking
- **Interactive Listing Filters**: Every data listing (Products, Categories, Inventory, Sales, Purchases, Customers, Suppliers, Users, Settings, Reports, Dashboard) features real-time search and multi-criteria filters.
- **Dynamic Client-Side Filtering**: Filters records by keyword, category, status, role, movement type, supplier, customer, and price/stock sorting with instant updates and live counter badges.
- **Unified ID Architecture**: Every catalog and transactional listing begins with a clear, standardized `ID` column (`#ID`), ensuring full traceability from database primary keys to UI listings and form dropdowns.
- **Dual Invoice/PO Identifiers**: Sales and Purchases show primary database `ID` alongside official formatted invoice/PO codes (`#SL-0001`, `#PO-0001`).


---

### 13. 🎨 NextGen Enterprise SaaS UI & Interactive Analytics
- **Modern Typography & Design System**: Powered by Google's `Plus Jakarta Sans` and monospace `JetBrains Mono` font stacks, sleek CSS custom properties, and multi-tier elevation shadows.
- **Real-Time Dynamic Telemetry Navbar**:
  - Live Digital Clock: Synchronizes current date and seconds in real-time.
  - Live Database Heartbeat Pill: Displays active MySQL port with breathing green pulse dot.
  - Quick Action Shortcuts: Instant one-click triggers for `+ Sale` and `+ Restock`.
- **Interactive Chart.js Visualizations**:
  - Trailing 6-Month Revenue vs Outlay dual bar/area chart.
  - Stock Health distribution donut chart (Healthy vs Low Stock vs Out of Stock) with fallback accessible rendering.
- **Universal 1-Click CSV Exporter**: Every data table includes an `📥 Export CSV` button that dynamically compiles filtered table data into a spreadsheet file for offline audit.
- **Collapsible Form Drawers**: Toggleable creation cards (`➕ Add New ...` / `✕ Close Form`) that tuck away forms to maximize tabular data viewports.
- **Financial Gauges & Progress Bars**: Visual profit margin efficiency meters and proportional contribution bars in Reports.
- **NextGen Authentication Portal**: Ambient mesh background glow, glassmorphism auth container, show/hide password toggle, and one-click demo credentials chips with visual feedback.
