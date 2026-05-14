<?php
require_once 'core/db.php';

// Set Admin Dashboard to Order 1
$pdo->prepare("UPDATE sys_pages SET sort_order = 1 WHERE page_url = 'index.php'")->execute();

// Set Trainer Dashboard to Order 2
$pdo->prepare("UPDATE sys_pages SET sort_order = 2 WHERE page_url = 'dashboards/trainer/dashboard.php'")->execute();

echo "Menu Order Updated: 1. Admin Dashboard, 2. Trainer Dashboard.\n";
?>
