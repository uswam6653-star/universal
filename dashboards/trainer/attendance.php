<?php 
require_once __DIR__ . '/../../includes/header.php'; 

$uid = $_SESSION['user_id'];

// Handle Attendance Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    $mid = $_POST['member_id'];
    $status = $_POST['status'];
    
    // Check if already marked today
    $check = $pdo->prepare("SELECT id FROM sys_activity_logs WHERE user_id = ? AND action = 'ATTENDANCE' AND DATE(created_at) = CURDATE()");
    $check->execute([$mid]);
    
    if (!$check->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO sys_activity_logs (user_id, action, details) VALUES (?, 'ATTENDANCE', ?)");
        $stmt->execute([$mid, "Status: $status (Marked by Trainer ID: $uid)"]);
        $success = "Attendance marked successfully!";
    } else {
        $error = "Attendance already recorded for today.";
    }
}

// Fetch Assigned Members
$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.registration_no, 
           (SELECT details FROM sys_activity_logs WHERE user_id = u.id AND action = 'ATTENDANCE' AND DATE(created_at) = CURDATE() LIMIT 1) as today_status
    FROM users u
    JOIN complaints c ON u.id = c.user_id AND c.subject = 'WORKOUT'
    WHERE c.assigned_to = ?
");
$stmt->execute([$uid]);
$members = $stmt->fetchAll();
?>

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white d-flex flex-row justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold mb-0 text-dark">Attendance Tracking</h3>
                <p class="text-muted mb-0">Mark daily attendance for your squad members.</p>
            </div>
            <div class="text-end">
                <h5 class="fw-bold mb-0"><?= date('d M, Y') ?></h5>
                <span class="badge bg-light text-dark border rounded-pill px-3 mt-1">Live Session</span>
            </div>
        </div>
    </div>

    <div class="col-12">
        <?php if(isset($success)) echo "<div class='alert alert-success rounded-4 shadow-sm border-0 mb-4'>$success</div>"; ?>
        <?php if(isset($error)) echo "<div class='alert alert-warning rounded-4 shadow-sm border-0 mb-4'>$error</div>"; ?>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light small fw-bold text-uppercase">
                        <tr>
                            <th class="ps-4">Member</th>
                            <th>Registration</th>
                            <th>Status Today</th>
                            <th class="text-end pe-4">Mark Attendance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($members as $m): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?= htmlspecialchars($m['name']) ?></div>
                            </td>
                            <td><code class="small"><?= $m['registration_no'] ?></code></td>
                            <td>
                                <?php if($m['today_status']): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">
                                        <i class="bi bi-check-circle-fill me-1"></i> Recorded
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <form method="POST" class="d-flex justify-content-end gap-2">
                                    <input type="hidden" name="member_id" value="<?= $m['id'] ?>">
                                    <input type="hidden" name="status" value="Present">
                                    <?php if(!$m['today_status']): ?>
                                        <button type="submit" name="mark_attendance" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">Present</button>
                                        <button type="submit" name="mark_attendance" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="this.form.status.value='Absent'">Absent</button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-light rounded-pill px-3 disabled" disabled><i class="bi bi-lock-fill"></i> Locked</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($members)): ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">No members assigned to your squad.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
