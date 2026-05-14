<?php
require_once 'core/db.php';

$password = '123';
$hash = password_hash($password, PASSWORD_DEFAULT);

// Update all users to have '123' as password using Bcrypt
$stmt = $pdo->prepare("UPDATE users SET password = ?");
$stmt->execute([$hash]);

echo "All user passwords have been reset to '123' using secure Bcrypt hashing.\n";
echo "You can now login with email and password: 123\n";
?>
