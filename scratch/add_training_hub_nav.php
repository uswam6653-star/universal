<?php
require_once 'core/db.php';

$stmt = $pdo->prepare("SELECT id FROM sys_pages WHERE page_url = ?");
$stmt->execute(['dashboards/super_admin/manage_training.php']);
$pageId = $stmt->fetchColumn();

if (!$pageId) {
    $stmt = $pdo->prepare("INSERT INTO sys_pages (page_name, page_url, icon_class, parent_id, sort_order) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['Training Hub', 'dashboards/super_admin/manage_training.php', 'bi bi-person-bounding-box', 0, 10]);
    $pageId = $pdo->lastInsertId();
}

$stmt = $pdo->prepare("INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('super_admin', ?)");
$stmt->execute([$pageId]);
echo "DONE";
?>
