<?php 
require_once '../../includes/header.php'; 

// Handle Save/Update Fee Structure
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_fee'])) {
    $semester_id = $_POST['semester_id'];
    $base_fee = $_POST['base_fee'];
    $lab_charges = $_POST['lab_charges'] ?: 0;
    $library_fee = $_POST['library_fee'] ?: 0;
    $hostel_fee = $_POST['hostel_fee'] ?: 0;
    $credit_rate = $_POST['credit_hour_rate'] ?: 0;
    $late_fine = $_POST['late_fine_per_day'] ?: 0;

    // Check if exists
    $check = $pdo->prepare("SELECT id FROM fee_structures WHERE semester_id = ?");
    $check->execute([$semester_id]);
    $existing = $check->fetch();

    if ($existing) {
        $sql = "UPDATE fee_structures SET base_fee=?, lab_charges=?, library_fee=?, hostel_fee=?, credit_hour_rate=?, late_fine_per_day=? WHERE semester_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$base_fee, $lab_charges, $library_fee, $hostel_fee, $credit_rate, $late_fine, $semester_id]);
    } else {
        $sql = "INSERT INTO fee_structures (semester_id, base_fee, lab_charges, library_fee, hostel_fee, credit_hour_rate, late_fine_per_day) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$semester_id, $base_fee, $lab_charges, $library_fee, $hostel_fee, $credit_rate, $late_fine]);
    }
    echo "<script>alert('Fee structure updated successfully!'); window.location.href='manage_fees.php';</script>";
}

// Fetch semesters and their fee structures
$semesters = $pdo->query("
    SELECT s.*, p.name as program_name, f.base_fee, f.lab_charges, f.library_fee, f.hostel_fee, f.late_fine_per_day 
    FROM semesters s 
    JOIN programs p ON s.program_id = p.id 
    LEFT JOIN fee_structures f ON s.id = f.semester_id 
    ORDER BY p.name, s.number
")->fetchAll();
?>

<div class="card card-primary card-outline">
    <div class="card-header"><h3 class="card-title">Define Semester Fee Structures</h3></div>
    <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Program / Semester</th>
                    <th>Base Fee</th>
                    <th>Lab/Lib</th>
                    <th>Hostel</th>
                    <th>Late Fine</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($semesters as $s): ?>
                <tr>
                    <td><strong><?= $s['program_name'] ?></strong><br><small><?= $s['name'] ?></small></td>
                    <td>PKR <?= number_format($s['base_fee'] ?: 0, 2) ?></td>
                    <td><?= number_format($s['lab_charges'] + $s['library_fee'], 0) ?></td>
                    <td><?= number_format($s['hostel_fee'] ?: 0, 0) ?></td>
                    <td><?= number_format($s['late_fine_per_day'] ?: 0, 0) ?>/day</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $s['id'] ?>">
                            <i class="bi bi-pencil-square"></i> Set Fees
                        </button>
                    </td>
                </tr>

                <!-- Fee Modal -->
                <div class="modal fade" id="editModal<?= $s['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Fees: <?= $s['program_name'] ?> - <?= $s['name'] ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="semester_id" value="<?= $s['id'] ?>">
                                <div class="row g-2">
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Base Tuition Fee</label>
                                        <input type="number" name="base_fee" class="form-control" value="<?= $s['base_fee'] ?>" required>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Lab Charges</label>
                                        <input type="number" name="lab_charges" class="form-control" value="<?= $s['lab_charges'] ?>">
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Library Fee</label>
                                        <input type="number" name="library_fee" class="form-control" value="<?= $s['library_fee'] ?>">
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Hostel Fee</label>
                                        <input type="number" name="hostel_fee" class="form-control" value="<?= $s['hostel_fee'] ?>">
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Late Fine (Daily)</label>
                                        <input type="number" name="late_fine_per_day" class="form-control" value="<?= $s['late_fine_per_day'] ?>">
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label">Credit Hour Rate</label>
                                        <input type="number" name="credit_hour_rate" class="form-control" value="0">
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="save_fee" class="btn btn-primary">Save Fee Structure</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>