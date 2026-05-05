<?php 
require_once '../../includes/header.php'; 

$unpaid = $pdo->query("
    SELECT i.*, u.name, u.email, u.roll_no 
    FROM invoices i 
    JOIN users u ON i.user_id = u.id 
    WHERE i.status != 'paid' 
    ORDER BY i.balance_due DESC
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_alert'])) {
    $inv_id = $_POST['invoice_id'];
    $type = $_POST['alert_type'];
    $success = "Process Success: $type sent for Invoice #$inv_id.";
}
?>

<div class="card card-danger card-outline">
    <div class="card-header"><h3 class="card-title">Fee Notification Simulator</h3></div>
    <div class="card-body p-0">
        <?php if(isset($success)): ?> <div class="alert alert-success m-3"><?= $success ?></div> <?php endif; ?>
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Student Details</th>
                    <th>Balance</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($unpaid as $u): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($u['name']) ?></strong> (<?= $u['roll_no'] ?>)<br>
                        <small class="text-muted"><?= $u['email'] ?></small>
                    </td>
                    <td class="text-danger fw-bold">PKR <?= number_format($u['balance_due'], 0) ?></td>
                    <td>
                        <form method="POST" class="d-flex gap-2">
                            <input type="hidden" name="invoice_id" value="<?= $u['id'] ?>">
                            <select name="alert_type" class="form-select form-select-sm" style="width: auto;">
                                <option value="Email Reminder">Email</option>
                                <option value="SMS Notification">SMS</option>
                                <option value="Late Fine Alert">Fine Warning</option>
                            </select>
                            <button type="submit" name="send_alert" class="btn btn-danger btn-sm">Alert</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>