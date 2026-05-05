<?php
require_once 'core/db.php';
$email = 'admin@sys.com';
$newPass = 'admin123';
$hash = password_hash($newPass, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
if ($stmt->execute([$hash, $email])) {
    echo "SUCCESS: Password for $email has been reset to: $newPass\n";
} else {
    echo "FAILURE: Could not reset password.\n";
}
?>
