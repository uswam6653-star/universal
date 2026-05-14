<?php
require_once 'core/db.php';

// 1. Get Dashboard ID (Primary parent for Admin)
$stmt = $pdo->prepare("SELECT id FROM sys_pages WHERE page_url = 'index.php' LIMIT 1");
$stmt->execute();
$parentId = $stmt->fetchColumn();

if ($parentId) {
    // 2. Set Gym Staff and Finance as children of Dashboard
    $pagesToNest = ['dashboards/super_admin/manage_staff.php', 'dashboards/super_admin/gym_payments.php'];
    
    foreach ($pagesToNest as $url) {
        $pdo->prepare("UPDATE sys_pages SET parent_id = ? WHERE page_url = ?")
            ->execute([$parentId, $url]);
    }
    
    echo "Gym Staff and Finance have been nested UNDER the Admin Dashboard successfully.\n";
} else {
    echo "Dashboard (index.php) not found in sys_pages.\n";
}
?>
