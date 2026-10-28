-- Stock Flow Sample Seed Data
-- Default Users:
-- Admin:   username: admin   | password: admin123
-- Manager: username: manager | password: manager123
-- Staff:   username: staff   | password: staff123

USE `stock_flow`;

-- Seed Users with bcrypt hashed passwords
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `status`) VALUES
(1, 'admin', '$2y$10$olkJ6FFnL9FjB9HRAAK/Euk7S1ZcvJEAk3xu9qugwGlFeNs6nykhC', 'System Administrator', 'admin@stockflow.com', 'admin', 'active'),
(2, 'manager', '$2y$10$bKhZpYiWj2pDT97YPmqZVemI3ZG1V5wXL80Wo5k2JOfuC48uj6RRW', 'Operations Manager', 'manager@stockflow.com', 'manager', 'active'),
(3, 'staff', '$2y$10$wb5RddutyThO/oXE2rWYEu37bHw9tqWtrAIwrqqbp/GxH1kVOOScm', 'Warehouse Staff', 'staff@stockflow.com', 'staff', 'active')
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`), `email` = VALUES(`email`), `role` = VALUES(`role`), `status` = VALUES(`status`);

-- Sample Categories
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Electronics', 'Gadgets, devices, and computer accessories'),
(2, 'Furniture', 'Ergonomic office and warehouse furniture'),
(3, 'Stationery', 'Office supplies, printing paper, and desk stationery'),
(4, 'Apparel', 'Company uniforms and protective safety wear')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);

-- Sample Products
INSERT INTO `products` (`id`, `sku`, `name`, `category_id`, `price`, `cost`, `quantity`, `min_stock`, `description`) VALUES
(1, 'PROD-001', 'Wireless Optical Mouse', 1, 29.99, 15.00, 45, 10, 'Ergonomic 2.4GHz wireless optical mouse with USB nano receiver'),
(2, 'PROD-002', 'Mechanical Gaming Keyboard', 1, 89.99, 45.00, 20, 5, 'RGB backlit mechanical keyboard with tactile blue switches'),
(3, 'PROD-003', 'Ergonomic Mesh Chair', 2, 199.99, 110.00, 8, 3, 'High-back mesh executive chair with lumbar support'),
(4, 'PROD-004', 'A4 Premium Paper Ream (500 Sheets)', 3, 6.50, 3.20, 150, 25, '80 GSM multipurpose white laser copy paper'),
(5, 'PROD-005', 'USB-C Fast Charging Cable 2M', 1, 12.99, 4.00, 3, 10, 'Heavy-duty braided USB-C to USB-C charging cable')
ON DUPLICATE KEY UPDATE `price` = VALUES(`price`), `cost` = VALUES(`cost`), `quantity` = VALUES(`quantity`), `min_stock` = VALUES(`min_stock`);

-- Sample Customers
INSERT INTO `customers` (`id`, `name`, `email`, `phone`, `address`) VALUES
(1, 'John Smith', 'john@example.com', '555-0192', '123 Main St, New York, NY 10001'),
(2, 'Emma Watson', 'emma@example.com', '555-0144', '456 Oak Ave, San Francisco, CA 94102'),
(3, 'Acme Global Corp', 'billing@acme.com', '555-0888', '789 Business Pkwy, Suite 300, Chicago, IL 60601')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `email` = VALUES(`email`), `phone` = VALUES(`phone`);

-- Sample Suppliers
INSERT INTO `suppliers` (`id`, `name`, `company`, `email`, `phone`, `address`) VALUES
(1, 'Tech Supplies Inc', 'Tech Supplies Ltd', 'sales@techsupplies.com', '800-555-0100', '10 Tech Plaza, Austin, TX 78701'),
(2, 'Global Office Co', 'Global Office Goods LLC', 'info@globaloffice.com', '800-555-0200', '50 Industrial Way, Columbus, OH 43215')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `company` = VALUES(`company`), `email` = VALUES(`email`);

-- Sample Purchases
INSERT INTO `purchases` (`id`, `user_id`, `product_id`, `supplier_id`, `quantity`, `unit_cost`, `total_amount`) VALUES
(1, 1, 1, 1, 50, 15.00, 750.00),
(2, 2, 2, 1, 25, 45.00, 1125.00),
(3, 1, 3, 2, 10, 110.00, 1100.00)
ON DUPLICATE KEY UPDATE `total_amount` = VALUES(`total_amount`);

-- Sample Sales
INSERT INTO `sales` (`id`, `user_id`, `product_id`, `customer_id`, `quantity`, `unit_price`, `total_amount`) VALUES
(1, 3, 1, 1, 2, 29.99, 59.98),
(2, 3, 2, 2, 1, 89.99, 89.99),
(3, 2, 4, 3, 10, 6.50, 65.00)
ON DUPLICATE KEY UPDATE `total_amount` = VALUES(`total_amount`);

-- Sample Inventory Logs
INSERT INTO `inventory_logs` (`id`, `user_id`, `product_id`, `type`, `quantity`, `reason`) VALUES
(1, 1, 1, 'in', 50, 'Initial Stock Purchase Restock'),
(2, 2, 2, 'in', 25, 'Initial Stock Purchase Restock'),
(3, 3, 1, 'out', 2, 'Sale #1 Fulfillment'),
(4, 3, 2, 'out', 1, 'Sale #2 Fulfillment')
ON DUPLICATE KEY UPDATE `quantity` = VALUES(`quantity`);
