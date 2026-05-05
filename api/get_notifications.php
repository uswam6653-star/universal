<?php

require_once '../core/session.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];

// Unread count
$unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM sys_notifications WHERE user_id = ? AND is_read = 0");
$unreadStmt->execute([$user_id]);
$unreadCount = $unreadStmt->fetchColumn();

// Latest 5 notifications
$notifStmt = $pdo->prepare("SELECT * FROM sys_notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$notifStmt->execute([$user_id]);
$items = $notifStmt->fetchAll();

// Add helper for time ago (simple version)
foreach ($items as &$item) {
    $item['time_ago'] = 'Just now'; // You can improve this with real time differences
}

echo json_encode([
    'unread' => $unreadCount,
    'items' => $items
]);
?>
