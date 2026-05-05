<?php
require_once 'core/db.php';
$tables = ['complaints', 'sys_activity_logs'];
foreach ($tables as $t) {
    echo "\nTable: $t\n";
    try {
        $stmt = $pdo->query("DESCRIBE $t");
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
