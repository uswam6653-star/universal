<?php
require_once 'core/db.php';
$stmt = $pdo->query("SELECT ra.*, sp.page_name, sp.page_url FROM role_access ra JOIN sys_pages sp ON ra.page_id = sp.id WHERE ra.role_key = 'trainer'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
