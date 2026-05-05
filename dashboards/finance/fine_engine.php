<?php 
require_once '../../includes/header.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_engine'])) {
    $unpaid = $pdo->query("
        SELECT i.*, f.late_fine_per_day 
        FROM invoices i 
        JOIN fee_structures f ON i.semester_id = f.semester_id 
        WHERE i.status != 'paid'
    ")->fetchAll();

    $count = 0;
    foreach ($unpaid as $inv) {
        $new_fine = $inv['fine_amount'] + ($inv['late_fine_per_day'] * 1);
        $new_payable = ($inv['total_base_amount'] - $inv['discount_amount']) + $new_fine;
        
        $upd = $pdo->prepare("UPDATE invoices SET fine_amount = ?, payable_amount = ?, balance_due = balance_due + ? WHERE id = ?");
        $upd->execute([$new_fine, $new_payable, $inv['late_fine_per_day'], $inv['id']]);
        $count++;
    }
    $success = "Fine Engine processed $count invoices with $+1 day fines.";
}
?>

<div class="card card-warning card-outline text-center p-5">
    <h3>Late Fine Automation Engine</h3>
    <p class="text-muted">Simulate the daily job that adds late fines to overdue invoices.</p>
    
    <?php if(isset($success)): ?> <div class="alert alert-success"><?= $success ?></div> <?php endif; ?>

    <form method="POST" class="mt-4">
        <button type="submit" name="run_engine" class="btn btn-warning btn-lg">
            <i class="bi bi-stopwatch me-1"></i> Apply Daily Late Fines (Simulate 1 Day)
        </button>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>