<?php
require_once 'core/db.php';

// Remove the duplicate Trainer Dashboard (ID 1003)
$pdo->prepare("DELETE FROM role_access WHERE page_id = 1003")->execute();
$pdo->prepare("DELETE FROM sys_pages WHERE id = 1003")->execute();

// Also remove the empty "Trainer Portal" header if it exists
$pdo->prepare("DELETE FROM role_access WHERE page_id = 902")->execute();
$pdo->prepare("DELETE FROM sys_pages WHERE id = 902")->execute();

echo "Duplicate Trainer Dashboard and redundant headers removed successfully.\n";
?>
