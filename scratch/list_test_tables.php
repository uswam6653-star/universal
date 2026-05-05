<?php
try {
    $p = new PDO('mysql:host=localhost;dbname=test', 'root', '');
    $stmt = $p->query("SHOW TABLES");
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $t) {
        echo "$t\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
