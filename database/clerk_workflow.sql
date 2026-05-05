-- University Fee System - Clerk Workflow Extensions
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Add Clerk Role
INSERT IGNORE INTO sys_roles (role_name, role_key, is_system_role) 
VALUES ('Clerk', 'clerk', 1);

-- 2. Update Invoice Status Column
-- Changing from ENUM to VARCHAR to support more flexible statuses
ALTER TABLE invoices MODIFY COLUMN status VARCHAR(30) DEFAULT 'draft';

-- 3. Update Payments Table for Proofs
ALTER TABLE payments ADD COLUMN proof_image VARCHAR(255) DEFAULT NULL;
ALTER TABLE payments ADD COLUMN verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending';

-- 4. Register Clerk Pages in sys_pages
INSERT IGNORE INTO sys_pages (page_title, page_url, parent_id, icon, sort_order) VALUES 
('Voucher Dispatch', 'dashboards/clerk/vouchers.php', 0, 'bi-send', 10),
('Verify Payments', 'dashboards/clerk/verify_payments.php', 0, 'bi-check-all', 11);

-- 5. Set Permissions for Clerk (Role ID will likely be 9)
-- Fetching Clerk ID dynamically in a real scenario, but here we assume it follows the sequence
-- Let's use subqueries for safety
INSERT IGNORE INTO role_access (role_id, page_id)
SELECT r.id, p.id FROM sys_roles r, sys_pages p 
WHERE r.role_key = 'clerk' AND p.page_url LIKE 'dashboards/clerk/%';

-- Also give Clerk access to profile and logout
INSERT IGNORE INTO role_access (role_id, page_id)
SELECT r.id, p.id FROM sys_roles r, sys_pages p 
WHERE r.role_key = 'clerk' AND p.page_url IN ('profile.php', 'logout.php', 'index.php');

SET FOREIGN_KEY_CHECKS = 1;
