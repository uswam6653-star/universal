<?php
require_once 'core/session.php';
echo "Roles in DB:\n";
$stmt = $pdo->query("SELECT * FROM sys_roles");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}

echo "\nSample Users:\n";
$stmt = $pdo->query("SELECT id, name, email, role FROM users LIMIT 10");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
?>
