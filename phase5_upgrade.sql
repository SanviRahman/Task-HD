-- JV Construction | Phase 5: additive, rerunnable migration.
-- BACK UP THE EXISTING DATABASE before running in phpMyAdmin > jv_construction > Import.
-- Does NOT drop or alter any existing tables or records.
-- MariaDB 10.4+ / MySQL 8.0+ with InnoDB, utf8mb4.

CREATE TABLE IF NOT EXISTS project_budgets (
  project_id INT NOT NULL PRIMARY KEY,
  budget_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
  notes VARCHAR(500) NULL,
  updated_by INT NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_p5_budget_project FOREIGN KEY (project_id) REFERENCES projects(id),
  CONSTRAINT fk_p5_budget_user FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_items (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  item_name VARCHAR(150) NOT NULL,
  unit VARCHAR(20) NOT NULL DEFAULT 'pcs',
  minimum_qty DECIMAL(14,3) NOT NULL DEFAULT 0,
  quantity_on_hand DECIMAL(14,3) NOT NULL DEFAULT 0,
  created_by INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_p5_item_project_name (project_id, item_name),
  KEY ix_p5_item_project (project_id),
  CONSTRAINT fk_p5_item_project FOREIGN KEY (project_id) REFERENCES projects(id),
  CONSTRAINT fk_p5_item_actor FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS suppliers (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  supplier_name VARCHAR(150) NOT NULL,
  contact_name VARCHAR(120) NULL,
  phone VARCHAR(40) NULL,
  address VARCHAR(400) NULL,
  created_by INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_p5_supplier_actor FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_purchases (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  supplier_id INT NOT NULL,
  item_id INT NOT NULL,
  invoice_no VARCHAR(100) NULL,
  purchase_date DATE NOT NULL,
  quantity DECIMAL(14,3) NOT NULL,
  unit_price DECIMAL(14,2) NOT NULL,
  total_amount DECIMAL(14,2) NOT NULL,
  notes VARCHAR(500) NULL,
  created_by INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_p5_supplier_invoice (supplier_id,invoice_no),
  KEY ix_p5_purchase_project_date (project_id,purchase_date),
  KEY ix_p5_purchase_supplier (supplier_id),
  CONSTRAINT fk_p5_purchase_project FOREIGN KEY (project_id) REFERENCES projects(id),
  CONSTRAINT fk_p5_purchase_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
  CONSTRAINT fk_p5_purchase_item FOREIGN KEY (item_id) REFERENCES inventory_items(id),
  CONSTRAINT fk_p5_purchase_actor FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_movements (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  movement_type ENUM('in','out') NOT NULL,
  quantity DECIMAL(14,3) NOT NULL,
  movement_date DATE NOT NULL,
  purchase_id INT NULL,
  note VARCHAR(500) NULL,
  created_by INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_p5_movement_item_date (item_id,movement_date),
  UNIQUE KEY uq_p5_movement_purchase (purchase_id),
  CONSTRAINT fk_p5_movement_item FOREIGN KEY (item_id) REFERENCES inventory_items(id),
  CONSTRAINT fk_p5_movement_purchase FOREIGN KEY (purchase_id) REFERENCES supplier_purchases(id),
  CONSTRAINT fk_p5_movement_actor FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS supplier_payments (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  purchase_id INT NOT NULL,
  payment_date DATE NOT NULL,
  amount DECIMAL(14,2) NOT NULL,
  method ENUM('cash','bank','cheque','mobile') NOT NULL DEFAULT 'cash',
  reference_no VARCHAR(120) NULL,
  note VARCHAR(500) NULL,
  created_by INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_p5_payment_purchase (purchase_id),
  CONSTRAINT fk_p5_payment_purchase FOREIGN KEY (purchase_id) REFERENCES supplier_purchases(id),
  CONSTRAINT fk_p5_payment_actor FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  actor_id INT NULL,
  action VARCHAR(90) NOT NULL,
  entity_type VARCHAR(70) NOT NULL,
  entity_id INT NULL,
  project_id INT NULL,
  description VARCHAR(500) NOT NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_p5_audit_time (created_at),
  KEY ix_p5_audit_action (action,created_at),
  KEY ix_p5_audit_project (project_id,created_at),
  CONSTRAINT fk_p5_audit_user FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
