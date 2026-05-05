<?php
require_once 'core/db.php';
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS roll_no VARCHAR(50)");
    $pdo->exec("UPDATE users SET roll_no = registration_no WHERE roll_no IS NULL OR roll_no = ''");
    echo "Successfully updated users table schema and data.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
