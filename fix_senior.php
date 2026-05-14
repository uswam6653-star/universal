<?php
require_once 'core/db.php';
// Change Senior Trainer role to trainer
$pdo->exec("UPDATE users SET role = 'trainer' WHERE name = 'Senior Trainer'");
echo "Senior Trainer role updated to trainer.\n";
?>
