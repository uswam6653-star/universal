<?php 
require_once '../../includes/header.php'; 

// Handle Create Installments
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_installments'])) {
    $invoice_id = $_POST['invoice_id'];
    $num_installments = $_POST['num_installments'];
    $interval_days = $_POST['interval_days'];

    $invStmt = $pdo->prepare("SELECT payable_amount FROM invoices WHERE id = ?");
    $invStmt->execute([$invoice_id]);
    $invoice = $invStmt->fetch();

    if ($invoice) {
        $amount_per_inst = $invoice['payable_amount'] / $num_installments;
        
        try {
            $pdo->beginTransaction();
            
            // Delete existing installments for this invoice if any (for reset)
            $pdo->prepare("DELETE FROM installments WHERE invoice_id = ?")->execute([$invoice_id]);

            for ($i = 1; $i <= $num_installments; $i++) {
                $due_date = date('Y-m-d', strtotime("+" . ($i * $interval_days) . " days"));
                $stmt = $pdo->prepare("INSERT INTO installments (invoice_id, installment_no, amount, due_date, status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt->execute([$invoice_id, $i, $amount_per_inst, $due_date]);
            }

            $pdo->commit();
            $success = "Installment plan created for $num_installments installments.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Fetch Invoices for management
$invoices = $pdo->query("SELECT i.*, u.name, u.roll_no FROM invoices i JOIN users u ON i.user_id = u.id ORDER BY i.id DESC")->fetchAll();
?>

<div class="card card-primary card-outline">
    <div class="card-header"><h3 class="card-title">Installment Plan Creator</h3></div>
    <div class="card-body">
        <?php if(isset($success)): ?> <div class="alert alert-success"><?= $success ?></div> <?php endif; ?>
        <?php if(isset($error)): ?> <div class="alert alert-danger"><?= $error ?></div> <?php endif; ?>

        <form method="POST" class="row">
            <div class="col-md-5 mb-3">
                <label>Target Invoice (Student)</label>
                <select name="invoice_id" class="form-select" required>
                    <option value="">-- Select Invoice --</option>
                    <?php foreach($invoices as $inv): ?>
                        <option value="<?= $inv['id'] ?>"><?= $inv['roll_no'] ?> - <?= htmlspecialchars($inv['name']) ?> (PKR <?= number_format($inv['payable_amount'], 2) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label>No. of Installments</label>
                <input type="number" name="num_installments" class="form-control" value="2" min="2" max="6" required>
            </div>
            <div class="col-md-2 mb-3">
                <label>Interval (Days)</label>
                <input type="number" name="interval_days" class="form-control" value="30" min="1" required>
            </div>
            <div class="col-md-2 mb-3 d-flex align-items-end">
                <button type="submit" name="create_installments" class="btn btn-primary w-100">Create Plan</button>
            </div>
        </form>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">Active Installment Plans</div>
    <div class="card-body p-0">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Inst #</th>
                    <th>Due Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $insts = $pdo->query("
                    SELECT s.*, u.name, u.roll_no 
                    FROM installments s 
                    JOIN invoices i ON s.invoice_id = i.id 
                    JOIN users u ON i.user_id = u.id 
                    ORDER BY s.due_date ASC LIMIT 20
                ");
                while($inst = $insts->fetch()):
                    $overdue = (strtotime($inst['due_date']) < time() && $inst['status'] === 'pending');
                ?>
                <tr>
                    <td><?= htmlspecialchars($inst['name']) ?> (<?= $inst['roll_no'] ?>)</td>
                    <td><?= $inst['installment_no'] ?></td>
                    <td class="<?= $overdue ? 'text-danger fw-bold' : '' ?>">
                        <?= date('d M Y', strtotime($inst['due_date'])) ?>
                    </td>
                    <td>PKR <?= number_format($inst['amount'], 2) ?></td>
                    <td><span class="badge bg-<?= $inst['status'] === 'paid' ? 'success' : 'warning' ?>"><?= ucfirst($inst['status']) ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>