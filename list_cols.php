<?php
require_once __DIR__ . '/core/db.php';
$stmt = $pdo->query("DESCRIBE users");
print_r($stmt->fetchAll());
?>
