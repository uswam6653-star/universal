<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'core/db.php';

echo "<h1>Browser Database Check</h1>";
echo "Host: " . DB_HOST . "<br>";
echo "DB: " . DB_NAME . "<br>";

try {
    $stmt = $pdo->query("SELECT * FROM programs");
    echo "✅ Success: Found 'programs' table.<br>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<h2>All Tables:</h2>";
$stmt = $pdo->query("SHOW TABLES");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
?>
