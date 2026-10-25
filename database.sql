CREATE DATABASE IF NOT EXISTS spendsense_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE spendsense_db;

DROP TABLE IF EXISTS category_budgets;
DROP TABLE IF EXISTS budgets;
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    expense_date DATE NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_expenses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_expenses_user_date (user_id, expense_date),
    INDEX idx_expenses_category (category),
    INDEX idx_expenses_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    budget_month INT NOT NULL,
    budget_year INT NOT NULL,
    monthly_limit DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_budgets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_month_budget (user_id, budget_month, budget_year)
) ENGINE=InnoDB;

CREATE TABLE category_budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    budget_month INT NOT NULL,
    budget_year INT NOT NULL,
    category VARCHAR(50) NOT NULL,
    category_limit DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_category_budgets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_category_budget (user_id, budget_month, budget_year, category)
) ENGINE=InnoDB;

INSERT INTO users (name, email, password) VALUES
('Govarthini', 'govarthini@gmail.com', '$2y$10$FR4bkUGNrDBGYvdAWcjsuOcVjRRmJnCxoufJErAzLGVOQ1VxGfU02'),
('Rohan Mehta', 'rohan@example.com', '$2y$10$FR4bkUGNrDBGYvdAWcjsuOcVjRRmJnCxoufJErAzLGVOQ1VxGfU02');

INSERT INTO expenses (user_id, title, amount, category, expense_date, payment_method, notes) VALUES
(1, 'Groceries', 1200.00, 'Food', '2026-10-01', 'UPI', 'Weekly groceries'),
(1, 'Metro Pass', 250.00, 'Travel', '2026-10-02', 'Card', 'Commute'),
(1, 'Books', 850.00, 'Education', '2026-10-04', 'Card', 'Semester books'),
(1, 'Movie Tickets', 600.00, 'Entertainment', '2026-10-07', 'UPI', 'Weekend movie'),
(1, 'Rent', 1800.00, 'Accommodation', '2026-10-10', 'Bank Transfer', 'Monthly hostel rent'),
(1, 'Pharmacy', 320.00, 'Health', '2026-10-12', 'Cash', 'Medicines'),
(2, 'Cafeteria', 450.00, 'Food', '2026-10-03', 'UPI', 'Lunch with friends'),
(2, 'Bus Card', 200.00, 'Travel', '2026-10-05', 'Cash', 'Travel top-up'),
(2, 'Internet Recharge', 499.00, 'Bills and Recharge', '2026-10-09', 'UPI', 'Monthly internet');

INSERT INTO budgets (user_id, budget_month, budget_year, monthly_limit) VALUES
(1, 10, 2026, 5000.00),
(2, 10, 2026, 3500.00);

INSERT INTO category_budgets (user_id, budget_month, budget_year, category, category_limit) VALUES
(1, 10, 2026, 'Food', 1500.00),
(1, 10, 2026, 'Travel', 700.00),
(1, 10, 2026, 'Education', 1000.00),
(2, 10, 2026, 'Food', 900.00),
(2, 10, 2026, 'Travel', 500.00);
