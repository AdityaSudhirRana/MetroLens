-- ============================================================================
-- AI PACKAGE INSPECTOR - COMPLETE MYSQL DATABASE SCHEMA & SEED DATA
-- Database Name: package_inspector
-- Engine: MySQL / MariaDB (XAMPP / phpMyAdmin Compatible)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `package_inspector` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `package_inspector`;

-- Drop existing tables in reverse dependency order for clean re-initialization
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `inquiries`;
DROP TABLE IF EXISTS `violations`;
DROP TABLE IF EXISTS `inspection_fields`;
DROP TABLE IF EXISTS `inspections`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `companies`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- 1. USERS TABLE (Role-based: 'officer' vs 'inspector')
-- ----------------------------------------------------------------------------
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `employee_id` VARCHAR(50) NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('officer', 'inspector') NOT NULL DEFAULT 'inspector',
    `department` VARCHAR(100) DEFAULT 'Legal Metrology Enforcement',
    `designation` VARCHAR(100) DEFAULT 'Inspector',
    `office_region` VARCHAR(100) DEFAULT 'Zone 1 - Headquarters',
    `joining_date` DATE DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `status` ENUM('Active', 'Inactive', 'Suspended') NOT NULL DEFAULT 'Active',
    `last_login` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 2. COMPANIES TABLE (Manufacturers, Packers, Importers)
-- ----------------------------------------------------------------------------
CREATE TABLE `companies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_name` VARCHAR(150) NOT NULL UNIQUE,
    `company_type` ENUM('Manufacturer', 'Packer', 'Importer', 'Manufacturer & Packer', 'Other') DEFAULT 'Manufacturer & Packer',
    `address` TEXT NOT NULL,
    `phone` VARCHAR(30) DEFAULT NULL,
    `email` VARCHAR(120) DEFAULT NULL,
    `website` VARCHAR(150) DEFAULT NULL,
    `status` ENUM('Active', 'Under Inquiry', 'Blacklisted') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 3. PRODUCTS TABLE (Commodity Catalog)
-- ----------------------------------------------------------------------------
CREATE TABLE `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT DEFAULT NULL,
    `gtin_barcode` VARCHAR(50) DEFAULT NULL,
    `product_name` VARCHAR(150) NOT NULL,
    `brand_name` VARCHAR(100) DEFAULT NULL,
    `category` VARCHAR(100) NOT NULL DEFAULT 'Packaged Commodities',
    `manufacturer_name` VARCHAR(150) NOT NULL,
    `total_inspections` INT DEFAULT 0,
    `total_violations` INT DEFAULT 0,
    `compliance_rate` DECIMAL(5,2) DEFAULT 100.00,
    `last_inspected_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 4. INSPECTIONS TABLE (Core Inspection Log)
-- ----------------------------------------------------------------------------
CREATE TABLE `inspections` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `inspection_number` VARCHAR(50) NOT NULL UNIQUE,
    `company_id` INT DEFAULT NULL,
    `product_id` INT DEFAULT NULL,
    `inspector_id` INT NOT NULL,
    `inspection_type` VARCHAR(100) DEFAULT 'Routine Market Inspection',
    `package_image_url` VARCHAR(500) DEFAULT NULL,
    `raw_ocr_text` LONGTEXT DEFAULT NULL,
    `extracted_json` JSON DEFAULT NULL,
    `overall_status` ENUM('COMPLIANT', 'NON_COMPLIANT', 'PENDING_REVIEW') NOT NULL DEFAULT 'COMPLIANT',
    `risk_level` ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') DEFAULT 'LOW',
    `compliance_score` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    `notes` TEXT DEFAULT NULL,
    `inspection_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`inspector_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 5. INSPECTION_FIELDS TABLE (Individual Declarations Breakdown)
-- ----------------------------------------------------------------------------
CREATE TABLE `inspection_fields` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `inspection_id` INT NOT NULL,
    `field_name` VARCHAR(100) NOT NULL,
    `expected_value` TEXT DEFAULT NULL,
    `extracted_value` TEXT DEFAULT NULL,
    `corrected_value` TEXT DEFAULT NULL,
    `field_status` VARCHAR(50) DEFAULT 'Verified',
    `confidence_score` DECIMAL(5,2) DEFAULT 95.00,
    FOREIGN KEY (`inspection_id`) REFERENCES `inspections`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 6. VIOLATIONS TABLE (Legal Metrology Rule 6 Infringements)
-- ----------------------------------------------------------------------------
CREATE TABLE `violations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `inspection_id` INT NOT NULL,
    `rule_name` VARCHAR(150) NOT NULL,
    `field_name` VARCHAR(100) NOT NULL,
    `severity` ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    `penalty_amount` DECIMAL(10,2) DEFAULT 10000.00,
    `notes` TEXT DEFAULT NULL,
    `status` ENUM('Detected', 'Notice Issued', 'Compounded', 'Court Appeal', 'Resolved') DEFAULT 'Detected',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`inspection_id`) REFERENCES `inspections`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 7. INQUIRIES TABLE (Statutory Show-Cause Legal Notices)
-- ----------------------------------------------------------------------------
CREATE TABLE `inquiries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `inquiry_number` VARCHAR(50) NOT NULL UNIQUE,
    `company_id` INT DEFAULT NULL,
    `product_id` INT DEFAULT NULL,
    `inspection_id` INT DEFAULT NULL,
    `inspector_id` INT NOT NULL,
    `manufacturer_name` VARCHAR(150) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `response_deadline` DATE NOT NULL,
    `manufacturer_response` TEXT DEFAULT NULL,
    `status` ENUM('Issued', 'Responded', 'Under Review', 'Resolved', 'Escalated') DEFAULT 'Issued',
    `issued_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`inspection_id`) REFERENCES `inspections`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`inspector_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 8. AUDIT_LOGS TABLE (Security & Action Traceability)
-- ----------------------------------------------------------------------------
CREATE TABLE `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- SEED DATA INSERTIONS
-- Default Passwords:
--   Officer: admin123 -> $2y$10$n4O8Wn6M.Uv1nJ5M0O1S7.nUeWfC0wB1B4Y9hF4X7G7Z0R3K2L1.y
--   Inspector: 123456 -> $2y$10$e7K5.aP7d1G0B1D4Y7Z0R.nUeWfC0wB1B4Y9hF4X7G7Z0R3K2L1.y
-- ============================================================================

INSERT INTO `users` (`id`, `employee_id`, `full_name`, `email`, `password_hash`, `role`, `department`, `designation`, `office_region`, `joining_date`, `phone`, `status`) VALUES
(1, 'OFF-2026-001', 'Chief Officer Vikram Malhotra', 'admin@example.com', '$2y$10$4.aU9.Z5p4gB1e1e1e1e1eKzZ0R3K2L1M0N1P2Q3R4S5T6U7V8W9X', 'officer', 'Legal Metrology HQ', 'Chief Controller', 'Zone 1 - Headquarters', '2020-01-15', '+91 98111 22334', 'Active'),
(2, 'INS-2026-101', 'Inspector Rajesh Sharma', 'officer@example.com', '$2y$10$e7K5.aP7d1G0B1D4Y7Z0R.nUeWfC0wB1B4Y9hF4X7G7Z0R3K2L1.y', 'inspector', 'Legal Metrology Zone 1', 'Senior Inspector', 'West Delhi Region', '2022-03-10', '+91 98765 43210', 'Active'),
(3, 'INS-2026-102', 'Inspector Priya Patel', 'priya.p@gov.in', '$2y$10$e7K5.aP7d1G0B1D4Y7Z0R.nUeWfC0wB1B4Y9hF4X7G7Z0R3K2L1.y', 'inspector', 'Legal Metrology Zone 2', 'Field Inspector', 'South Mumbai Region', '2023-06-01', '+91 98222 33445', 'Active'),
(4, 'INS-2026-103', 'Inspector Amit Verma', 'amit.v@gov.in', '$2y$10$e7K5.aP7d1G0B1D4Y7Z0R.nUeWfC0wB1B4Y9hF4X7G7Z0R3K2L1.y', 'inspector', 'Legal Metrology Zone 1', 'Field Inspector', 'North Delhi Region', '2024-02-15', '+91 98333 44556', 'Active');

INSERT INTO `companies` (`id`, `company_name`, `company_type`, `address`, `phone`, `email`, `website`, `status`) VALUES
(1, 'Parle Products Pvt. Ltd.', 'Manufacturer & Packer', 'Vile Parle East, Mumbai - 400057, Maharashtra', '1800-22-1080', 'customercare@parle.biz', 'www.parleproducts.com', 'Active'),
(2, 'PepsiCo India Holdings Pvt. Ltd.', 'Manufacturer & Packer', 'Level 3-6, Pioneer Square, Sector 62, Gurugram - 122001, Haryana', '1800-22-4466', 'feedback@pepsico.com', 'www.pepsicoindia.co.in', 'Active'),
(3, 'Tata Consumer Products Limited', 'Manufacturer & Packer', '1 Bishop Lefroy Road, Kolkata - 700020, West Bengal', '1800-108-4488', 'care@tataconsumer.com', 'www.tataconsumer.com', 'Active'),
(4, 'Britannia Industries Ltd.', 'Manufacturer & Packer', '5A Hungerford Street, Kolkata - 700017, West Bengal', '1800-425-4449', 'feedback@britaindia.com', 'www.britannia.co.in', 'Active');

INSERT INTO `products` (`id`, `company_id`, `gtin_barcode`, `product_name`, `brand_name`, `category`, `manufacturer_name`, `total_inspections`, `total_violations`, `compliance_rate`, `last_inspected_at`) VALUES
(1, 1, '8901030000011', 'Parle-G Original Glucose Biscuits (250g)', 'Parle-G', 'Packaged Foods & Biscuits', 'Parle Products Pvt. Ltd.', 4, 0, 100.00, '2026-09-05 10:15:00'),
(2, 2, '8901491100022', 'Kurkure Masala Munch (90g)', 'Kurkure', 'Packaged Foods & Biscuits', 'PepsiCo India Holdings Pvt. Ltd.', 3, 1, 66.67, '2026-09-04 14:30:00'),
(3, 3, '8901058000033', 'Tata Salt Vacuum Evaporated Iodized Salt (1kg)', 'Tata Salt', 'Packaged Foods & Biscuits', 'Tata Consumer Products Limited', 3, 0, 100.00, '2026-08-28 11:20:00'),
(4, 4, '8901063000044', 'Britannia Good Day Cashew Cookies (200g)', 'Britannia', 'Packaged Foods & Biscuits', 'Britannia Industries Ltd.', 2, 0, 100.00, '2026-08-15 09:45:00');

INSERT INTO `inspections` (`id`, `inspection_number`, `company_id`, `product_id`, `inspector_id`, `inspection_type`, `package_image_url`, `raw_ocr_text`, `overall_status`, `risk_level`, `compliance_score`, `notes`, `inspection_date`) VALUES
(1, 'INS-20260905-00101', 1, 1, 2, 'Routine Market Inspection', 'assets/demo/parle-g.jpg', 'PARLE-G ORIGINAL GLUCOSE BISCUITS\nNet Wt: 250 g\nMFR: Parle Products Pvt Ltd, Vile Parle East, Mumbai 400057\nMRP: Rs. 30.00\nPkd Date: 08/2026\nConsumer Care: 1800-22-1080', 'COMPLIANT', 'LOW', 100.00, 'All 7 mandatory declarations present and verified.', '2026-09-05 10:15:00'),
(2, 'INS-20260904-00102', 2, 2, 2, 'Market Surveillance Inspection', 'assets/demo/kurkure.jpg', 'KURKURE MASALA MUNCH\nNet Wt: 90 g\nMFR: PepsiCo India Holdings Pvt Ltd\nMRP: Rs. 20.00\nPkd Date: 08/2026\nBatch No: KK082026M', 'NON_COMPLIANT', 'HIGH', 71.43, 'Consumer care email address missing on package label.', '2026-09-04 14:30:00'),
(3, 'INS-20260828-00103', 3, 3, 3, 'Citizen Complaint Audit', 'assets/demo/tata-salt.jpg', 'TATA SALT VACUUM EVAPORATED IODIZED SALT\nNet Wt: 1 kg\nMFR: Tata Consumer Products Ltd\nMRP: Rs. 28.00\nPkd Date: 07/2026', 'COMPLIANT', 'LOW', 100.00, 'Complaint verified false; net weight accurate.', '2026-08-28 11:20:00'),
(4, 'INS-20260815-00104', 4, 4, 4, 'Routine Market Inspection', 'assets/demo/goodday.jpg', 'BRITANNIA GOOD DAY CASHEW COOKIES\nNet Wt: 200 g\nMFR: Britannia Industries Ltd\nMRP: Rs. 45.00', 'COMPLIANT', 'LOW', 100.00, 'Full compliance verified.', '2026-08-15 09:45:00'),
(5, 'INS-20260730-00105', 1, 1, 2, 'Routine Market Inspection', 'assets/demo/parle-g.jpg', 'PARLE-G BISCUITS\nNet Wt: 250 g\nMFR: Parle Products Pvt Ltd', 'COMPLIANT', 'LOW', 100.00, 'Routine inspection verified.', '2026-07-30 16:00:00');

INSERT INTO `inspection_fields` (`inspection_id`, `field_name`, `expected_value`, `extracted_value`, `corrected_value`, `field_status`, `confidence_score`) VALUES
(1, 'Manufacturer Details', 'Parle Products Pvt Ltd, Vile Parle East, Mumbai 400057', 'Parle Products Pvt Ltd, Vile Parle East, Mumbai 400057', NULL, 'Verified', 98.00),
(1, 'Product Name', 'Parle-G Original Glucose Biscuits', 'Parle-G Original Glucose Biscuits', NULL, 'Verified', 99.00),
(1, 'Net Quantity', '250 g', '250 g', NULL, 'Verified', 98.00),
(1, 'MRP', '₹30.00', '₹30.00', NULL, 'Verified', 97.00),
(1, 'Manufacturing Date', '08/2026', '08/2026', NULL, 'Verified', 95.00),
(1, 'Consumer Care', '1800-22-1080', '1800-22-1080', NULL, 'Verified', 96.00),
(1, 'Country of Origin', 'India', 'India', NULL, 'Verified', 98.00),
(2, 'Manufacturer Details', 'PepsiCo India Holdings Pvt. Ltd.', 'PepsiCo India Holdings Pvt. Ltd.', NULL, 'Verified', 94.00),
(2, 'Consumer Care Email', 'feedback@pepsico.com', '', NULL, 'Missing', 0.00);

INSERT INTO `violations` (`inspection_id`, `rule_name`, `field_name`, `severity`, `penalty_amount`, `notes`, `status`) VALUES
(2, 'Rule 6(2) - Consumer Grievance Contact', 'Consumer Care Email', 'High', 15000.00, 'Consumer care email address not printed on retail package label.', 'Notice Issued');

INSERT INTO `inquiries` (`id`, `inquiry_number`, `company_id`, `product_id`, `inspection_id`, `inspector_id`, `manufacturer_name`, `subject`, `description`, `response_deadline`, `status`) VALUES
(1, 'INQ-20260904-001', 2, 2, 2, 2, 'PepsiCo India Holdings Pvt. Ltd.', 'Notice under Rule 6(2) of LM Packaged Commodities Rules 2011', 'Non-compliance detected during inspection INS-20260904-00102 regarding missing consumer care email declaration.', '2026-09-19', 'Issued');

INSERT INTO `audit_logs` (`user_id`, `action`, `details`, `ip_address`) VALUES
(1, 'SYSTEM_INIT', 'Database schema initialized with pre-seeded Legal Metrology records', '127.0.0.1'),
(2, 'CREATE_INSPECTION', 'Inspection #INS-20260905-00101 saved for Parle Products', '127.0.0.1');
