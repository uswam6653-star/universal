<?php 
require_once __DIR__ . '/../../includes/header.php'; 

$uid = $_SESSION['user_id'];

// Fetch Member Details
$member = $pdo->prepare("
    SELECT u.*, c.description
    FROM users u
    LEFT JOIN complaints c ON u.id = c.user_id AND c.subject = 'WORKOUT'
    WHERE u.id = ?
");
$member->execute([$uid]);
$userData = $member->fetch();

// Parse Plan Info from identity_no (Index 0: Plan Name, Index 1: Price, Index 8: Expiry)
$meta = $userData['identity_no'] ?? '';
$parts = explode('|', $meta);
$planName = $parts[0] ?? 'No Plan';
$expiryDate = $parts[8] ?? 'N/A';

// Fetch Assigned Trainer
$trainer = $pdo->prepare("
    SELECT t.name, t.email 
    FROM complaints c 
    JOIN users t ON c.assigned_to = t.id 
    WHERE c.user_id = ? AND c.subject = 'WORKOUT' 
    LIMIT 1
");
$trainer->execute([$uid]);
$trainerData = $trainer->fetch();

// Fetch Recent Attendance
$attendance = $pdo->prepare("SELECT * FROM sys_activity_logs WHERE user_id = ? AND action = 'ATTENDANCE' ORDER BY created_at DESC LIMIT 5");
$attendance->execute([$uid]);
$attendanceLogs = $attendance->fetchAll();
?>

<div class="row g-4">
    <!-- Membership Info -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-primary text-white">
            <h5 class="fw-bold mb-4">My Membership</h5>
            <div class="mb-4">
                <h1 class="fw-bold"><?= htmlspecialchars($planName) ?></h1>
                <p class="opacity-75">Expires on: <?= $expiryDate ?></p>
            </div>
            <div class="mt-auto pt-3 border-top border-white border-opacity-25">
                <div class="d-flex justify-content-between">
                    <span>Member ID:</span>
                    <span class="fw-bold"><?= $userData['registration_no'] ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Workout Plan -->
    <div class="col-md-7">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent border-0 p-4 pb-0 d-flex justify-content-between">
                <h5 class="fw-bold mb-0">My Workout Plan</h5>
                <span class="badge bg-light text-dark border"><?= htmlspecialchars($planName) ?></span>
            </div>
            <div class="card-body p-4">
                <?php if(!empty($userData['description'])): ?>
                    <div class="bg-light p-3 rounded-4 border-start border-primary border-4">
                        <pre class="mb-0 text-wrap fw-normal" style="font-family: inherit;"><?= htmlspecialchars($userData['description']) ?></pre>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-file-earmark-text h1 d-block opacity-25"></i>
                        No workout plan assigned yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Trainer Info -->
    <div class="col-md-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h5 class="fw-bold mb-4">My Trainer</h5>
            <?php if($trainerData): ?>
                <div class="d-flex align-items-center mb-4">
                    <div class="rounded-circle bg-light p-3 me-3">
                        <i class="bi bi-person-badge-fill text-primary display-6"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0"><?= $trainerData['name'] ?></h4>
                        <small class="text-muted"><?= $trainerData['email'] ?></small>
                    </div>
                </div>
                <!-- Mini Progress Preview -->
                <div class="mt-4 pt-4 border-top">
                    <h6 class="fw-bold mb-3 small text-uppercase opacity-50">Latest Progress Log</h6>
                    <?php 
                    $prog = $pdo->prepare("SELECT details, created_at FROM sys_activity_logs WHERE user_id = ? AND action = 'PROGRESS' ORDER BY created_at DESC LIMIT 1");
                    $prog->execute([$uid]);
                    $latestProg = $prog->fetch();
                    if($latestProg): ?>
                        <div class="small">
                            <div class="fw-bold"><?= date('d M Y', strtotime($latestProg['created_at'])) ?></div>
                            <div class="text-muted"><?= $latestProg['details'] ?></div>
                        </div>
                    <?php else: ?>
                        <small class="text-muted">No progress logged yet.</small>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-person-x h1 text-muted"></i>
                    <p class="text-muted">No trainer assigned yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="col-md-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-transparent border-0 p-4"><h5 class="fw-bold mb-0">Recent Attendance</h5></div>
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light small fw-bold"><tr><th class="ps-4">Date</th><th>Time</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach($attendanceLogs as $log): ?>
                        <tr>
                            <td class="ps-4"><?= date('d M Y', strtotime($log['created_at'])) ?></td>
                            <td><?= date('h:i A', strtotime($log['created_at'])) ?></td>
                            <td>
                                <span class="badge rounded-pill px-3 <?= $log['details'] == 'Present' ? 'bg-success' : 'bg-danger' ?>">
                                    <?= strtoupper($log['details']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($attendanceLogs)): ?>
                            <tr><td colspan="3" class="text-center py-4 text-muted">No attendance activity found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
