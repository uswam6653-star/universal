<?php
require_once 'core/db.php';

// 1. Rename 'Dashboard' to 'Trainer Dashboard' and get its ID
$stmt = $pdo->prepare("SELECT id FROM sys_pages WHERE page_url = 'dashboards/trainer/dashboard.php' LIMIT 1");
$stmt->execute();
$parentId = $stmt->fetchColumn();

if ($parentId) {
    // Update parent name and icon
    $pdo->prepare("UPDATE sys_pages SET page_name = 'Trainer Dashboard', icon_class = 'bi bi-speedometer2' WHERE id = ?")
        ->execute([$parentId]);

    // 2. Set others as children of Trainer Dashboard
    $child_urls = [
        'dashboards/trainer/manage_members.php',
        'dashboards/hod/workout_plans.php',
        'dashboards/trainer/assign_workout.php',
        'dashboards/hod/member_progress.php',
        'dashboards/trainer/training_hub.php'
    ];

    foreach ($child_urls as $url) {
        $pdo->prepare("UPDATE sys_pages SET parent_id = ? WHERE page_url = ?")
            ->execute([$parentId, $url]);
    }

    echo "Trainer Dashboard is now the PARENT menu. All other pages have been nested UNDER it successfully.\n";
} else {
    echo "Trainer Dashboard page not found.\n";
}
?>
