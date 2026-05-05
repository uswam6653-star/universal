<?php 
require_once __DIR__ . '/../../includes/header.php'; 

$uid = $_SESSION['user_id'];

// Fetch All Members Assigned to this Trainer
$stmt = $pdo->prepare("
    SELECT u.*, c.created_at as assigned_date
    FROM complaints c
    JOIN users u ON c.user_id = u.id
    WHERE c.assigned_to = ? AND c.subject = 'WORKOUT'
    ORDER BY u.name ASC
");
$stmt->execute([$uid]);
$members = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">My Members</h3>
            <p class="text-muted mb-0">List of all gym members currently assigned to you for training.</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill">
            <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light small fw-bold">
                        <tr>
                            <th class="ps-4">Member Name</th>
                            <th>Contact Info</th>
                            <th>Registration No</th>
                            <th>Assigned Since</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($members as $m): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold me-3" style="width:40px; height:40px;">
                                        <?= substr($m['name'], 0, 1) ?>
                                    </div>
                                    <span class="fw-bold"><?= $m['name'] ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="small">
                                    <div><i class="bi bi-envelope me-1"></i> <?= $m['email'] ?></div>
                                    <div class="text-muted"><i class="bi bi-telephone me-1"></i> <?= $m['registration_no'] ?></div>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= $m['registration_no'] ?></span></td>
                            <td><small class="text-muted"><?= date('d M Y', strtotime($m['assigned_date'])) ?></small></td>
                            <td>
                                <span class="badge rounded-pill px-3 <?= $m['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $m['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    <a href="../hod/member_progress.php?user_id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary" title="Progress Log">
                                        <i class="bi bi-graph-up"></i>
                                    </a>
                                    <a href="../hod/workout_plans.php?user_id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-success" title="Workout Plan">
                                        <i class="bi bi-clipboard2-check"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($members)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-people h1 d-block opacity-25"></i>
                                    No members have been assigned to you yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
