<?php
require_once 'core/db.php';
$stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'super_admin' LIMIT 1");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
?>
