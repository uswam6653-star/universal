<?php
require_once 'core/db.php';
$staff_roles = ['hod', 'clerk', 'trainer', 'gym_manager'];
$placeholders = implode(',', array_fill(0, count($staff_roles), '?'));
$stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE role IN ($placeholders)");
$stmt->execute($staff_roles);
$staff = $stmt->fetchAll();

echo "=== Staff Restored Check ===\n";
echo "Total Staff found: " . count($staff) . "\n";
foreach ($staff as $s) {
    echo "- ID: {$s['id']} | Name: {$s['name']} | Role: {$s['role']}\n";
}
echo "============================\n";

// Also check manage_staff.php content to verify the fix is applied
$content = file_get_contents('dashboards/super_admin/manage_staff.php');
if (strpos($content, "'hod', 'clerk', 'trainer', 'gym_manager'") !== false) {
    echo "SUCCESS: manage_staff.php updated with broad role filter.\n";
} else {
    echo "FAILURE: manage_staff.php missing broad role filter.\n";
}
?>
