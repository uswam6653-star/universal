<?php
require_once __DIR__ . '/core/db.php';
$queries = [
    "SELECT COUNT(*) FROM sys_activity_logs",
    "SELECT COUNT(*) FROM complaints",
    "SELECT COUNT(*) FROM programs",
    "SELECT COUNT(*) FROM semesters",
    "SELECT COUNT(*) FROM users"
];
foreach ($queries as $q) {
    try {
        $count = $pdo->query($q)->fetchColumn();
        echo "$q: SUCCESS (Count: $count)\n";
    } catch (Exception $e) {
        echo "$q: FAILED (Error: " . $e->getMessage() . ")\n";
    }
}
?>
