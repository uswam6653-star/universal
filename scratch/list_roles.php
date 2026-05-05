<?php
require_once 'core/db.php';
$stmt = $pdo->query("SELECT * FROM sys_roles");
print_r($stmt->fetchAll());
?>
