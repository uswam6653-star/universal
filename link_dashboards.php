<?php
require_once 'core/db.php';

// Insert Gym Dashboard (index.php) if not exists
$pdo->exec("INSERT IGNORE INTO sys_pages (id, parent_id, page_name, page_url, icon_class, sort_order) VALUES (901, 0, 'Admin Dashboard', 'index.php', 'bi bi-speedometer2', 1)");

// Insert Trainer Dashboard
$pdo->exec("INSERT IGNORE INTO sys_pages (id, parent_id, page_name, page_url, icon_class, sort_order) VALUES (902, 0, 'Trainer Dashboard', 'dashboards/trainer/dashboard.php', 'bi bi-person-badge', 2)");

// Insert Member Dashboard
$pdo->exec("INSERT IGNORE INTO sys_pages (id, parent_id, page_name, page_url, icon_class, sort_order) VALUES (903, 0, 'Member Dashboard', 'dashboards/student/member_dashboard.php', 'bi bi-person-heart', 3)");

// Give super_admin access to all 3
$pdo->exec("INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('super_admin', 901), ('super_admin', 902), ('super_admin', 903)");

// Give trainer access to Trainer Dashboard
$pdo->exec("INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('trainer', 902)");

// Give student access to Member Dashboard
$pdo->exec("INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('student', 903)");

echo "Menus linked successfully!\n";
?>
