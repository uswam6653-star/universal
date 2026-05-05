<?php
require_once 'core/db.php';
$stmt = $pdo->query("SELECT email, password FROM users");
$users = $stmt->fetchAll();

$common_passwords = ['123', '1234', '12345', '123456', 'password', 'admin123', 'admin', 'gym123', 'azan', '1234'];

foreach ($users as $u) {
    $found = false;
    // Try email prefix
    $prefix = explode('@', $u['email'])[0];
    if (password_verify($prefix, $u['password'])) {
        echo "User: {$u['email']} | Password: {$prefix}\n";
        $found = true;
    }
    
    // Try common passwords
    if (!$found) {
        foreach ($common_passwords as $p) {
            if (password_verify($p, $u['password'])) {
                echo "User: {$u['email']} | Password: {$p}\n";
                $found = true;
                break;
            }
        }
    }
    
    if (!$found) {
        echo "User: {$u['email']} | Password: UNKNOWN\n";
    }
}
?>
