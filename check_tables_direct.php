<?php
require_once __DIR__ . '/core/db.php';
$tables = ['programs', 'semesters', 'complaints', 'invoices', 'payments', 'installments', 'fee_structures'];
foreach ($tables as $t) {
    echo "--- $t ---\n";
    try {
        $stmt = $pdo->query("DESCRIBE $t");
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "{$row['Field']} ({$row['Type']})\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
