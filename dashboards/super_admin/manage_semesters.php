<?php 
require_once __DIR__ . '/../../includes/header.php'; 

// Handle Add Semester
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_semester'])) {
    $program_id = $_POST['program_id'];
    $name = trim($_POST['name']);
    $number = $_POST['number'];
    
    $stmt = $pdo->prepare("INSERT INTO semesters (program_id, name, number) VALUES (?, ?, ?)");
    try {
        $stmt->execute([$program_id, $name, $number]);
        echo "<script>alert('Duration added successfully!'); window.location.href='manage_semesters.php';</script>";
    } catch(Exception $e) { 
        $error = "Error adding duration: " . $e->getMessage(); 
    }
}

// Fetch programs for dropdown
$programs = $pdo->query("SELECT * FROM programs ORDER BY name")->fetchAll();

// Fetch all semesters with program name
$semesters = $pdo->query("
    SELECT s.*, p.name as program_name 
    FROM semesters s 
    JOIN programs p ON s.program_id = p.id 
    ORDER BY p.name, s.number
")->fetchAll();
?>

<div class="card card-primary card-outline">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Manage Subscription Durations</h3>
        <button class="btn btn-primary btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-circle me-1"></i> Add New Duration
        </button>
    </div>
    <div class="card-body p-0">
        <?php if(isset($error)): ?> <div class="alert alert-danger m-3"><?= $error ?></div> <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Membership Plan</th>
                        <th>Duration Name</th>
                        <th>Months #</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($semesters as $s): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($s['program_name']) ?></strong></td>
                        <td><?= htmlspecialchars($s['name']) ?></td>
                        <td><span class="badge bg-secondary"><?= $s['number'] ?></span></td>
                        <td><span class="badge bg-info"><?= ucfirst($s['status']) ?></span></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($semesters)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No durations defined yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Subscription Duration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Target Plan</label>
                    <select name="program_id" class="form-select" required>
                        <option value="">-- Select Membership Plan --</option>
                        <?php foreach($programs as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= $p['name'] ?> (<?= $p['code'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Duration Title</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Full Year, 1 Month Trial" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Number of Months</label>
                    <input type="number" name="number" class="form-control" min="1" max="100" value="1" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_semester" class="btn btn-primary">Save Duration</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>