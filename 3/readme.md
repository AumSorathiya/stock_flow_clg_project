For a clean and scalable Inventory Management System, I recommend the following database schema.

---

# 1. users

| Field      | Type                      | Attributes                                            |
| ---------- | ------------------------- | ----------------------------------------------------- |
| id         | INT                       | PRIMARY KEY, AUTO_INCREMENT                           |
| username   | VARCHAR(50)               | UNIQUE, NOT NULL                                      |
| password   | VARCHAR(255)              | NOT NULL (Hashed Password)                            |
| full_name  | VARCHAR(100)              | NOT NULL                                              |
| email      | VARCHAR(100)              | UNIQUE                                                |
| role       | ENUM('admin','manager')   | DEFAULT 'admin'                                       |
| status     | ENUM('active','inactive') | DEFAULT 'active'                                      |
| created_at | TIMESTAMP                 | DEFAULT CURRENT_TIMESTAMP                             |
| updated_at | TIMESTAMP                 | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

---

# 2. categories

| Field         | Type                      | Attributes                  |
| ------------- | ------------------------- | --------------------------- |
| id            | INT                       | PRIMARY KEY, AUTO_INCREMENT |
| category_name | VARCHAR(100)              | UNIQUE, NOT NULL            |
| description   | TEXT                      | NULL                        |
| status        | ENUM('active','inactive') | DEFAULT 'active'            |
| created_at    | TIMESTAMP                 | DEFAULT CURRENT_TIMESTAMP   |

---

# 3. suppliers

| Field          | Type                      | Attributes                  |
| -------------- | ------------------------- | --------------------------- |
| id             | INT                       | PRIMARY KEY, AUTO_INCREMENT |
| supplier_name  | VARCHAR(150)              | NOT NULL                    |
| contact_person | VARCHAR(100)              | NULL                        |
| phone          | VARCHAR(20)               | NOT NULL                    |
| email          | VARCHAR(100)              | NULL                        |
| address        | TEXT                      | NULL                        |
| city           | VARCHAR(100)              | NULL                        |
| state          | VARCHAR(100)              | NULL                        |
| pincode        | VARCHAR(10)               | NULL                        |
| gst_number     | VARCHAR(20)               | NULL                        |
| status         | ENUM('active','inactive') | DEFAULT 'active'            |
| created_at     | TIMESTAMP                 | DEFAULT CURRENT_TIMESTAMP   |

---

# 4. products

| Field          | Type                      | Attributes                                            |
| -------------- | ------------------------- | ----------------------------------------------------- |
| id             | INT                       | PRIMARY KEY, AUTO_INCREMENT                           |
| product_name   | VARCHAR(150)              | NOT NULL                                              |
| sku            | VARCHAR(50)               | UNIQUE                                                |
| category_id    | INT                       | FOREIGN KEY                                           |
| supplier_id    | INT                       | FOREIGN KEY                                           |
| purchase_price | DECIMAL(10,2)             | NOT NULL                                              |
| selling_price  | DECIMAL(10,2)             | NOT NULL                                              |
| quantity       | INT                       | DEFAULT 0                                             |
| minimum_stock  | INT                       | DEFAULT 5                                             |
| unit           | VARCHAR(20)               | e.g., Piece, Kg, Box                                  |
| description    | TEXT                      | NULL                                                  |
| image          | VARCHAR(255)              | NULL                                                  |
| status         | ENUM('active','inactive') | DEFAULT 'active'                                      |
| created_at     | TIMESTAMP                 | DEFAULT CURRENT_TIMESTAMP                             |
| updated_at     | TIMESTAMP                 | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

---

# 5. stock_transactions

| Field            | Type             | Attributes                  |
| ---------------- | ---------------- | --------------------------- |
| id               | INT              | PRIMARY KEY, AUTO_INCREMENT |
| product_id       | INT              | FOREIGN KEY                 |
| transaction_type | ENUM('IN','OUT') | NOT NULL                    |
| quantity         | INT              | NOT NULL                    |
| previous_stock   | INT              | NOT NULL                    |
| current_stock    | INT              | NOT NULL                    |
| remarks          | VARCHAR(255)     | NULL                        |
| created_by       | INT              | FOREIGN KEY (users.id)      |
| created_at       | TIMESTAMP        | DEFAULT CURRENT_TIMESTAMP   |

---

# Database Relationships

```text
users (1)
      │
      └──── created_by
              │
              ▼
stock_transactions

categories (1)
      │
      ▼
products

suppliers (1)
      │
      ▼
products

products (1)
      │
      ▼
stock_transactions
```

---

# Foreign Keys

### products

* `category_id → categories.id`
* `supplier_id → suppliers.id`

### stock_transactions

* `product_id → products.id`
* `created_by → users.id`

---

# Total Tables

| Table              | Purpose                    |
| ------------------ | -------------------------- |
| users              | Login & authentication     |
| categories         | Product categories         |
| suppliers          | Supplier information       |
| products           | Product inventory          |
| stock_transactions | Inventory movement history |

---

## Recommended Future Tables (Optional)

If you later expand the project, add these tables:

| Table           | Purpose                     |
| --------------- | --------------------------- |
| customers       | Customer details            |
| purchase_orders | Purchase orders             |
| purchase_items  | Products in purchase orders |
| sales           | Sales invoices              |
| sales_items     | Products sold               |
| notifications   | Low stock alerts            |
| activity_logs   | User activity history       |

This design is normalized, easy to understand, and appropriate for a college project while leaving room for future enhancements like sales, purchases, barcode scanning, and reporting.