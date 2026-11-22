-- Run once on an existing installation; new installations can run this after install.php.
CREATE TABLE IF NOT EXISTS employee_advances (
 id INT AUTO_INCREMENT PRIMARY KEY, project_id INT NOT NULL, employee_id INT NOT NULL,
 advance_date DATE NOT NULL, amount DECIMAL(14,2) NOT NULL, note VARCHAR(500) NULL,
 entered_by INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(project_id) REFERENCES projects(id), FOREIGN KEY(employee_id) REFERENCES users(id),
 FOREIGN KEY(entered_by) REFERENCES users(id), INDEX (project_id,employee_id,advance_date)
);
ALTER TABLE expenses ADD COLUMN advance_employee_id INT NULL;
ALTER TABLE expenses ADD CONSTRAINT fk_expense_advance_employee FOREIGN KEY (advance_employee_id) REFERENCES users(id);
