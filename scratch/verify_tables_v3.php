<?php
require_once 'core/db.php';
echo "Connected to: " . DB_NAME . "\n";
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);

foreach($tables as $t) {
    if($t == 'programs') {
        echo "\n--- Content of programs table ---\n";
        print_r($pdo->query("SELECT * FROM programs")->fetchAll(PDO::FETCH_ASSOC));
    }
}
?>
