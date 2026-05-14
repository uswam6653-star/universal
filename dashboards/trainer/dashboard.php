<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../includes/header.php'; 

$uid = $_SESSION['user_id'];
$uname = $_SESSION['name'];

try {
    // 1. Total Members Assigned
    $totalAssigned = $pdo->prepare("SELECT count(*) FROM users WHERE identity_no LIKE ?");
    $totalAssigned->execute(["%|trainer:$uid%"]);
    $assignedCount = $totalAssigned->fetchColumn() ?: 0;

    // 2. Today's Sessions (Count of progress logs today)
    $todaySessions = $pdo->prepare("SELECT count(*) FROM sys_activity_logs WHERE details LIKE ? AND DATE(created_at) = CURDATE()");
    $todaySessions->execute(["%Logged by Coach: $uname%"]);
    $sessionCount = $todaySessions->fetchColumn() ?: 0;

    // 3. Recent Progress Logs
    $recentProgress = $pdo->prepare("
        SELECT u.name, s.details, s.created_at 
        FROM sys_activity_logs s
        JOIN users u ON s.user_id = u.id
        WHERE s.action = 'PROGRESS' AND s.details LIKE ?
        ORDER BY s.created_at DESC LIMIT 5
    ");
    $recentProgress->execute(["%Logged by Coach: $uname%"]);
    $logs = $recentProgress->fetchAll();

} catch (Exception $e) {
    die("<div class='alert alert-danger m-5'>Database Error: " . $e->getMessage() . "</div>");
}
?>

<div class="row g-4 mt-2">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="d-flex align-items-center">
                <div class="avatar-lg bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-4" style="width: 70px; height: 70px;">
                    <i class="bi bi-person-workspace h2 mb-0"></i>
                </div>
                <div>
                    <h2 class="fw-bold mb-0">Welcome back, <?= htmlspecialchars($uname) ?>!</h2>
                    <p class="text-muted mb-0">Here's a summary of your gym training activities today.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
            <h6 class="text-muted text-uppercase small fw-bold mb-3">Total Members</h6>
            <h1 class="display-4 fw-bold text-primary mb-0"><?= $assignedCount ?></h1>
            <p class="text-muted small mb-0 mt-2">Active in your squad</p>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
            <h6 class="text-muted text-uppercase small fw-bold mb-3">Today's Sessions</h6>
            <h1 class="display-4 fw-bold text-success mb-0"><?= $sessionCount ?></h1>
            <p class="text-muted small mb-0 mt-2">Members trained today</p>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
            <h6 class="text-muted text-uppercase small fw-bold mb-3">Avg. Progress</h6>
            <h1 class="display-4 fw-bold text-warning mb-0">92%</h1>
            <p class="text-muted small mb-0 mt-2">Squad performance level</p>
        </div>
    </div>

    <!-- Recent Activity Timeline -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
            <div class="card-header bg-white border-0 p-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Recent Activity Logs</h5>
                <a href="manage_members.php" class="btn btn-sm btn-light rounded-pill px-3">View Squad</a>
            </div>
            <div class="card-body p-4">
                <div class="timeline-simple">
                    <?php foreach($logs as $log): ?>
                    <div class="mb-4 border-start border-2 border-primary-subtle ps-3 position-relative">
                        <div class="position-absolute translate-middle-x" style="left: -1px; top: 0;"><i class="bi bi-dot h1 text-primary m-0"></i></div>
                        <div class="small fw-bold mb-1 text-dark"><?= htmlspecialchars($log['name']) ?></div>
                        <div class="small text-muted mb-2"><?= htmlspecialchars($log['details']) ?></div>
                        <div class="small text-muted opacity-50" style="font-size: 10px;"><i class="bi bi-clock me-1"></i><?= date('d M, h:i A', strtotime($log['created_at'])) ?></div>
                    </div>
                    <?php endforeach; ?>
                    <?php if(empty($logs)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-journal-text h1 opacity-25 d-block"></i>
                            No activities logged yet for today.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-primary text-white">
            <h5 class="fw-bold mb-3">Quick Actions</h5>
            <div class="d-grid gap-2">
                <a href="manage_members.php" class="btn btn-white text-primary rounded-3 text-start p-3 border-0 shadow-sm">
                    <i class="bi bi-people-fill me-2"></i> Manage My Members
                </a>
                <a href="../hod/workout_plans.php" class="btn btn-white text-primary rounded-3 text-start p-3 border-0 shadow-sm">
                    <i class="bi bi-clipboard-pulse me-2"></i> Update Workout Plans
                </a>
                <a href="../hod/member_progress.php" class="btn btn-white text-primary rounded-3 text-start p-3 border-0 shadow-sm">
                    <i class="bi bi-activity me-2"></i> Log New Progress
                </a>
            </div>
            <div class="mt-auto pt-4 border-top border-white border-opacity-25 mt-4">
                <small class="opacity-75">Gym ERP v3.1 | Secure Portal</small>
            </div>
        </div>
    </div>
</div>

<style>
    .btn-white { background: #fff; }
    .btn-white:hover { background: #f8f9fa; }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
