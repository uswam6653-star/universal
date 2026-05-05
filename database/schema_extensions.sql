SET FOREIGN_KEY_CHECKS = 0;
-- Programs table to store department/degree info
CREATE TABLE IF NOT EXISTS programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Semesters vinculated to programs
CREATE TABLE IF NOT EXISTS semesters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    name VARCHAR(100) NOT NULL, -- e.g. Fall 2024
    number INT NOT NULL,         -- e.g. 1st, 2nd...
    status ENUM('active', 'completed', 'upcoming') DEFAULT 'upcoming',
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE
);

-- Fee structures per semester [REFINED]
DROP TABLE IF EXISTS fee_structures;
CREATE TABLE fee_structures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    semester_id INT NOT NULL,
    base_fee DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    lab_charges DECIMAL(10, 2) DEFAULT 0.00,
    library_fee DECIMAL(10, 2) DEFAULT 0.00,
    hostel_fee DECIMAL(10, 2) DEFAULT 0.00,
    credit_hour_rate DECIMAL(10, 2) DEFAULT 0.00,
    late_fine_per_day DECIMAL(10, 2) DEFAULT 0.00,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE
);

-- Scholarship table
CREATE TABLE IF NOT EXISTS scholarships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL, -- e.g. Merit-based, Need-based
    type ENUM('percentage', 'fixed') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Map scholarships to users
CREATE TABLE IF NOT EXISTS user_scholarships (
    user_id INT NOT NULL,
    scholarship_id INT NOT NULL,
    PRIMARY KEY (user_id, scholarship_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE
);

-- Alter users table for refined roles and fields
ALTER TABLE users ADD COLUMN IF NOT EXISTS roll_no VARCHAR(50) UNIQUE;
ALTER TABLE users ADD COLUMN IF NOT EXISTS program_id INT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS semester_id INT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS fee_category VARCHAR(50) DEFAULT 'Regular';
ALTER TABLE users ADD COLUMN IF NOT EXISTS scholarship_percent DECIMAL(5,2) DEFAULT 0.00;

-- Assuming 'roles' table might need updating for 'finance', 'student', 'hod'
INSERT IGNORE INTO sys_roles (role_name, role_key, is_system_role) VALUES 
('Finance Officer', 'finance', 1),
('Student', 'student', 1),
('Head of Department', 'hod', 1);

-- Invoices for tracking fees [REFINED]
DROP TABLE IF EXISTS invoices;
CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    semester_id INT NOT NULL,
    total_base_amount DECIMAL(10, 2) NOT NULL, -- Original fee before scholarship
    discount_amount DECIMAL(10, 2) DEFAULT 0.00, -- From scholarship
    fine_amount DECIMAL(10, 2) DEFAULT 0.00,
    payable_amount DECIMAL(10, 2) NOT NULL, -- total_base - discount + fine
    balance_due DECIMAL(10, 2) NOT NULL,
    status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE
);

-- Installments [REFINED]
DROP TABLE IF EXISTS installments;
CREATE TABLE installments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    installment_no INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('pending', 'paid') DEFAULT 'pending',
    paid_date TIMESTAMP NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);

-- Payments tracking table [MISSING ADDED]
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('JazzCash', 'EasyPaisa', 'Card', 'Cash') NOT NULL,
    transaction_id VARCHAR(100) UNIQUE,
    paid_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);

-- ==========================================
-- SYSTEM PAGES & ACCESS (For Sidebar)
-- ==========================================

-- 1. Management Pages (Super Admin)
INSERT INTO sys_pages (page_name, page_url, icon_class, parent_id, sort_order) VALUES 
('Academic Setup', '#', 'bi bi-mortarboard-fill', 0, 10);

SET @academic_id = LAST_INSERT_ID();

INSERT INTO sys_pages (page_name, page_url, icon_class, parent_id, sort_order) VALUES 
('Manage Programs', 'dashboards/super_admin/manage_programs.php', 'bi bi-book', @academic_id, 1),
('Manage Semesters', 'dashboards/super_admin/manage_semesters.php', 'bi bi-calendar-event', @academic_id, 2);

-- 2. Finance Pages (Finance Officer)
INSERT INTO sys_pages (page_name, page_url, icon_class, parent_id, sort_order) VALUES 
('Finance Management', '#', 'bi bi-cash-stack', 0, 20);

SET @finance_id = LAST_INSERT_ID();

INSERT INTO sys_pages (page_name, page_url, icon_class, parent_id, sort_order) VALUES 
('Fee Structure', 'dashboards/finance/manage_fees.php', 'bi bi-list-columns-reverse', @finance_id, 1),
('Generate Invoices', 'dashboards/finance/generate_invoices.php', 'bi bi-receipt', @finance_id, 2),
('Installment Plans', 'dashboards/finance/installments.php', 'bi bi-calendar-check', @finance_id, 3),
('Scholarships', 'dashboards/finance/manage_scholarships.php', 'bi bi-trophy', @finance_id, 4),
('Finance Reports', 'dashboards/finance/reports.php', 'bi bi-graph-up', @finance_id, 5),
('Fee Notifications', 'dashboards/finance/notifications.php', 'bi bi-bell-fill', @finance_id, 6),
('Fine Engine', 'dashboards/finance/fine_engine.php', 'bi bi-stopwatch', @finance_id, 7);

-- 3. HOD Access
INSERT INTO sys_pages (page_name, page_url, icon_class, parent_id, sort_order) VALUES 
('Department Stats', 'dashboards/hod/department_report.php', 'bi bi-pie-chart', 0, 40);

-- Assign permissions to HOD
INSERT INTO role_access (role_key, page_id)
SELECT 'hod', id FROM sys_pages WHERE page_url LIKE 'dashboards/hod/%';

-- 3. Student Portal
INSERT INTO sys_pages (page_name, page_url, icon_class, parent_id, sort_order) VALUES 
('My Finances', 'dashboards/student/my_fees.php', 'bi bi-wallet2', 0, 30);

-- Assign permissions to roles
-- Super Admin gets everything (handled by logic anyway, but good to have)
-- Finance Officer permissions
INSERT INTO role_access (role_key, page_id) 
SELECT 'finance', id FROM sys_pages WHERE page_url LIKE 'dashboards/finance/%' OR id = @finance_id;

-- Student permissions
INSERT INTO role_access (role_key, page_id)
SELECT 'student', id FROM sys_pages WHERE page_url LIKE 'dashboards/student/%';

SET FOREIGN_KEY_CHECKS = 1;
