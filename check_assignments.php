<?php
require_once 'core/db.php';
$stmt = $pdo->query("SELECT * FROM complaints WHERE subject = 'WORKOUT'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
