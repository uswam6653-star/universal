<?php
require_once __DIR__ . '/core/db.php';
$stmt = $pdo->query("SELECT name, email, role FROM users");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
