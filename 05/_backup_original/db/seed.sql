-- Stock Flow Sample Seed Data

USE `stock_flow`;

-- Default Admin User (username: admin, password: admin (hashed using password_hash or plain fallback check))
INSERT INTO `users` (`username`, `password`, `full_name`, `email`, `role`) VALUES
('admin', 'admin123', 'System Administrator', 'admin@stockflow.com', 'admin');
-- Note: The hashed password corresponds to 'admin' using password_hash('admin', PASSWORD_BCRYPT)

-- Sample Categories
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Electronics', 'Gadgets, devices, and accessories'),
(2, 'Furniture', 'Office and home furniture'),
(3, 'Stationery', 'Office supplies and paper goods'),
(4, 'Apparel', 'Clothing and footwear');

-- Sample Products
INSERT INTO `products` (`id`, `sku`, `name`, `category_id`, `price`, `cost`, `quantity`, `min_stock`, `description`) VALUES
(1, 'PROD-001', 'Wireless Mouse', 1, 29.99, 15.00, 45, 10, 'Ergonomic optical wireless mouse'),
(2, 'PROD-002', 'Mechanical Keyboard', 1, 89.99, 45.00, 20, 5, 'RGB backlit mechanical keyboard'),
(3, 'PROD-003', 'Office Chair', 2, 199.99, 110.00, 8, 3, 'Adjustable ergonomic mesh chair'),
(4, 'PROD-004', 'A4 Printing Paper (500 sheets)', 3, 6.50, 3.20, 150, 25, 'High quality white copy paper'),
(5, 'PROD-005', 'USB-C Cable 2m', 1, 12.99, 4.00, 3, 10, 'Fast charging braided USB-C cable');

-- Sample Customers
INSERT INTO `customers` (`id`, `name`, `email`, `phone`, `address`) VALUES
(1, 'John Smith', 'john@example.com', '555-0192', '123 Main St, New York, NY'),
(2, 'Emma Watson', 'emma@example.com', '555-0144', '456 Oak Ave, San Francisco, CA'),
(3, 'Acme Corporation', 'billing@acme.com', '555-0888', '789 Business Pkwy, Chicago, IL');

-- Sample Suppliers
INSERT INTO `suppliers` (`id`, `name`, `company`, `email`, `phone`, `address`) VALUES
(1, 'Tech Supplies Inc', 'Tech Supplies Ltd', 'sales@techsupplies.com', '800-555-0100', '10 Tech Plaza, Austin, TX'),
(2, 'Global Office Co', 'Global Office Goods', 'info@globaloffice.com', '800-555-0200', '50 Industrial Way, Columbus, OH');

-- Sample Sales
INSERT INTO `sales` (`product_id`, `customer_id`, `quantity`, `unit_price`, `total_amount`) VALUES
(1, 1, 2, 29.99, 59.98),
(2, 2, 1, 89.99, 89.99),
(4, 3, 10, 6.50, 65.00);

-- Sample Purchases
INSERT INTO `purchases` (`product_id`, `supplier_id`, `quantity`, `unit_cost`, `total_amount`) VALUES
(1, 1, 50, 15.00, 750.00),
(2, 1, 25, 45.00, 1125.00),
(3, 2, 10, 110.00, 1100.00);

-- Sample Inventory Logs
INSERT INTO `inventory_logs` (`product_id`, `type`, `quantity`, `reason`) VALUES
(1, 'in', 50, 'Initial Stock Purchase'),
(2, 'in', 25, 'Initial Stock Purchase'),
(1, 'out', 2, 'Sale #1'),
(2, 'out', 1, 'Sale #2');
