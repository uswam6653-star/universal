<?php 
require_once __DIR__ . '/../../includes/header.php'; 

// Handle Add Program
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_program'])) {
    $name = trim($_POST['name']);
    $code = strtoupper(trim($_POST['code']));
    
    $stmt = $pdo->prepare("INSERT INTO programs (name, code) VALUES (?, ?)");
    try {
        $stmt->execute([$name, $code]);
        echo "<script>alert('Membership Plan added successfully!'); window.location.href='manage_programs.php';</script>";
    } catch(Exception $e) { 
        $error = "Error: Plan Code already exists or database issue."; 
    }
}

// Handle Delete Program
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM programs WHERE id = ?");
    $stmt->execute([$id]);
    echo "<script>window.location.href='manage_programs.php';</script>";
}

// Fetch all programs
$programs = $pdo->query("SELECT * FROM programs ORDER BY id DESC")->fetchAll();
?>

<div class="card card-primary card-outline">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Manage Membership Plans</h3>
        <button class="btn btn-primary btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-circle me-1"></i> Add New Plan
        </button>
    </div>
    <div class="card-body p-0">
        <?php if(isset($error)): ?> <div class="alert alert-danger m-3"><?= $error ?></div> <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>ID</th>
                        <th>Plan Name</th>
                        <th>Plan Code</th>
                        <th>Created At</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($programs as $p): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                        <td><span class="badge bg-secondary"><?= $p['code'] ?></span></td>
                        <td><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                        <td class="text-center">
                            <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure? This will delete all related semesters!')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($programs)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No membership plans defined yet.</td></tr>
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
                <h5 class="modal-title">Add New Membership Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Plan Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Monthly Pro, Annual Basic" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Plan Code (Short)</label>
                    <input type="text" name="code" class="form-control" placeholder="e.g. MPRO, ABAS" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_program" class="btn btn-primary">Save Plan</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>