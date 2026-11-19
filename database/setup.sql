-- ============================================
-- Laptop Store Management System
-- Database Setup Script
-- Database: laptop_store_db
-- ============================================

CREATE DATABASE IF NOT EXISTS `laptop_store_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `laptop_store_db`;

-- ============================================
-- Users Table
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
    `user_id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(100) NOT NULL,
    `role` VARCHAR(50) NOT NULL DEFAULT 'Cashier'
) ENGINE=InnoDB;

-- ============================================
-- Customers Table
-- ============================================
CREATE TABLE IF NOT EXISTS `customers` (
    `customer_id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(15),
    `email` VARCHAR(100)
) ENGINE=InnoDB;

-- ============================================
-- Products Table
-- ============================================
CREATE TABLE IF NOT EXISTS `products` (
    `product_id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_name` VARCHAR(100) NOT NULL,
    `brand` VARCHAR(50) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `stock` INT NOT NULL DEFAULT 0,
    `category` VARCHAR(50) NOT NULL DEFAULT 'Laptop',
    `specifications` TEXT NULL
) ENGINE=InnoDB;

-- ============================================
-- Suppliers Table
-- ============================================
CREATE TABLE IF NOT EXISTS `suppliers` (
    `supplier_id` INT AUTO_INCREMENT PRIMARY KEY,
    `supplier_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(50)
) ENGINE=InnoDB;

-- ============================================
-- Inventory Table
-- ============================================
CREATE TABLE IF NOT EXISTS `inventory` (
    `inventory_id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `stock` INT NOT NULL DEFAULT 0,
    `last_updated` DATE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Sales Table
-- ============================================
CREATE TABLE IF NOT EXISTS `sales` (
    `sale_id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `payment_method` VARCHAR(50) NOT NULL DEFAULT 'Cash',
    `payment_status` VARCHAR(50) NOT NULL DEFAULT 'Paid',
    `payment_reference` VARCHAR(100) NULL,
    `order_type` VARCHAR(20) NOT NULL DEFAULT 'pos',
    `order_status` VARCHAR(50) NOT NULL DEFAULT 'Delivered',
    `delivery_address` TEXT NULL,
    `serial_number` VARCHAR(100) NULL,
    `warranty_info` VARCHAR(255) NOT NULL DEFAULT '1 Year Official Manufacturer Warranty',
    `sale_date` DATE NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Seed Data
-- ============================================

-- Users (passwords hashed with password_hash('password', PASSWORD_BCRYPT))
-- Default passwords: admin123, manager123, cashier123
INSERT INTO `users` (`name`, `username`, `password`, `role`) VALUES
('System Admin', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin'),
('Store Manager', 'manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manager'),
('Jane Cashier', 'cashier', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Cashier')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Customers
INSERT INTO `customers` (`name`, `phone`, `email`) VALUES
('Juan Dela Cruz', '09171234567', 'juan@email.com'),
('Maria Santos', '09181234567', 'maria@email.com'),
('Pedro Reyes', '09191234567', 'pedro@email.com'),
('Ana Garcia', '09201234567', 'ana@email.com'),
('Carlos Mendoza', '09211234567', 'carlos@email.com');

-- Products
INSERT INTO `products` (`product_name`, `brand`, `price`, `stock`) VALUES
('VivoBook 15', 'ASUS', 32999.00, 25),
('IdeaPad 3', 'Lenovo', 28999.00, 18),
('Pavilion 14', 'HP', 35499.00, 12),
('Inspiron 15', 'Dell', 38999.00, 8),
('Swift 3', 'Acer', 41999.00, 15),
('ThinkPad E14', 'Lenovo', 45999.00, 6),
('ROG Strix G15', 'ASUS', 62999.00, 4),
('MacBook Air M2', 'Apple', 72999.00, 3),
('Predator Helios 300', 'Acer', 79999.00, 2),
('ZenBook 14', 'ASUS', 54999.00, 10);

-- Suppliers
INSERT INTO `suppliers` (`supplier_name`, `phone`) VALUES
('TechSource PH', '02-8123-4567'),
('CompuTrade Inc.', '02-8234-5678'),
('Digital Solutions Corp.', '02-8345-6789');

-- Inventory (mirrors product stock)
INSERT INTO `inventory` (`product_id`, `stock`, `last_updated`) VALUES
(1, 25, CURDATE()),
(2, 18, CURDATE()),
(3, 12, CURDATE()),
(4, 8, CURDATE()),
(5, 15, CURDATE()),
(6, 6, CURDATE()),
(7, 4, CURDATE()),
(8, 3, CURDATE()),
(9, 2, CURDATE()),
(10, 10, CURDATE());

-- Sales (sample transactions)
INSERT INTO `sales` (`customer_id`, `product_id`, `quantity`, `total_amount`, `payment_method`, `sale_date`) VALUES
(1, 1, 1, 32999.00, 'Cash', DATE_SUB(CURDATE(), INTERVAL 30 DAY)),
(2, 3, 1, 35499.00, 'GCash', DATE_SUB(CURDATE(), INTERVAL 28 DAY)),
(1, 5, 2, 83998.00, 'Credit Card', DATE_SUB(CURDATE(), INTERVAL 25 DAY)),
(3, 2, 1, 28999.00, 'Cash', DATE_SUB(CURDATE(), INTERVAL 20 DAY)),
(4, 4, 1, 38999.00, 'Maya', DATE_SUB(CURDATE(), INTERVAL 18 DAY)),
(2, 7, 1, 62999.00, 'Bank Transfer', DATE_SUB(CURDATE(), INTERVAL 15 DAY)),
(5, 1, 2, 65998.00, 'GCash', DATE_SUB(CURDATE(), INTERVAL 12 DAY)),
(3, 6, 1, 45999.00, 'Cash', DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
(1, 8, 1, 72999.00, 'Credit Card', DATE_SUB(CURDATE(), INTERVAL 7 DAY)),
(4, 10, 1, 54999.00, 'Maya', DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(5, 3, 1, 35499.00, 'GCash', DATE_SUB(CURDATE(), INTERVAL 3 DAY)),
(2, 9, 1, 79999.00, 'Bank Transfer', DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(3, 5, 1, 41999.00, 'Cash', DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(1, 2, 3, 86997.00, 'Credit Card', CURDATE()),
(4, 1, 1, 32999.00, 'GCash', CURDATE());
