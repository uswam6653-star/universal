-- Clean up potentially failed previous attempts and ensure fresh state
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Ensure Clerk Role exists
INSERT IGNORE INTO sys_roles (role_name, role_key, is_system_role) 
VALUES ('Clerk', 'clerk', 1);

-- 2. Insert Clerk Pages into sys_pages
-- First, delete any existing but incomplete clerk pages to avoid duplicates
DELETE FROM sys_pages WHERE page_url IN ('dashboards/clerk/vouchers.php', 'dashboards/clerk/verify_payments.php');

INSERT INTO sys_pages (page_name, page_url, parent_id, icon_class, sort_order) VALUES 
('Voucher Dispatch', 'dashboards/clerk/vouchers.php', 0, 'bi bi-send', 50),
('Verify Payments', 'dashboards/clerk/verify_payments.php', 0, 'bi bi-check-all', 51);

-- 3. Map Clerk Role to role_access
-- Clear old clerk permissions if any
DELETE FROM role_access WHERE role_key = 'clerk';

-- Dashboard/Root Access
-- Use subqueries to get Page IDs for safety
INSERT INTO role_access (role_key, page_id)
SELECT 'clerk', id FROM sys_pages WHERE page_url IN ('index.php', 'profile.php', 'logout.php');

-- Clerk Specific Pages
INSERT INTO role_access (role_key, page_id)
SELECT 'clerk', id FROM sys_pages WHERE page_url LIKE 'dashboards/clerk/%';

SET FOREIGN_KEY_CHECKS = 1;
