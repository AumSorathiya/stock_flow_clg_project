Listed directory 03
Listed directory pages
Listed directory script
Listed directory style
Listed directory includes
Listed directory config
Listed directory db
Ran command: `Remove-Item -Path "index.html", "pages\categories.html", "pages\customers.html", "pages\dashbord.html", "pages\inventory.html", "pages\products.html", "pages\purchases.html", "pages\reports.html", "pages\sales.html", "pages\settings.html", "pages\suppliers.html" -Force`
Listed directory pages

### Cleanup Completed & Project File Overview

All 11 obsolete, static HTML files (`index.html`, `pages/*.html`) have been removed from the codebase. The application now runs cleanly as a dedicated **PHP & SQL Inventory Management System**.

---

### Project File Structure & File Descriptions

#### 1. Root Directory (`/`)
- [`index.php`](file:///d:/collage%20project/03/index.php): Application entry point and secure login page. Validates admin credentials against the MySQL database and manages session login.
- [`logout.php`](file:///d:/collage%20project/03/logout.php): Log out handler that destroys user sessions and redirects to the login screen (`index.php`).

---

#### 2. Configuration & Helpers (`config/`)
- [`config/db.php`](file:///d:/collage%20project/03/config/db.php): PDO database connection configuration, global error handling, input sanitization helpers, and session verification routines.

---

#### 3. Database Layer (`db/`)
- [`db/schema.sql`](file:///d:/collage%20project/03/db/schema.sql): Complete MySQL database schema defining tables for `users`, `categories`, `products`, `customers`, `suppliers`, `sales`, `purchases`, and `inventory_logs`.
- [`db/seed.sql`](file:///d:/collage%20project/03/db/seed.sql): Initial demo dataset containing default admin user (`admin` / `admin`), sample products, categories, suppliers, customers, and transaction records.

---

#### 4. Modular Layout Components (`includes/`)
- [`includes/header.php`](file:///d:/collage%20project/03/includes/header.php): Shared HTML `<head>` container, page titles, authentication check, stylesheet linking, and top container wrapper.
- [`includes/sidebar.php`](file:///d:/collage%20project/03/includes/sidebar.php): Left navigation sidebar containing links to all application routes (`Overview`, `Catalog`, `Transactions`, `Contacts`, `Insights`, `Logout`).
- [`includes/footer.php`](file:///d:/collage%20project/03/includes/footer.php): Shared HTML closing tags and JavaScript script attachments.

---

#### 5. Main Application Modules (`pages/`)
- [`pages/dashboard.php`](file:///d:/collage%20project/03/pages/dashboard.php): Main dashboard interface presenting real-time SQL metrics (Total Sales, Total Purchases, Customer Count, Supplier Count, Low Stock Warnings, and Recent Sales Table).
- [`pages/products.php`](file:///d:/collage%20project/03/pages/products.php): Product Catalog Management (Add, Edit, Delete products, SKU, Category selection, Pricing, Cost, and Stock Thresholds).
- [`pages/categories.php`](file:///d:/collage%20project/03/pages/categories.php): Category Management (Add, Edit, Delete categories, and track associated product counts).
- [`pages/inventory.php`](file:///d:/collage%20project/03/pages/inventory.php): Stock Management (Manual Stock-In, Stock-Out, Direct Adjustments, Stock level badges, and Inventory Audit logs).
- [`pages/sales.php`](file:///d:/collage%20project/03/pages/sales.php): Sales Order Entry (Record new customer sales with automatic real-time stock deduction from the database).
- [`pages/purchases.php`](file:///d:/collage%20project/03/pages/purchases.php): Purchase Order Entry (Record new supplier purchases with automatic real-time stock addition to the database).
- [`pages/customers.php`](file:///d:/collage%20project/03/pages/customers.php): Customer Directory (Add, Edit, Delete client records, order counts, and total spending metrics).
- [`pages/suppliers.php`](file:///d:/collage%20project/03/pages/suppliers.php): Supplier Directory (Add, Edit, Delete vendor records, company details, and purchase history).
- [`pages/reports.php`](file:///d:/collage%20project/03/pages/reports.php): Financial Insights (Total Revenue, Total Purchases Cost, Gross Profit calculation, Total Inventory Valuation, Top Sellers, and Low Stock Report).
- [`pages/settings.php`](file:///d:/collage%20project/03/pages/settings.php): Account Profile Management (Update admin credentials/password) and system connection overview.

---

#### 6. Styles & Scripts (`style/`, `script/`)
- [`style/style.css`](file:///d:/collage%20project/03/style/style.css): Main stylesheet defining sidebar layout, dashboard grid cards, form controls, tables, update/delete buttons, and responsive breakpoints.
- [`style/loginstyle.css`](file:///d:/collage%20project/03/style/loginstyle.css): Dedicated stylesheet for the centered login card interface.
- [`script/script.js`](file:///d:/collage%20project/03/script/script.js): Shared JavaScript file handling client-side event helpers, input feedback, and DOM utilities.

Viewed suppliers.html:1-37