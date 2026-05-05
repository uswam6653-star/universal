<?php 
require_once __DIR__ . '/../../includes/header.php'; 

$uid = $_SESSION['user_id'];

// Handle Plan Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_plan'])) {
    $mid = $_POST['member_id'];
    $plan_text = $_POST['workout_plan'];
    
    // Store plan in 'description' field of 'complaints' table where subject='WORKOUT'
    $stmt = $pdo->prepare("UPDATE complaints SET description = ?, status = 'Active' WHERE user_id = ? AND assigned_to = ? AND subject = 'WORKOUT'");
    $stmt->execute([$plan_text, $mid, $uid]);
    $success = "Workout plan updated successfully!";
}

// Fetch Assigned Members
$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.roll_no, c.description as workout_plan
    FROM users u
    JOIN complaints c ON u.id = c.user_id AND c.subject = 'WORKOUT'
    WHERE c.assigned_to = ?
");
$stmt->execute([$uid]);
$assignedMembers = $stmt->fetchAll();
?>

<div class="row g-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-transparent border-0 p-4">
                <h5 class="fw-bold mb-0">Manage Workout Plans</h5>
            </div>
            <div class="card-body p-0">
                <?php if(isset($success)) echo "<div class='alert alert-success m-4 small'>$success</div>"; ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small fw-bold text-uppercase">
                            <tr>
                                <th class="ps-4">Member</th>
                                <th>Current Plan</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($assignedMembers as $m): ?>
                            <tr>
                                <td class="ps-4">
                                    <strong><?= htmlspecialchars($m['name']) ?></strong><br>
                                    <small class="text-muted"><?= $m['roll_no'] ?></small>
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 300px;">
                                        <?= !empty($m['workout_plan']) ? htmlspecialchars($m['workout_plan']) : '<i class="text-muted">No plan set yet</i>' ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#planModal<?= $m['id'] ?>">
                                        <i class="bi bi-pencil-square me-1"></i> Edit Plan
                                    </button>
                                </td>
                            </tr>

                            <!-- Plan Modal -->
                            <div class="modal fade" id="planModal<?= $m['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <form method="POST" class="modal-content border-0 rounded-4">
                                        <div class="modal-header border-0 p-4 pb-0">
                                            <h5 class="fw-bold mb-0">Workout Plan for <?= htmlspecialchars($m['name']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <input type="hidden" name="member_id" value="<?= $m['id'] ?>">
                                            <div class="mb-3">
                                                <label class="small fw-bold mb-2">Detailed Plan (Exercises, Sets, Reps)</label>
                                                <textarea name="workout_plan" class="form-control rounded-3" rows="6" required><?= htmlspecialchars($m['workout_plan']) ?></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0 p-4 pt-0">
                                            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="update_plan" class="btn btn-primary rounded-pill px-4">Save Plan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
