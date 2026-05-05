<?php
$files = [
    'dashboards/super_admin/manage_customers.php',
    'dashboards/super_admin/manage_staff.php',
    'dashboards/super_admin/manage_users.php'
];

echo "<h3>File Diagnostic</h3>";
foreach ($files as $f) {
    if (file_exists($f)) {
        echo "FILE EXISTS: $f (Size: " . filesize($f) . " bytes)<br>";
    } else {
        echo "NOT FOUND: $f<br>";
    }
}
?>
