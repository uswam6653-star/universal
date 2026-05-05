<?php
require_once __DIR__ . '/core/db.php';
echo "--- PROGRAMS ---\n";
print_r($pdo->query("DESCRIBE programs")->fetchAll(PDO::FETCH_ASSOC));
echo "\n--- SEMESTERS ---\n";
print_r($pdo->query("DESCRIBE semesters")->fetchAll(PDO::FETCH_ASSOC));
echo "\n--- COURSES ---\n";
print_r($pdo->query("DESCRIBE courses")->fetchAll(PDO::FETCH_ASSOC));
?>
