-- PocketPilot Database Schema & Seed Data
-- PocketPilot: Student Personal Finance Manager (Rupee Currency Edition)
-- Compatible with MySQL 5.7+, MySQL 8.0+, MariaDB, cPanel, Railway, Render, InfinityFree

-- Optional: Create database if importing on a blank server with root privileges:
-- CREATE DATABASE IF NOT EXISTS `pocketpilot_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `pocketpilot_db`;

-- 1. Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `type` ENUM('expense', 'income') NOT NULL,
    `icon` VARCHAR(50) DEFAULT 'bi-tag',
    `color` VARCHAR(20) DEFAULT '#4f46e5'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Transactions Table (Expenses & Income)
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `type` ENUM('expense', 'income') NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `date` DATE NOT NULL,
    `payment_method` VARCHAR(50) NOT NULL DEFAULT 'Cash',
    `description` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Budgets Table
CREATE TABLE IF NOT EXISTS `budgets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category` VARCHAR(50) NOT NULL,
    `monthly_limit` DECIMAL(10,2) NOT NULL,
    `month_year` VARCHAR(7) NOT NULL, -- Format: YYYY-MM
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `cat_month` (`category`, `month_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Savings Goals Table
CREATE TABLE IF NOT EXISTS `savings_goals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(100) NOT NULL,
    `target_amount` DECIMAL(10,2) NOT NULL,
    `current_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `target_date` DATE NOT NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Savings Contributions Table
CREATE TABLE IF NOT EXISTS `savings_contributions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `goal_id` INT NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `contribution_date` DATE NOT NULL,
    `note` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`goal_id`) REFERENCES `savings_goals`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Contact Messages Table
CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `subject` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Users Table (Authentication)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `avatar` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default demo student account (password: password123)
INSERT INTO `users` (`id`, `name`, `email`, `password`) VALUES
(1, 'Shruti', 'student@pocketpilot.com', '$2y$10$HidVwGe4quVBHxSlr/eyCetdbINgLsRb6eQtEnv0iU2WPe/OYSCIm')
ON DUPLICATE KEY UPDATE `name` = 'Shruti', `password` = VALUES(`password`);

-- Clear and seed default categories
TRUNCATE TABLE `categories`;

INSERT INTO `categories` (`name`, `type`, `icon`, `color`) VALUES
('Food & Dining', 'expense', 'bi-cup-hot', '#ef4444'),
('Books & Study Supplies', 'expense', 'bi-book', '#3b82f6'),
('Rent & Housing', 'expense', 'bi-house-door', '#8b5cf6'),
('Transportation', 'expense', 'bi-bus-front', '#f59e0b'),
('Entertainment & Outings', 'expense', 'bi-controller', '#ec4899'),
('Personal Care & Health', 'expense', 'bi-heart-pulse', '#10b981'),
('Tech & Subscriptions', 'expense', 'bi-laptop', '#06b6d4'),
('Groceries', 'expense', 'bi-cart', '#84cc16'),
('Other Expense', 'expense', 'bi-three-dots', '#64748b'),
('Pocket Money / Allowance', 'income', 'bi-wallet2', '#10b981'),
('Scholarship / Grant', 'income', 'bi-award', '#3b82f6'),
('Part-Time Work', 'income', 'bi-briefcase', '#f59e0b'),
('Freelance & Tutoring', 'income', 'bi-laptop', '#8b5cf6'),
('Gifts & Cashbacks', 'income', 'bi-gift', '#ec4899'),
('Other Income', 'income', 'bi-cash-coin', '#06b6d4');

-- Clear and seed fresh transactions in Rupees
TRUNCATE TABLE `transactions`;

INSERT INTO `transactions` (`type`, `amount`, `category`, `date`, `payment_method`, `description`) VALUES
('income', 15000.00, 'Scholarship / Grant', DATE_FORMAT(CURRENT_DATE, '%Y-%m-01'), 'Bank Transfer', 'Semester Merit Scholarship'),
('income', 6000.00, 'Pocket Money / Allowance', DATE_FORMAT(CURRENT_DATE, '%Y-%m-03'), 'UPI / Online', 'Monthly Home Pocket Money'),
('income', 3500.00, 'Part-Time Work', DATE_FORMAT(CURRENT_DATE, '%Y-%m-10'), 'Debit Card', 'Campus Library Shift Wages'),
('income', 2500.00, 'Freelance & Tutoring', DATE_FORMAT(CURRENT_DATE, '%Y-%m-18'), 'UPI / Online', 'Math & Coding Tutoring Sessions'),
('expense', 650.00, 'Books & Study Supplies', DATE_FORMAT(CURRENT_DATE, '%Y-%m-04'), 'Debit Card', 'Data Structures & Algorithms textbook'),
('expense', 180.00, 'Food & Dining', DATE_FORMAT(CURRENT_DATE, '%Y-%m-05'), 'UPI / Online', 'Campus canteen lunch with study group'),
('expense', 600.00, 'Transportation', DATE_FORMAT(CURRENT_DATE, '%Y-%m-06'), 'UPI / Online', 'Monthly student metro rail pass'),
('expense', 1850.00, 'Groceries', DATE_FORMAT(CURRENT_DATE, '%Y-%m-08'), 'Credit Card', 'Hostel room pantry & breakfast restocking'),
('expense', 149.00, 'Tech & Subscriptions', DATE_FORMAT(CURRENT_DATE, '%Y-%m-12'), 'UPI / Online', 'Spotify Student + Cloud Storage subscription'),
('expense', 450.00, 'Food & Dining', DATE_FORMAT(CURRENT_DATE, '%Y-%m-15'), 'Cash', 'Dinner celebration after midterm exams'),
('expense', 320.00, 'Personal Care & Health', DATE_FORMAT(CURRENT_DATE, '%Y-%m-19'), 'UPI / Online', 'Pharmacy prescriptions and vitamins'),
('expense', 350.00, 'Entertainment & Outings', DATE_FORMAT(CURRENT_DATE, '%Y-%m-22'), 'UPI / Online', 'Weekend movie ticket and popcorn'),
('expense', 1200.00, 'Groceries', DATE_FORMAT(CURRENT_DATE, '%Y-%m-25'), 'Debit Card', 'Weekly grocery supermarket trip'),
('expense', 65.00, 'Food & Dining', CURRENT_DATE, 'Cash', 'Evening chai & samosa at campus cafe');

-- Clear and seed fresh budgets in Rupees
TRUNCATE TABLE `budgets`;

INSERT INTO `budgets` (`category`, `monthly_limit`, `month_year`) VALUES
('Food & Dining', 3500.00, DATE_FORMAT(CURRENT_DATE, '%Y-%m')),
('Groceries', 4000.00, DATE_FORMAT(CURRENT_DATE, '%Y-%m')),
('Books & Study Supplies', 1500.00, DATE_FORMAT(CURRENT_DATE, '%Y-%m')),
('Transportation', 1000.00, DATE_FORMAT(CURRENT_DATE, '%Y-%m')),
('Entertainment & Outings', 1200.00, DATE_FORMAT(CURRENT_DATE, '%Y-%m')),
('Tech & Subscriptions', 500.00, DATE_FORMAT(CURRENT_DATE, '%Y-%m')),
('Personal Care & Health', 1000.00, DATE_FORMAT(CURRENT_DATE, '%Y-%m'));

-- Clear and seed fresh savings goals in Rupees
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `savings_contributions`;
TRUNCATE TABLE `savings_goals`;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO `savings_goals` (`title`, `target_amount`, `current_amount`, `target_date`, `notes`) VALUES
('New Coding Laptop (MacBook Air)', 65000.00, 42000.00, DATE_ADD(CURRENT_DATE, INTERVAL 90 DAY), 'For computer science courses and app development'),
('Emergency Student Cushion', 10000.00, 7500.00, DATE_ADD(CURRENT_DATE, INTERVAL 60 DAY), '3 months of unexpected expenses safety net'),
('Spring Semester Break Trip', 6000.00, 3200.00, DATE_ADD(CURRENT_DATE, INTERVAL 120 DAY), 'Road trip with college roommates'),
('Certification Exam Fee', 3500.00, 3500.00, DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY), 'AWS Certified Cloud Practitioner voucher - Fully Saved!');
