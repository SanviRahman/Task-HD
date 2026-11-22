-- Run once after upgrade.sql. One opening balance per project and employee.
CREATE TABLE IF NOT EXISTS employee_opening_balances (
 id INT AUTO_INCREMENT PRIMARY KEY,
 project_id INT NOT NULL,
 employee_id INT NOT NULL,
 opening_date DATE NOT NULL,
 amount DECIMAL(14,2) NOT NULL DEFAULT 0,
 note VARCHAR(500) NULL,
 updated_by INT NOT NULL,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_project_employee (project_id,employee_id),
 FOREIGN KEY (project_id) REFERENCES projects(id),
 FOREIGN KEY (employee_id) REFERENCES users(id),
 FOREIGN KEY (updated_by) REFERENCES users(id)
);
