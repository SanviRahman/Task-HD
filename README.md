# JV Construction Management System — Starter MVP

## Requirements
- PHP 8.1+
- MySQL 5.7+ / MariaDB
- Apache with PDO MySQL enabled

## XAMPP installation
1. Copy the `jv_construction_management` folder to `C:\xampp\htdocs\`.
2. Start Apache and MySQL in XAMPP.
3. Open `config.php` and update DB_HOST, DB_NAME, DB_USER, DB_PASS if needed.
4. Open `http://localhost/jv_construction_management/install.php`.
5. Set the administrator name, username and password (minimum 8 characters).
6. Sign in at `http://localhost/jv_construction_management/login.php`.
7. Delete `install.php` after successful installation.

## First setup
1. Create a financial year (example: FY 2026-27, 2026-07-01 to 2027-06-30).
2. Create projects.
3. Create users and assign per-project permissions.
4. Add expense heads (the current MVP provides the schema; the dedicated expense-head editor is a next-stage feature).
5. Cashiers with Add Expense permission can submit entries; Admin can approve entries.

## Important MVP scope
This is a first runnable foundation, not yet the complete requested production system. The next development pass should add:
- Project-specific expense-head management UI and strict permission checks for each action
- Editing/deleting expense entries with audit log
- Owner approval workflow distinct from Admin
- Monthly/daily/head-wise printable reports
- Project closing with cheque image/PDF upload, bank deposit amount, surplus/deficit calculation and final approval
- Audit trail, backups, password reset, file validation, and deployment hardening

## Security
Use a strong unique admin password. Do not expose this system publicly until HTTPS, server permissions, database credentials, upload validation, audit logging, backups and production security review are completed.


## Upgrade: daily advances and reports
On existing installs, back up your database and run `upgrade.sql` once using phpMyAdmin or MySQL client. On a fresh install, run `install.php` first and then `upgrade.sql`. New pages: `advances.php`, `reports.php`. Expenses now support per-project head filtering, optional employee advance allocation, and editing (resets approval). Approval is POST/CSRF protected. Reports filter by date, project, employee, status, offer CSV export, and browser Print/Save as PDF. PDF is browser-generated, not a direct server PDF download.

**Important accounting limitations:** An advance is associated with one employee and project; expenses can optionally be assigned to that employee. The displayed period difference is not a brought-forward running balance. No automated prevention of spending beyond advances, no approval workflow for advances, and no immutable audit history yet. Test in staging before use with real financial data.

## Employee Advance Ledger (new)
Run `ledger_upgrade.sql` once after `upgrade.sql`. Navigate to `ledger.php` or the Employee Ledger menu. Admin sets a per-project/per-employee opening balance and opening date (on or before earliest transaction). Filter by project, employee and date range. The ledger carries forward all earlier advances and **approved** expenses, shows running and closing balance, and supports browser Print / Save as PDF. Pending/rejected expenses are not deducted. Unassigned/general expenses are not deducted. This ledger is an accounting view, not a verified cash reconciliation. Database/PHP integration testing is still required.
