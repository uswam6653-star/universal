<?php
require_once 'core/db.php';
$stmt = $pdo->query("SELECT id, name, email, role, is_active FROM users");
$users = $stmt->fetchAll();
echo "Total Users: " . count($users) . "\n";
foreach ($users as $u) {
    echo "ID: {$u['id']} | Name: {$u['name']} | Email: {$u['email']} | Role: {$u['role']} | Active: {$u['is_active']}\n";
}
?>
