<?php
require_once 'core/db.php';
try {
    $pdo->exec("ALTER TABLE complaints ADD COLUMN IF NOT EXISTS priority VARCHAR(20) DEFAULT 'Medium'");
    echo "Successfully added 'priority' column to complaints table.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
