<?php 
require_once __DIR__ . '/../../includes/header.php'; 

$uid = $_SESSION['user_id'];

// Handle Progress Log
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_progress'])) {
    $mid = $_POST['member_id'];
    $weight = $_POST['weight'];
    $body_fat = $_POST['body_fat'] ?: 'N/A';
    $notes = $_POST['notes'];
    
    $details = "Weight: {$weight}kg | Fat: {$body_fat}% | Notes: {$notes}";
    
    // Store progress in 'sys_activity_logs' with action 'PROGRESS'
    $stmt = $pdo->prepare("INSERT INTO sys_activity_logs (user_id, action, details) VALUES (?, 'PROGRESS', ?)");
    $stmt->execute([$mid, $details]);
    $success = "Progress logged successfully!";
}

// Fetch Assigned Members
$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.roll_no
    FROM users u
    JOIN complaints c ON u.id = c.user_id AND c.subject = 'WORKOUT'
    WHERE c.assigned_to = ?
");
$stmt->execute([$uid]);
$assignedMembers = $stmt->fetchAll();
?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h5 class="fw-bold mb-4">Log New Progress</h5>
            <?php if(isset($success)) echo "<div class='alert alert-success small'>$success</div>"; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="small fw-bold">Select Member</label>
                    <select name="member_id" class="form-select rounded-3" required>
                        <option value="">-- Choose Member --</option>
                        <?php foreach($assignedMembers as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= $m['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="small fw-bold">Weight (kg)</label>
                        <input type="number" step="0.1" name="weight" class="form-control rounded-3" required>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold">Body Fat (%)</label>
                        <input type="number" step="0.1" name="body_fat" class="form-control rounded-3">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="small fw-bold">Trainer Notes</label>
                    <textarea name="notes" class="form-control rounded-3" rows="3" placeholder="Performance, fatigue, etc."></textarea>
                </div>
                <button type="submit" name="log_progress" class="btn btn-primary w-100 rounded-3 py-2 fw-bold">Save Metrics</button>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
            <div class="card-header bg-transparent border-0 p-4">
                <h5 class="fw-bold mb-0">Recent Progress Logs</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small fw-bold">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Member</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Fetch last 10 progress logs for this trainer's members
                            $stmt = $pdo->prepare("
                                SELECT a.created_at, u.name, a.details
                                FROM sys_activity_logs a
                                JOIN users u ON a.user_id = u.id
                                JOIN complaints c ON u.id = c.user_id AND c.subject = 'WORKOUT'
                                WHERE c.assigned_to = ? AND a.action = 'PROGRESS'
                                ORDER BY a.created_at DESC LIMIT 10
                            ");
                            $stmt->execute([$uid]);
                            $logs = $stmt->fetchAll();
                            
                            foreach($logs as $l): ?>
                            <tr>
                                <td class="ps-4 small"><?= date('d M Y', strtotime($l['created_at'])) ?></td>
                                <td><strong><?= htmlspecialchars($l['name']) ?></strong></td>
                                <td class="small opacity-75"><?= htmlspecialchars($l['details']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($logs)): ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted">No progress metrics logged yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
