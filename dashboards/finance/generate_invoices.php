<?php 
require_once '../../includes/header.php'; 

// Handle Bulk Generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_invoices'])) {
    $semester_id = $_POST['semester_id'];
    
    // 1. Get Fee Structure
    $feeStmt = $pdo->prepare("SELECT * FROM fee_structures WHERE semester_id = ?");
    $feeStmt->execute([$semester_id]);
    $fs = $feeStmt->fetch();
    
    if (!$fs) {
        $error = "No fee structure defined for this semester!";
    } else {
        $total_base = $fs['base_fee'] + $fs['lab_charges'] + $fs['library_fee'] + $fs['hostel_fee'];
        
        // 2. Fetch Students in this semester
        $stuStmt = $pdo->prepare("SELECT id, scholarship_percent FROM users WHERE semester_id = ? AND role = 'student'");
        $stuStmt->execute([$semester_id]);
        $students = $stuStmt->fetchAll();
        
        $count = 0;
        foreach($students as $s) {
            $discount = ($total_base * $s['scholarship_percent']) / 100;
            $payable = $total_base - $discount;
            
            // Check if already generated for this semester to avoid duplicates
            $check = $pdo->prepare("SELECT id FROM invoices WHERE user_id = ? AND semester_id = ?");
            $check->execute([$s['id'], $semester_id]);
            
            if ($check->rowCount() == 0) {
                $invStmt = $pdo->prepare("INSERT INTO invoices (user_id, semester_id, total_base_amount, discount_amount, payable_amount, balance_due, status) VALUES (?, ?, ?, ?, ?, ?, 'unpaid')");
                $invStmt->execute([$s['id'], $semester_id, $total_base, $discount, $payable, $payable]);
                $count++;
            }
        }
        $success = "Successfully generated $count new invoices.";
    }
}

// Fetch semesters for selection
$semesters = $pdo->query("
    SELECT s.*, p.name as program_name 
    FROM semesters s 
    JOIN programs p ON s.program_id = p.id 
    ORDER BY p.name, s.number
")->fetchAll();
?>

<div class="card card-warning card-outline">
    <div class="card-header"><h3 class="card-title">Bulk Fee Invoice Generation</h3></div>
    <div class="card-body">
        <?php if(isset($success)): ?> <div class="alert alert-success"><?= $success ?></div> <?php endif; ?>
        <?php if(isset($error)): ?> <div class="alert alert-danger"><?= $error ?></div> <?php endif; ?>

        <form method="POST" class="row align-items-end">
            <div class="col-md-8">
                <label class="form-label">Target Semester</label>
                <select name="semester_id" class="form-select" required>
                    <option value="">-- Choose Semester to Bill --</option>
                    <?php foreach($semesters as $sem): ?>
                        <option value="<?= $sem['id'] ?>"><?= $sem['program_name'] ?> - <?= $sem['name'] ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Invoices will be created for all students assigned to this semester.</small>
            </div>
            <div class="col-md-4">
                <button type="submit" name="generate_invoices" class="btn btn-warning w-100">
                    <i class="bi bi-magic me-1"></i> Generate Invoices
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><h3 class="card-title">Recently Generated Invoices</h3></div>
    <div class="card-body p-0">
        <table class="table table-sm table-striped">
            <thead>
                <tr>
                    <th>Inv #</th>
                    <th>Student Name</th>
                    <th>Roll No</th>
                    <th>Payable</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $recent = $pdo->query("
                    SELECT i.*, u.name, u.roll_no 
                    FROM invoices i 
                    JOIN users u ON i.user_id = u.id 
                    ORDER BY i.id DESC LIMIT 10
                ");
                while($r = $recent->fetch()): ?>
                <tr>
                    <td>#<?= $r['id'] ?></td>
                    <td><?= htmlspecialchars($r['name']) ?></td>
                    <td><?= $r['roll_no'] ?></td>
                    <td><?= number_format($r['payable_amount'], 2) ?></td>
                    <td class="text-danger"><?= number_format($r['balance_due'], 2) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>