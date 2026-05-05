<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../includes/header.php'; 

$uid = $_SESSION['user_id'];
$uname = $_SESSION['name'];

try {
    // 1. Fetch Stats for Assigned Members
    $totalAssigned = $pdo->prepare("SELECT count(*) FROM users WHERE identity_no LIKE ?");
    $totalAssigned->execute(["%|trainer:$uid%"]);
    $assignedCount = $totalAssigned->fetchColumn() ?: 0;

    // 2. Fetch Recent Progress Logs
    $recentProgress = $pdo->prepare("
        SELECT u.name, s.details, s.created_at 
        FROM sys_activity_logs s
        JOIN users u ON s.user_id = u.id
        WHERE s.action = 'PROGRESS' AND s.details LIKE ?
        ORDER BY s.created_at DESC LIMIT 5
    ");
    $recentProgress->execute(["%Logged by Trainer:$uid%"]);
    $logs = $recentProgress->fetchAll();

    // 3. Fetch My Squad (Assigned Members)
    $squad = $pdo->prepare("SELECT id, name, registration_no, is_active FROM users WHERE identity_no LIKE ? LIMIT 5");
    $squad->execute(["%|trainer:$uid%"]);
    $members = $squad->fetchAll();
} catch (Exception $e) {
    die("<div class='alert alert-danger m-5'>Database Error: " . $e->getMessage() . "</div>");
}
?>

<div class="row g-4 w-100 m-0">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-5 bg-primary bg-gradient text-white">
            <h1 class="fw-bold mb-2">Trainer Portal: <?= htmlspecialchars($uname) ?></h1>
            <p class="lead opacity-75">Empowering your members to reach their peak fitness goals.</p>
        </div>
    </div>

    <!-- Trainer Stats -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100">
            <div class="rounded-circle bg-primary bg-opacity-10 p-3 mx-auto mb-3" style="width: 80px; height: 80px;">
                <i class="bi bi-people-fill display-5 text-primary"></i>
            </div>
            <h3 class="fw-bold mb-1"><?= $assignedCount ?></h3>
            <p class="text-muted small mb-0">Assigned Members</p>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100">
            <div class="rounded-circle bg-success bg-opacity-10 p-3 mx-auto mb-3" style="width: 80px; height: 80px;">
                <i class="bi bi-calendar-check display-5 text-success"></i>
            </div>
            <h3 class="fw-bold mb-1">Today</h3>
            <p class="text-muted small mb-0">Manage Schedules</p>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100">
            <div class="rounded-circle bg-info bg-opacity-10 p-3 mx-auto mb-3" style="width: 80px; height: 80px;">
                <i class="bi bi-graph-up-arrow display-5 text-info"></i>
            </div>
            <h3 class="fw-bold mb-1">94%</h3>
            <p class="text-muted small mb-0">Overall Attendance</p>
        </div>
    </div>

    <!-- My Squad Table -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
            <div class="card-header bg-transparent border-0 p-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">My Squad (Recent Members)</h5>
                <a href="../super_admin/manage_users.php" class="btn btn-sm btn-light rounded-pill px-3 shadow-sm border">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small fw-bold">
                            <tr>
                                <th class="ps-4">Member Name</th>
                                <th>Reg No</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Manage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($members as $m): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-primary"><?= htmlspecialchars($m['name']) ?></td>
                                <td><code><?= $m['registration_no'] ?></code></td>
                                <td><span class="badge <?= $m['is_active'] ? 'bg-success' : 'bg-danger' ?> rounded-pill">Active</span></td>
                                <td class="text-end pe-4">
                                    <div class="btn-group shadow-sm rounded-pill overflow-hidden border">
                                        <a href="../hod/workout_plans.php?uid=<?= $m['id'] ?>" class="btn btn-sm btn-white text-primary" title="Update Workout"><i class="bi bi-clipboard-pulse"></i></a>
                                        <a href="../hod/member_progress.php?uid=<?= $m['id'] ?>" class="btn btn-sm btn-white text-success" title="Log Progress"><i class="bi bi-activity"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($members)): ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted">No members assigned yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Logs -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
            <h5 class="fw-bold mb-4">Latest Logs</h5>
            <div class="timeline-simple">
                <?php foreach($logs as $log): ?>
                <div class="mb-4 border-start border-2 border-primary-subtle ps-3 position-relative">
                    <div class="position-absolute translate-middle-x" style="left: -1px; top: 0;"><i class="bi bi-dot h1 text-primary m-0"></i></div>
                    <div class="small fw-bold mb-1"><?= htmlspecialchars($log['name']) ?></div>
                    <div class="small text-muted text-truncate mb-2"><?= htmlspecialchars($log['details']) ?></div>
                    <div class="small text-muted opacity-50" style="font-size: 10px;"><?= date('d M, h:i A', strtotime($log['created_at'])) ?></div>
                </div>
                <?php endforeach; ?>
                <?php if(empty($logs)): ?>
                    <div class="text-center py-4 text-muted">No recent progress logs.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
