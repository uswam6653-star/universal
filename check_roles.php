<?php
require_once 'core/db.php';
$stmt = $pdo->query("SELECT * FROM sys_roles");
while ($r = $stmt->fetch()) {
    echo $r['role_key'] . " | " . $r['role_name'] . "\n";
}
?>
