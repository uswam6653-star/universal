<?php
require_once 'core/db.php';

$to_remove = [
    'dashboards/super_admin/manage_programs.php',
    'dashboards/super_admin/manage_semesters.php',
    'dashboards/super_admin/manage_departments.php',
    'dashboards/hod/department_report.php',
    'dashboards/finance/manage_fees.php',
    'dashboards/finance/installments.php',
    'dashboards/finance/fine_engine.php',
    'dashboards/finance/generate_invoices.php',
    'dashboards/finance/reports.php',
    'dashboards/clerk/vouchers.php'
];

foreach ($to_remove as $url) {
    // Delete from role_access first
    $pdo->prepare("DELETE FROM role_access WHERE page_id IN (SELECT id FROM sys_pages WHERE page_url LIKE ?)")->execute(["%$url%"]);
    // Delete from sys_pages
    $pdo->prepare("DELETE FROM sys_pages WHERE page_url LIKE ?")->execute(["%$url%"]);
    echo "Removed: $url\n";
}

// Also rename some categories if they exist
$pdo->prepare("UPDATE sys_pages SET page_name = 'Membership Hub' WHERE page_name = 'System Configuration'")->execute();
?>
