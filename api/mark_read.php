<?php

require_once '../core/session.php';

$id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

$pdo->prepare("UPDATE sys_notifications SET is_read = 1 WHERE id = ? AND user_id = ?")
    ->execute([$id, $user_id]);
?>
