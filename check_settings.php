<?php
require_once __DIR__ . '/core/db.php';
$stmt = $pdo->query("DESCRIBE system_settings");
print_r($stmt->fetchAll());
$stmt = $pdo->query("SELECT * FROM system_settings");
print_r($stmt->fetchAll());
?>
