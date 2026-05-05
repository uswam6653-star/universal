<?php
require_once 'core/db.php';
$stmt = $pdo->query("SELECT * FROM sys_pages WHERE page_name LIKE '%Dashboard%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
