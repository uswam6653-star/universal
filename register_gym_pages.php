<?php
require_once __DIR__ . '/core/db.php';

$pages = [
    ['Workout Plans', 'dashboards/hod/workout_plans.php', 'bi-file-text', 0, 15, 'hod'],
    ['Member Progress', 'dashboards/hod/member_progress.php', 'bi-graph-up', 0, 16, 'hod'],
    ['Gym Reports', 'dashboards/super_admin/gym_reports.php', 'bi-pie-chart', 0, 50, 'super_admin'],
    ['Renewals', 'dashboards/clerk/renewals.php', 'bi-arrow-repeat', 0, 25, 'clerk']
];

foreach ($pages as $p) {
    // Insert into sys_pages
    $stmt = $pdo->prepare("INSERT IGNORE INTO sys_pages (page_name, page_url, icon_class, parent_id, sort_order) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$p[0], $p[1], $p[2], $p[3], $p[4]]);
    $pageId = $pdo->lastInsertId();
    
    if($pageId == 0) {
        $st = $pdo->prepare("SELECT id FROM sys_pages WHERE page_url = ?");
        $st->execute([$p[1]]);
        $pageId = $st->fetchColumn();
    }
    
    // Assign access
    $stmt = $pdo->prepare("INSERT IGNORE INTO role_access (role_key, page_id) VALUES (?, ?)");
    $stmt->execute([$p[5], $pageId]);
    echo "Registered: {$p[0]}<br>";
}

echo "Done! You can delete this file now.";
?>
