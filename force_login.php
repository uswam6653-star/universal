<?php
session_start();
require_once __DIR__ . '/core/db.php';

// Force super_admin session
$_SESSION['id'] = 1;
$_SESSION['name'] = 'System Admin';
$_SESSION['email'] = 'admin@sys.com';
$_SESSION['role'] = 'super_admin';

echo "Session FORCED. Redirecting to Gym Members in 2 seconds...";
header("Refresh: 2; URL=dashboards/super_admin/manage_users.php");
?>
