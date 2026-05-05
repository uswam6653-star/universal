<?php
require_once 'core/db.php';

try {
    // 1. Ensure 'hod' role exists
    $pdo->query("INSERT INTO sys_roles (role_name, role_key) VALUES ('Trainer', 'hod') ON DUPLICATE KEY UPDATE role_name='Trainer'");
    
    // 2. Ensure Trainer user has 'hod' role
    $pdo->query("UPDATE users SET role = 'hod' WHERE email = 'trainer@gym.com'");

    // 3. Ensure pages exist in sys_pages
    $pdo->query("INSERT INTO sys_pages (id, page_name, page_url, icon_class, parent_id) 
                 VALUES (33, 'Trainer Dashboard', 'dashboards/hod/trainer_dashboard.php', 'bi bi-speedometer2', 0)
                 ON DUPLICATE KEY UPDATE page_url='dashboards/hod/trainer_dashboard.php'");

    // 4. Grant access
    $pdo->query("INSERT INTO role_access (role_key, page_id) VALUES ('hod', 33) ON DUPLICATE KEY UPDATE role_key='hod'");
    
    echo "Permissions fixed successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
