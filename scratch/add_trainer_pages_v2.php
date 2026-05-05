<?php
require_once 'core/db.php';

$pages = [
    ['page_name' => 'Workout Plans', 'page_url' => 'dashboards/hod/workout_plans.php', 'icon_class' => 'bi bi-clipboard2-check', 'parent_id' => 0, 'sort_order' => 3],
    ['page_name' => 'Member Progress', 'page_url' => 'dashboards/hod/member_progress.php', 'icon_class' => 'bi bi-graph-up', 'parent_id' => 0, 'sort_order' => 4]
];

foreach ($pages as $p) {
    $stmt = $pdo->prepare("SELECT id FROM sys_pages WHERE page_url = ?");
    $stmt->execute([$p['page_url']]);
    $pageId = $stmt->fetchColumn();

    if (!$pageId) {
        $stmt = $pdo->prepare("INSERT INTO sys_pages (page_name, page_url, icon_class, parent_id, sort_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$p['page_name'], $p['page_url'], $p['icon_class'], $p['parent_id'], $p['sort_order']]);
        $pageId = $pdo->lastInsertId();
    }

    $stmt = $pdo->prepare("INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('trainer', ?)");
    $stmt->execute([$pageId]);
}
echo "DONE";
?>
