<?php
$files = [
    'dashboards/clerk/vouchers.php' => <<<'CODE'
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
CODE,
    'dashboards/clerk/verify_payments.php' => <<<'CODE'
<?php 
require_once '../../includes/header.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_id = $_POST['payment_id'];
    $invoice_id = $_POST['invoice_id'];
    $action = $_POST['action']; 
    
    if ($action === 'verify') {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE payments SET verification_status = 'verified' WHERE id = ?");
            $stmt->execute([$payment_id]);
            
            $pStmt = $pdo->prepare("SELECT amount FROM payments WHERE id = ?");
            $pStmt->execute([$payment_id]);
            $amount_paid = $pStmt->fetchColumn();

            $inv = $pdo->prepare("SELECT balance_due FROM invoices WHERE id = ?");
            $inv->execute([$invoice_id]);
            $current_balance = $inv->fetchColumn();
            
            $new_balance = $current_balance - $amount_paid;
            $status = ($new_balance <= 0) ? 'paid' : 'partial';
            
            $upd = $pdo->prepare("UPDATE invoices SET balance_due = ?, status = ? WHERE id = ?");
            $upd->execute([$new_balance, $status, $invoice_id]);
            
            $pdo->commit();
            $success = "Payment verified and invoice updated!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $stmt = $pdo->prepare("UPDATE payments SET verification_status = 'rejected' WHERE id = ?");
        $stmt->execute([$payment_id]);
        $error = "Payment proof rejected.";
    }
}

$pendings = $pdo->query("
    SELECT p.*, i.payable_amount, i.balance_due, u.name as student_name, u.roll_no 
    FROM payments p 
    JOIN invoices i ON p.invoice_id = i.id 
    JOIN users u ON i.user_id = u.id 
    WHERE p.verification_status = 'pending' 
    ORDER BY p.paid_date DESC
")->fetchAll();
?>

<div class="card card-warning card-outline">
    <div class="card-header"><h3 class="card-title">Payment Verification Queue</h3></div>
    <div class="card-body p-0">
        <?php if(isset($success)): ?> <div class="alert alert-success m-3"><?= $success ?></div> <?php endif; ?>
        <?php if(isset($error)): ?> <div class="alert alert-danger m-3"><?= $error ?></div> <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Student</th>
                        <th>Amount Paid</th>
                        <th>Method</th>
                        <th>Proof Slip</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pendings as $p): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['student_name']) ?></strong><br><small><?= $p['roll_no'] ?></small></td>
                        <td>PKR <?= number_format($p['amount'], 2) ?></td>
                        <td><?= $p['payment_method'] ?></td>
                        <td>
                            <?php if($p['proof_image']): ?>
                                <a href="../../<?= $p['proof_image'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-image me-1"></i> View Slip
                                </a>
                            <?php else: ?>
                                <span class="text-muted">No image</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="invoice_id" value="<?= $p['invoice_id'] ?>">
                                <button type="submit" name="action" value="verify" class="btn btn-sm btn-success">
                                    <i class="bi bi-check-circle me-1"></i> Approve
                                </button>
                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">
                                    <i class="bi bi-x-circle me-1"></i> Reject
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($pendings)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No pending payments for verification.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
CODE
];

foreach ($files as $path => $content) {
    $full_path = __DIR__ . '/' . $path;
    $dir = dirname($full_path);
    if (!is_dir($dir))
        mkdir($dir, 0777, true);
    if (file_put_contents($full_path, $content)) {
        echo "[ OK ] Restored: $path (" . strlen($content) . " bytes)\n";
    }
    else {
        echo "[ FAIL ] Could not write: $path\n";
    }
}
