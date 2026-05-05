<?php
require_once 'core/db.php';
$stmt = $pdo->query("SELECT role_key, role_name FROM role_access GROUP BY role_key");
while ($row = $stmt->fetch()) {
    echo "Key: {$row['role_key']} | Name: {$row['role_key']}\n";
}
?>
