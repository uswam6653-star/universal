<?php 
require_once '../../includes/header.php'; 

$invoice_id = $_GET['invoice_id'] ?? 0;
$stmt = $pdo->prepare("SELECT i.*, u.name FROM invoices i JOIN users u ON i.user_id = u.id WHERE i.id = ?");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $amount = $_POST['amount'];
    $method = $_POST['method'];
    $tx_id = "TXN" . time();

    try {
        $pdo->beginTransaction();
        
        // 1. Record Payment
        $pStmt = $pdo->prepare("INSERT INTO payments (invoice_id, amount, payment_method, transaction_id, status) VALUES (?, ?, ?, ?, 'completed')");
        $pStmt->execute([$invoice_id, $amount, $method, $tx_id]);
        
        // 2. Update Invoice Balance
        $new_balance = $invoice['balance_due'] - $amount;
        $status = ($new_balance <= 0) ? 'paid' : 'partial';
        
        $uStmt = $pdo->prepare("UPDATE invoices SET balance_due = ?, status = ? WHERE id = ?");
        $uStmt->execute([$new_balance, $status, $invoice_id]);
        
        // 3. Mark installments as paid if applicable
        if ($new_balance <= 0) {
            $pdo->prepare("UPDATE installments SET status = 'paid' WHERE invoice_id = ?")->execute([$invoice_id]);
        }

        $pdo->commit();
        echo "<script>alert('Payment Successful!'); window.location.href='my_fees.php';</script>";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Payment failed: " . $e->getMessage();
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card card-success card-outline">
            <div class="card-header"><h3 class="card-title">Process Fee Payment</h3></div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Invoice #<?= $invoice_id ?></strong><br>
                    Payable Amount: PKR <?= number_format($invoice['balance_due'], 2) ?>
                </div>

                <form method="POST">
                    <div class="mb-3">
                        <label>Amount to Pay</label>
                        <input type="number" name="amount" class="form-control form-control-lg" max="<?= $invoice['balance_due'] ?>" value="<?= $invoice['balance_due'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Payment Method</label>
                        <div class="d-flex gap-3">
                            <label><input type="radio" name="method" value="JazzCash" checked> JazzCash</label>
                            <label><input type="radio" name="method" value="EasyPaisa"> EasyPaisa</label>
                            <label><input type="radio" name="method" value="Bank Transfer"> Bank</label>
                        </div>
                    </div>
                    <button type="submit" name="process_payment" class="btn btn-success btn-lg w-100">
                        Confirm & Pay PKR <span id="payAmount"><?= number_format($invoice['balance_due'], 0) ?></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>