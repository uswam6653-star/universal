<?php
require_once 'core/db.php';
$stmt = $pdo->query("SELECT id, name, role FROM users WHERE name LIKE '%Senior Trainer%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
