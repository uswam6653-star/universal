<?php
require_once 'core/db.php';
$stmt = $pdo->query("SELECT id, name, email, identity_no FROM users WHERE role = 'student' ORDER BY id DESC LIMIT 10");
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Recent Members:\n";
foreach ($members as $m) {
    echo "ID: {$m['id']}, Name: {$m['name']}, Email: {$m['email']}, Meta: {$m['identity_no']}\n";
}
?>
