<?php 
require_once '../../includes/header.php'; 

$inv_id = $_GET['invoice_id'];
$stmt = $pdo->prepare("
    SELECT i.*, u.name, u.roll_no, s.name as sem_name, p.name as prog_name 
    FROM invoices i 
    JOIN users u ON i.user_id = u.id 
    JOIN semesters s ON i.semester_id = s.id 
    JOIN programs p ON s.program_id = p.id 
    WHERE i.id = ?
");
$stmt->execute([$inv_id]);
$inv = $stmt->fetch();

$payments = $pdo->prepare("SELECT * FROM payments WHERE invoice_id = ?");
$payments->execute([$inv_id]);
$history = $payments->fetchAll();
?>

<div class="container bg-white p-5 border shadow-sm mt-4" id="receiptArea">
    <div class="text-center mb-4">
        <h2>UNIVERSITY FINANCE PORTAL</h2>
        <p class="text-muted">Official Fee Payment Receipt</p>
    </div>
    
    <div class="row mb-4">
        <div class="col-6">
            <strong>Student:</strong> <?= htmlspecialchars($inv['name']) ?><br>
            <strong>Roll No:</strong> <?= $inv['roll_no'] ?><br>
            <strong>Program:</strong> <?= $inv['prog_name'] ?>
        </div>
        <div class="col-6 text-end">
            <strong>Invoice #:</strong> <?= $inv['id'] ?><br>
            <strong>Date:</strong> <?= date('d M Y') ?><br>
            <strong>Status:</strong> <span class="text-uppercase"><?= $inv['status'] ?></span>
        </div>
    </div>

    <table class="table table-bordered">
        <thead class="table-light">
            <tr><th>Description</th><th class="text-end">Amount (PKR)</th></tr>
        </thead>
        <tbody>
            <tr><td>Semester Tuition Fee (<?= $inv['sem_name'] ?>)</td><td class="text-end"><?= number_format($inv['total_base_amount'], 2) ?></td></tr>
            <tr><td>Scholarship Discount</td><td class="text-end text-success">-<?= number_format($inv['discount_amount'], 2) ?></td></tr>
            <tr><td>Late Fines Applied</td><td class="text-end text-danger">+<?= number_format($inv['fine_amount'], 2) ?></td></tr>
            <tr class="table-info"><th>Net Payable</th><th class="text-end">PKR <?= number_format($inv['payable_amount'], 2) ?></th></tr>
        </tbody>
    </table>

    <h5 class="mt-4">Payment History</h5>
    <table class="table table-sm table-striped">
        <thead><tr><th>Date</th><th>Method</th><th>Transaction ID</th><th class="text-end">Amount</th></tr></thead>
        <tbody>
            <?php foreach($history as $p): ?>
            <tr>
                <td><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                <td><?= $p['payment_method'] ?></td>
                <td><?= $p['transaction_id'] ?></td>
                <td class="text-end">PKR <?= number_format($p['amount'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="text-end mt-5">
        <h4 class="text-danger">Balance Due: PKR <?= number_format($inv['balance_due'], 2) ?></h4>
    </div>
</div>

<div class="text-center mt-4 no-print">
    <button onclick="window.print()" class="btn btn-secondary btn-lg"><i class="bi bi-printer me-2"></i>Print Receipt</button>
</div>

<?php require_once '../../includes/footer.php'; ?>