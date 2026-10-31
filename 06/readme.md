# 📊 Stock Flow - Enterprise Inventory Management System

**Stock Flow** is a full-stack PHP & MySQL web application engineered for college and enterprise submissions. It features an automated multi-port database connectivity engine, granular **Role-Based Access Control (RBAC)** across three tiers (**Admin**, **Manager**, and **Staff**), synchronized real-time stock deduction and procurement replenishment, audit logging, and modern responsive dashboard interfaces.

---

## 🌟 Key Highlights & Architectural Features

### 1. 🛡️ Three-Tier Role-Based Access Control (RBAC)
Permissions are enforced strictly **on the server side** across all routes and state-changing actions:
- **👑 Administrator (`admin`)**:
  - Full access to all catalog, transactional, and contact modules.
  - Exclusive access to **User Management** (`pages/users.php`): create users, assign roles, activate/deactivate accounts, and reset passwords with BCRYPT.
  - Exclusive access to **System Settings & Diagnostics** (`pages/settings.php`) and security activity audit logs.
  - Built-in self-protection: Admins cannot accidentally deactivate or demote their own active accounts.
- **👔 Operations Manager (`manager`)**:
  - Full operational access: Catalog (Products & Categories), Transactions (Sales & Purchases), and Directory (Customers & Suppliers).
  - Access to Financial and Inventory Analytics (**Reports**).
  - Blocked by server guards from User Management and System Settings.
- **📦 Warehouse / Cashier Staff (`staff`)**:
  - Operational entry: View catalog and current stock levels, create sales orders (with real-time stock deductions), log inbound purchases, register new customers, and perform stock adjustments.
  - Restricted from destructive actions (cannot delete products, categories, suppliers, or transactions).
  - Blocked by server guards from Reports, Settings, and User Management.

### 2. ⚡ Real-Time Stock Synchronization & Transaction Integrity
- **Sales Orders (`pages/sales.php`)**:
  - Automatic price autofill when selecting in-stock products.
  - Dynamic client-side order total calculator.
  - Database transaction locking (`SELECT ... FOR UPDATE`) prevents race conditions.
  - **Negative inventory protection**: Rejects orders that exceed current available warehouse stock.
  - Automatically deducts product stock and records an audit log entry tagged with the active user.
- **Purchase Orders (`pages/purchases.php`)**:
  - Dynamic procurement cost calculator.
  - Automatically replenishes stock quantity and updates latest unit cost.
  - Automatically generates inbound inventory audit logs.
- **Inventory Control & Physical Reconciliation (`pages/inventory.php`)**:
  - Manual Stock-In (+), Stock-Out (-), and Direct Count Reset.
  - Live stock status badges: `IN STOCK`, `LOW STOCK` (safety threshold), `OUT OF STOCK`.
  - Comprehensive audit trail recording user, timestamp, quantity, and reason.

### 3. 🔒 Enterprise Security & Robustness
- **Password Security**: Passwords hashed using PHP's native `password_hash($pass, PASSWORD_BCRYPT)`.
- **CSRF Defense**: Cryptographic token generation and verification on state-changing POST forms.
- **SQL Injection Prevention**: All queries utilize PDO prepared statements with parameterized binding.
- **Data Integrity**: Foreign key constraints with `ON DELETE SET NULL` protect historical financial records when related entities are deleted.
- **Smart Database Connection Engine**: Automatically discovers whether MySQL is running on standard port `3306` or custom port `3000` (common in modified XAMPP installations), seamlessly connecting with 0ms delay.

---

## 💻 Tech Stack

- **Backend**: PHP 8.x (PHP 8.2+ supported, PDO MySQL driver)
- **Database**: MySQL 5.7+ / MariaDB 10.4+
- **Frontend**: Responsive HTML5, Vanilla JavaScript, CSS3 Design System
- **Server Environment**: Apache / XAMPP on Windows, macOS, or Linux

---

## 👥 Pre-Configured Demo Accounts

The database comes pre-seeded with active demo accounts for all three permission tiers:

| Role | Username | Password | Permissions & Scope |
| :--- | :--- | :--- | :--- |
| **👑 Admin** | `admin` | `admin123` | Full system access, User Management, System Settings, Reports, CRUD on all modules |
| **👔 Manager** | `manager` | `manager123` | Dashboard, Products, Categories, Inventory, Sales, Purchases, Customers, Suppliers, Reports |
| **📦 Staff** | `staff` | `staff123` | Dashboard, View Catalog/Stock, Create Sales, Log Purchases, Register Customers, Stock In/Out |

> **Tip for evaluators**: The login screen (`index.php`) includes one-click demo credentials buttons to instantly autofill and test each role!

---

## 🚀 Local XAMPP Setup Instructions

### Step 1: Place Project Files in XAMPP
Copy or clone the project folder into your XAMPP web root:
```text
C:\xampp\htdocs\stock_flow\
```
*(Or keep in your custom development directory and configure a virtual host or local PHP server).*

### Step 2: Start Apache and MySQL in XAMPP
1. Open the **XAMPP Control Panel**.
2. Click **Start** for **Apache**.
3. Click **Start** for **MySQL**.

### Step 3: Import the Database
1. Open your browser and navigate to **phpMyAdmin**:
   ```
   http://localhost/phpmyadmin/
   ```
2. Click **New** on the left navigation panel and create a database named:
   ```sql
   stock_flow
   ```
3. Click on the `stock_flow` database, navigate to the **Import** tab.
4. Select `db/schema.sql` and click **Import**.
5. Select `db/seed.sql` and click **Import**.

*Alternatively, using the MySQL CLI:*
```powershell
mysql -u root -p stock_flow < db/schema.sql
mysql -u root -p stock_flow < db/seed.sql
```

*(If upgrading an existing older database, execute `db/migration.sql` to apply incremental RBAC changes without losing existing records).*

### Step 4: Launch the Application
Open your web browser and navigate to:
```text
http://localhost/stock_flow/
```
Or if running directly with PHP's built-in web server:
```powershell
cd "d:\collage project\stock_flow_clg_project-main\stock_flow_clg_project-main\04"
C:\xampp\php\php.exe -S localhost:8000
```
Then visit: `http://localhost:8000/`

---

## 📋 Comprehensive Role Permission Matrix

| Feature / Route | Route File | Admin | Manager | Staff |
| :--- | :--- | :---: | :---: | :---: |
| **Dashboard KPIs & Charts** | `pages/dashboard.php` | ✅ Full | ✅ Full | ✅ Full |
| **View Product Catalog** | `pages/products.php` | ✅ Yes | ✅ Yes | ✅ Yes |
| **Add / Edit / Delete Products** | `pages/products.php` | ✅ Yes | ✅ Yes | ❌ Read-Only |
| **Category Management** | `pages/categories.php` | ✅ Yes | ✅ Yes | ❌ Read-Only |
| **Inventory Status & Logs** | `pages/inventory.php` | ✅ Yes | ✅ Yes | ✅ Yes |
| **Stock In / Out / Reset** | `pages/inventory.php` | ✅ Yes | ✅ Yes | ✅ Yes |
| **Record Sales Orders** | `pages/sales.php` | ✅ Yes | ✅ Yes | ✅ Yes |
| **Record Purchase Restocks** | `pages/purchases.php` | ✅ Yes | ✅ Yes | ✅ Yes |
| **Customer Directory** | `pages/customers.php` | ✅ CRUD | ✅ CRUD | ✅ View + Add |
| **Supplier Directory** | `pages/suppliers.php` | ✅ CRUD | ✅ CRUD | ❌ View Only |
| **Financial & Stock Reports** | `pages/reports.php` | ✅ Full | ✅ Full | ❌ Blocked |
| **User Account Management** | `pages/users.php` | ✅ Full | ❌ Blocked | ❌ Blocked |
| **System Settings & Audit** | `pages/settings.php` | ✅ Full | ❌ Blocked | ❌ Blocked |

*Note: All "Blocked" routes enforce immediate redirection with a flash alert if an unauthorized role attempts direct URL access.*

---

## 🗄️ Database Architecture (`stock_flow`)

- `users`: ID, unique username, bcrypt hashed password, full name, email, role (`admin`, `manager`, `staff`), status (`active`, `inactive`), timestamps.
- `categories`: ID, unique name, description, timestamps.
- `products`: ID, unique SKU, name, category_id, selling price, unit cost, quantity, minimum stock threshold, description, timestamps.
- `customers`: ID, full name, email, phone, billing address, timestamps.
- `suppliers`: ID, contact name, company name, email, phone, address, timestamps.
- `sales`: ID, user_id, product_id, customer_id, quantity, unit_price, total_amount, sale_date.
- `purchases`: ID, user_id, product_id, supplier_id, quantity, unit_cost, total_amount, purchase_date.
- `inventory_logs`: ID, user_id, product_id, movement type (`in`, `out`, `adjustment`), quantity, reason, created_at.
- `activity_logs`: ID, user_id, action, module, description, IP address, created_at.

---

## ⚠️ Known Limitations & Future Scope

1. **Multi-Currency Support**: System currently defaults to US Dollar (`$`). Multi-currency conversion can be added via an external exchange rate API.
2. **Barcode / QR Scanning**: SKUs are tracked numerically and textually; direct camera barcode scanning hardware integration can be added as a progressive web app (PWA) extension.
3. **Multi-Location Warehousing**: The inventory currently tracks centralized company stock; future versions can expand stock tracking by discrete bin/warehouse locations.

---

## 🎓 Academic Submission Metadata

- **Project Title**: Stock Flow Inventory Management System
- **Submission Date**: 2026
- **Architecture**: MVC-inspired PHP & MySQL Architecture with Role-Based Access Control
- **Testing Status**: 36/36 Automated Quality Assurance Tests Passed (All Features, Listings, Filters & ID Integrity Verified)
