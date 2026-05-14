<?php
require_once 'core/db.php';

$roles = ['trainer', 'hod'];

// 1. Wipe current trainer access
$pdo->prepare("DELETE FROM role_access WHERE role_key IN ('trainer', 'hod')")->execute();

// 2. Define the new menu structure
$menu = [
    ['name' => 'Dashboard', 'url' => 'dashboards/trainer/dashboard.php', 'icon' => 'bi bi-speedometer2', 'order' => 1],
    ['name' => 'My Clients', 'url' => 'dashboards/trainer/manage_members.php', 'icon' => 'bi bi-people', 'order' => 2],
    ['name' => 'Workout Plans', 'url' => 'dashboards/hod/workout_plans.php', 'icon' => 'bi bi-clipboard-data', 'order' => 3],
    ['name' => 'Progress Tracking', 'url' => 'dashboards/hod/member_progress.php', 'icon' => 'bi bi-graph-up', 'order' => 4],
    ['name' => 'Exercise Library', 'url' => 'dashboards/trainer/training_hub.php', 'icon' => 'bi bi-universal-access', 'order' => 5]
];

foreach ($menu as $item) {
    // a. Check if page exists in sys_pages, if not create it
    $stmt = $pdo->prepare("SELECT id FROM sys_pages WHERE page_url = ?");
    $stmt->execute([$item['url']]);
    $pageId = $stmt->fetchColumn();

    if ($pageId) {
        // Update existing page
        $pdo->prepare("UPDATE sys_pages SET page_name = ?, icon_class = ?, sort_order = ?, parent_id = 0 WHERE id = ?")
            ->execute([$item['name'], $item['icon'], $item['order'], $pageId]);
    } else {
        // Insert new page
        $pdo->prepare("INSERT INTO sys_pages (page_name, page_url, icon_class, sort_order, parent_id) VALUES (?, ?, ?, ?, 0)")
            ->execute([$item['name'], $item['url'], $item['icon'], $item['order']]);
        $pageId = $pdo->lastInsertId();
    }

    // b. Grant access to both trainer and hod
    foreach ($roles as $role) {
        $pdo->prepare("INSERT IGNORE INTO role_access (role_key, page_id) VALUES (?, ?)")
            ->execute([$role, $pageId]);
    }
}

// 3. Delete any other pages that might still be in sys_pages but shouldn't be for trainers
// (The sidebar logic handles this via role_access anyway, but cleaning up is good)

echo "Trainer Portal Menu has been REBUILT and ARRANGE successfully!\n";
echo "1. Dashboard\n2. My Clients\n3. Workout Plans\n4. Progress Tracking\n5. Exercise Library\n";
?>
