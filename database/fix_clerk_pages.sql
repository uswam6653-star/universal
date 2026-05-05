-- Fix column names for Clerk pages
UPDATE sys_pages SET page_name = 'Voucher Dispatch', icon_class = 'bi bi-send' WHERE page_url = 'dashboards/clerk/vouchers.php';
UPDATE sys_pages SET page_name = 'Verify Payments', icon_class = 'bi bi-check-all' WHERE page_url = 'dashboards/clerk/verify_payments.php';

-- Ensure Clerk role is properly linked to these pages
INSERT IGNORE INTO role_access (role_id, page_id)
SELECT r.id, p.id FROM sys_roles r, sys_pages p 
WHERE r.role_key = 'clerk' AND p.page_url LIKE 'dashboards/clerk/%';
