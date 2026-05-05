<?php
/**
 * Late Fine Automation Script (CLI Version)
 * Run this via command line: php cron_fines.php
 * Or set up a CRON job to run daily.
 */

if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

require_once __DIR__ . '/core/db.php';

echo "[ " . date('Y-m-d H:i:s') . " ] Starting Fine Calculation Engine...\n";

try {
    // Fetch all unpaid invoices that have a late fine policy
    $unpaid = $pdo->query("
        SELECT i.*, f.late_fine_per_day 
        FROM invoices i 
        JOIN fee_structures f ON i.semester_id = f.semester_id 
        WHERE i.status != 'paid' AND f.late_fine_per_day > 0
    ")->fetchAll();

    $count = 0;
    foreach ($unpaid as $inv) {
        $days_overdue = 1; // Assuming it runs daily
        $new_fine = $inv['fine_amount'] + ($inv['late_fine_per_day'] * $days_overdue);
        $new_payable = ($inv['total_base_amount'] - $inv['discount_amount']) + $new_fine;

        $upd = $pdo->prepare("UPDATE invoices SET fine_amount = ?, payable_amount = ?, balance_due = balance_due + ? WHERE id = ?");
        $upd->execute([$new_fine, $new_payable, ($inv['late_fine_per_day'] * $days_overdue), $inv['id']]);
        $count++;
        echo " - Updated Invoice #{$inv['id']}: Added PKR " . ($inv['late_fine_per_day'] * $days_overdue) . " fine.\n";
    }

    echo "[ FINISHED ] Processed $count invoices.\n";
}
catch (Exception $e) {
    echo "[ ERROR ] " . $e->getMessage() . "\n";
}
?>
