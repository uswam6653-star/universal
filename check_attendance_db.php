<?php
require_once 'core/db.php';
$stmt = $pdo->query("SHOW TABLES");
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $table) {
    if (stripos($table, 'attendance') !== false) {
        echo "Found Table: $table\n";
    }
}
?>
