<?php
include 'core/db.php';
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL AFTER role");
    echo "Column 'avatar' added successfully.";
} catch (Exception $e) {
    echo "Error or column already exists: " . $e->getMessage();
}
?>
