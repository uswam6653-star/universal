<?php
require_once __DIR__ . '/core/db.php';
$stmt = $pdo->query("SHOW TABLES");
print_r($stmt->fetchAll());
?>
