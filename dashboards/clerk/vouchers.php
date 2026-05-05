<?php 
require_once '../../includes/header.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dispatch_vouchers'])) {
    $invoice_ids = $_POST['invoice_ids'] ?? [];
    if (!empty($invoice_ids)) {
        $placeholders = implode(',', array_fill(0, count($invoice_ids), '?'));
        $stmt = $pdo->prepare("UPDATE invoices SET status = 'unpaid' WHERE id IN ($placeholders) AND status = 'draft'");
        $stmt->execute($invoice_ids);
        $success = count($invoice_ids) . " vouchers dispatched to students successfully!";
    }
}

$drafts = $pdo->query("
    SELECT i.*, u.name as student_name, u.roll_no, p.name as prog_name, s.name as sem_name 
    FROM invoices i 
    JOIN users u ON i.user_id = u.id 
    JOIN semesters s ON i.semester_id = s.id 
    JOIN programs p ON s.program_id = p.id 
    WHERE i.status = 'draft' 
    ORDER BY i.created_at DESC
")->fetchAll();
?>

<div class="card card-info card-outline">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Voucher Dispatch Center</h3>
    </div>
    <div class="card-body">
        <?php if(isset($success)): ?> <div class="alert alert-success"><?= $success ?></div> <?php endif; ?>
        
        <form method="POST">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="40"><input type="checkbox" id="selectAll"></th>
                            <th>Invoice #</th>
                            <th>Student</th>
                            <th>Program/Semester</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($drafts as $d): ?>
                        <tr>
                            <td><input type="checkbox" name="invoice_ids[]" value="<?= $d['id'] ?>" class="rowCheck"></td>
                            <td>#INV-<?= $d['id'] ?></td>
                            <td><strong><?= htmlspecialchars($d['student_name']) ?></strong><br><small><?= $d['roll_no'] ?></small></td>
                            <td><?= $d['prog_name'] ?> - <?= $d['sem_name'] ?></td>
                            <td>PKR <?= number_format($d['payable_amount'], 2) ?></td>
                            <td><span class="badge bg-secondary"><?= strtoupper($d['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($drafts)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No pending draft vouchers for dispatch.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if(!empty($drafts)): ?>
            <div class="mt-3">
                <button type="submit" name="dispatch_vouchers" class="btn btn-info btn-lg">
                    <i class="bi bi-send-check me-1"></i> Dispatch Selected to Students
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
document.getElementById('selectAll')?.addEventListener('change', function() {
    const checks = document.querySelectorAll('.rowCheck');
    checks.forEach(c => c.checked = this.checked);
});
</script>

<?php require_once '../../includes/footer.php'; ?>