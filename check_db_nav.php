<?php
require_once __DIR__ . '/core/db.php';
$pages = $pdo->query("SELECT * FROM sys_pages ORDER BY id DESC LIMIT 10")->fetchAll();
print_r($pages);
echo "\n--- role_access ---\n";
$access = $pdo->query("SELECT * FROM role_access ORDER BY page_id DESC LIMIT 10")->fetchAll();
print_r($access);
?>
