<?php 
require_once __DIR__ . '/../../includes/header.php'; 

$programs = $pdo->query("SELECT * FROM programs")->fetchAll();
$selected_id = $_GET['program_id'] ?? ($programs[0]['id'] ?? 0);

if ($selected_id > 0) {
    $stmt = $pdo->prepare("
        SELECT SUM(payable_amount) as total, SUM(payable_amount - balance_due) as collected, SUM(balance_due) as pending 
        FROM invoices i 
        JOIN semesters s ON i.semester_id = s.id 
        WHERE s.program_id = ?
    ");
    $stmt->execute([$selected_id]);
    $stats = $stmt->fetch();
}
?>

<div class="row mb-3">
    <div class="col-md-4">
        <form method="GET">
            <select name="program_id" class="form-select" onchange="this.form.submit()">
                <?php foreach($programs as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $p['id'] == $selected_id ? 'selected' : '' ?>><?= $p['name'] ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<?php if($selected_id > 0): ?>
<div class="row">
    <div class="col-md-4">
        <div class="info-box bg-primary">
            <span class="info-box-icon"><i class="bi bi-wallet2"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Expected Revenue</span>
                <span class="info-box-number">PKR <?= number_format($stats['total'] ?: 0, 0) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box bg-success text-white">
            <span class="info-box-icon"><i class="bi bi-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Collection</span>
                <span class="info-box-number">PKR <?= number_format($stats['collected'] ?: 0, 0) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box bg-danger text-white">
            <span class="info-box-icon"><i class="bi bi-exclamation-octagon"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Outstanding</span>
                <span class="info-box-number">PKR <?= number_format($stats['pending'] ?: 0, 0) ?></span>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">Program Defaulters List</div>
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0">
            <thead><tr><th>Student</th><th>Roll No</th><th>Outstanding Dues</th></tr></thead>
            <tbody>
                <?php
                $defaulters = $pdo->prepare("
                    SELECT u.name, u.roll_no, i.balance_due 
                    FROM invoices i 
                    JOIN users u ON i.user_id = u.id 
                    JOIN semesters s ON i.semester_id = s.id 
                    WHERE s.program_id = ? AND i.balance_due > 0 
                    ORDER BY i.balance_due DESC
                ");
                $defaulters->execute([$selected_id]);
                while($df = $defaulters->fetch()): ?>
                <tr>
                    <td><?= htmlspecialchars($df['name']) ?></td>
                    <td><?= $df['roll_no'] ?></td>
                    <td class="text-danger fw-bold">PKR <?= number_format($df['balance_due'], 0) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>