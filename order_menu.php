<?php
require_once 'core/db.php';

// 1. Hide old duplicates by moving them to an unused parent
$pdo->exec("UPDATE sys_pages SET parent_id = -1 WHERE id IN (1, 33, 37, 40, 41)");

// 2. Set Top Level Parents
$pdo->exec("UPDATE sys_pages SET page_name = 'Admin Dashboard', page_url = 'index.php', parent_id = 0, sort_order = 1 WHERE id = 901");

// Make 902 the "Trainer Portal" Parent
$pdo->exec("UPDATE sys_pages SET page_name = 'Trainer Portal', page_url = '#', parent_id = 0, sort_order = 2 WHERE id = 902");

// Make 903 the "Member Portal" Parent
$pdo->exec("UPDATE sys_pages SET page_name = 'Member Portal', page_url = '#', parent_id = 0, sort_order = 3 WHERE id = 903");

// 3. Create actual Dashboard children if they don't exist
$pdo->exec("INSERT IGNORE INTO sys_pages (id, parent_id, page_name, page_url, icon_class, sort_order) VALUES (1003, 902, 'Trainer Dashboard', 'dashboards/trainer/dashboard.php', 'bi bi-speedometer2', 1)");
$pdo->exec("INSERT IGNORE INTO sys_pages (id, parent_id, page_name, page_url, icon_class, sort_order) VALUES (1004, 903, 'Member Dashboard', 'dashboards/student/member_dashboard.php', 'bi bi-speedometer2', 1)");

// 4. Move Trainer Pages under Trainer Portal (902)
$trainerPages = [
    34 => 2, // My Members
    35 => 3, // Workout Plans
    36 => 4, // Member Progress
    32 => 5, // Gym Staff
    24 => 6, // Assign Trainers
    38 => 7  // Training Hub
];
foreach($trainerPages as $id => $sort) {
    $pdo->exec("UPDATE sys_pages SET parent_id = 902, sort_order = $sort WHERE id = $id");
}

// 5. Move Member Pages under Member Portal (903)
$memberPages = [
    31 => 2, // Gym Members
    21 => 3, // Assign Plan
    20 => 4, // Gym Membership Plans
    22 => 5, // Expiry Alerts
    23 => 6  // Gym Attendance
];
foreach($memberPages as $id => $sort) {
    $pdo->exec("UPDATE sys_pages SET parent_id = 903, sort_order = $sort WHERE id = $id");
}

// 6. Give permissions for the new dashboard links
$pdo->exec("INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('super_admin', 1003), ('super_admin', 1004), ('trainer', 1003), ('student', 1004)");

echo "Menu ordering updated successfully!\n";
?>
