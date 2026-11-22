CREATE DATABASE IF NOT EXISTS jv_construction CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE jv_construction;

CREATE TABLE IF NOT EXISTS users (
 id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, username VARCHAR(60) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL, role ENUM('admin','owner','cashier','employee','engineer','project_head') NOT NULL DEFAULT 'employee',
 active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS financial_years (
 id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(80) NOT NULL UNIQUE,
 start_date DATE NOT NULL, end_date DATE NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS projects (
 id INT AUTO_INCREMENT PRIMARY KEY, financial_year_id INT NOT NULL, title VARCHAR(180) NOT NULL,
 tender_id VARCHAR(100) NOT NULL, project_head VARCHAR(120) NULL, start_date DATE NOT NULL,
 duration_days INT NOT NULL, status ENUM('running','completed','closed') NOT NULL DEFAULT 'running',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(financial_year_id) REFERENCES financial_years(id)
);
CREATE TABLE IF NOT EXISTS project_assignments (
 id INT AUTO_INCREMENT PRIMARY KEY, project_id INT NOT NULL, user_id INT NOT NULL,
 view_project TINYINT(1) DEFAULT 1, add_expense TINYINT(1) DEFAULT 0, edit_expense TINYINT(1) DEFAULT 0,
 delete_expense TINYINT(1) DEFAULT 0, add_head TINYINT(1) DEFAULT 0, view_report TINYINT(1) DEFAULT 0,
 approve_expense TINYINT(1) DEFAULT 0, close_project TINYINT(1) DEFAULT 0,
 UNIQUE KEY project_user(project_id,user_id),
 FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS expense_heads (
 id INT AUTO_INCREMENT PRIMARY KEY, project_id INT NOT NULL, name VARCHAR(120) NOT NULL,
 FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS expenses (
 id INT AUTO_INCREMENT PRIMARY KEY, project_id INT NOT NULL, head_id INT NOT NULL,
 entry_date DATE NOT NULL, amount DECIMAL(14,2) NOT NULL, description TEXT NULL,
 entered_by INT NOT NULL, status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
 approved_by INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE,
 FOREIGN KEY(head_id) REFERENCES expense_heads(id),
 FOREIGN KEY(entered_by) REFERENCES users(id),
 FOREIGN KEY(approved_by) REFERENCES users(id)
);
CREATE TABLE IF NOT EXISTS project_closings (
 id INT AUTO_INCREMENT PRIMARY KEY, project_id INT NOT NULL UNIQUE,
 bank_amount DECIMAL(14,2) NOT NULL, cheque_file VARCHAR(255) NULL, notes TEXT NULL,
 closed_by INT NOT NULL, closed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(project_id) REFERENCES projects(id), FOREIGN KEY(closed_by) REFERENCES users(id)
);
