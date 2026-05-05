<?php
require_once 'core/db.php';

$stmt = $pdo->prepare("SELECT id FROM sys_pages WHERE page_url = ?");
$stmt->execute(['dashboards/student/member_portal.php']);
$pageId = $stmt->fetchColumn();

if (!$pageId) {
    $stmt = $pdo->prepare("INSERT INTO sys_pages (page_name, page_url, icon_class, parent_id, sort_order) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['My Member Portal', 'dashboards/student/member_portal.php', 'bi bi-person-badge', 0, 1]);
    $pageId = $pdo->lastInsertId();
}

$stmt = $pdo->prepare("INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('student', ?)");
$stmt->execute([$pageId]);
echo "DONE";
?>
