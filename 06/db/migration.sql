-- Stock Flow - Database Migration Script
-- Safe migration to update an older schema to the current full RBAC specification

USE `stock_flow`;

-- 1. Upgrade `users` table: role enum and status columns
ALTER TABLE `users` 
    MODIFY COLUMN `role` ENUM('admin', 'manager', 'staff') NOT NULL DEFAULT 'staff';

-- Add status column if not present
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'status');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `users` ADD COLUMN `status` ENUM(\'active\', \'inactive\') NOT NULL DEFAULT \'active\' AFTER `role`', 
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add created_at and updated_at to users
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'created_at');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `users` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. Upgrade `inventory_logs` table: add user_id and created_at if missing
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventory_logs' AND COLUMN_NAME = 'user_id');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `inventory_logs` ADD COLUMN `user_id` INT NULL AFTER `id`, ADD CONSTRAINT `fk_inv_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL', 
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventory_logs' AND COLUMN_NAME = 'created_at');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `inventory_logs` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP', 
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. Upgrade `sales` table: add user_id if missing
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sales' AND COLUMN_NAME = 'user_id');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `sales` ADD COLUMN `user_id` INT NULL AFTER `id`, ADD CONSTRAINT `fk_sales_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL', 
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. Upgrade `purchases` table: add user_id if missing
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchases' AND COLUMN_NAME = 'user_id');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `purchases` ADD COLUMN `user_id` INT NULL AFTER `id`, ADD CONSTRAINT `fk_purchases_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL', 
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5. Create `activity_logs` table if not exists
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `action` VARCHAR(100) NOT NULL,
    `module` VARCHAR(50) NOT NULL,
    `description` TEXT NOT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- 6. Ensure default role accounts exist with valid bcrypt passwords
-- admin / admin123
INSERT INTO `users` (`username`, `password`, `full_name`, `email`, `role`, `status`)
VALUES ('admin', '$2y$10$olkJ6FFnL9FjB9HRAAK/Euk7S1ZcvJEAk3xu9qugwGlFeNs6nykhC', 'System Administrator', 'admin@stockflow.com', 'admin', 'active')
ON DUPLICATE KEY UPDATE `role` = 'admin', `status` = 'active';

-- manager / manager123
INSERT INTO `users` (`username`, `password`, `full_name`, `email`, `role`, `status`)
VALUES ('manager', '$2y$10$bKhZpYiWj2pDT97YPmqZVemI3ZG1V5wXL80Wo5k2JOfuC48uj6RRW', 'Operations Manager', 'manager@stockflow.com', 'manager', 'active')
ON DUPLICATE KEY UPDATE `role` = 'manager', `status` = 'active';

-- staff / staff123
INSERT INTO `users` (`username`, `password`, `full_name`, `email`, `role`, `status`)
VALUES ('staff', '$2y$10$wb5RddutyThO/oXE2rWYEu37bHw9tqWtrAIwrqqbp/GxH1kVOOScm', 'Warehouse Staff', 'staff@stockflow.com', 'staff', 'active')
ON DUPLICATE KEY UPDATE `role` = 'staff', `status` = 'active';

