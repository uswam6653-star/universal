<?php
require_once 'core/db.php';

// 1. Add Trainer Dashboard to sys_pages if it doesn't exist
$pages = [
    ['page_name' => 'Trainer Dashboard', 'page_url' => 'dashboards/trainer/dashboard.php', 'icon_class' => 'bi bi-speedometer2', 'parent_id' => 0, 'sort_order' => 1],
    ['page_name' => 'My Members', 'page_url' => 'dashboards/trainer/manage_members.php', 'icon_class' => 'bi bi-people', 'parent_id' => 0, 'sort_order' => 2]
];

foreach ($pages as $p) {
    $stmt = $pdo->prepare("SELECT id FROM sys_pages WHERE page_url = ?");
    $stmt->execute([$p['page_url']]);
    $pageId = $stmt->fetchColumn();

    if (!$pageId) {
        $stmt = $pdo->prepare("INSERT INTO sys_pages (page_name, page_url, icon_class, parent_id, sort_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$p['page_name'], $p['page_url'], $p['icon_class'], $p['parent_id'], $p['sort_order']]);
        $pageId = $pdo->lastInsertId();
        echo "Created page: {$p['page_name']}\n";
    }

    // 2. Give access to trainer role
    $stmt = $pdo->prepare("SELECT 1 FROM role_access WHERE role_key = 'trainer' AND page_id = ?");
    $stmt->execute([$pageId]);
    if (!$stmt->fetchColumn()) {
        $stmt = $pdo->prepare("INSERT INTO role_access (role_key, page_id) VALUES ('trainer', ?)");
        $stmt->execute([$pageId]);
        echo "Gave trainer access to: {$p['page_name']}\n";
    }
}

// 3. Also give access to workout plans and progress logs (which are in hod/ admin/ etc)
$other_pages = ['dashboards/hod/workout_plans.php', 'dashboards/hod/member_progress.php'];
foreach ($other_pages as $url) {
    $stmt = $pdo->prepare("SELECT id FROM sys_pages WHERE page_url LIKE ?");
    $stmt->execute(["%$url%"]);
    $pageId = $stmt->fetchColumn();

    if ($pageId) {
        $stmt = $pdo->prepare("SELECT 1 FROM role_access WHERE role_key = 'trainer' AND page_id = ?");
        $stmt->execute([$pageId]);
        if (!$stmt->fetchColumn()) {
            $stmt = $pdo->prepare("INSERT INTO role_access (role_key, page_id) VALUES ('trainer', ?)");
            $stmt->execute([$pageId]);
            echo "Gave trainer access to: $url\n";
        }
    }
}
?>
