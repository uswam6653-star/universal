<?php 
require_once '../../includes/header.php'; 

$uid = $_SESSION['user_id'];

// Fetch latest notifications from activity logs or general announcements
$stmt = $pdo->prepare("SELECT action, details, created_at FROM sys_activity_logs WHERE user_id = ? ORDER BY id DESC LIMIT 50");
$stmt->execute([$uid]);
$logs = $stmt->fetchAll();
?>

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
            <div class="card-header bg-white border-0 p-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="bi bi-bell-fill text-primary me-2"></i>My Notifications & Activity</h5>
            </div>
            <div class="card-body p-4">
                <div class="timeline-simple">
                    <?php foreach($logs as $log): ?>
                    <div class="mb-4 border-start border-2 border-primary-subtle ps-3 position-relative">
                        <div class="position-absolute translate-middle-x bg-white" style="left: -1px; top: 0;"><i class="bi bi-dot h3 text-primary m-0"></i></div>
                        <div class="small fw-bold mb-1 text-dark"><?= htmlspecialchars($log['action']) ?></div>
                        <div class="small text-muted mb-2"><?= htmlspecialchars($log['details']) ?></div>
                        <div class="small text-muted opacity-50" style="font-size: 11px;"><i class="bi bi-clock me-1"></i><?= date('d M Y, h:i A', strtotime($log['created_at'])) ?></div>
                    </div>
                    <?php endforeach; ?>
                    <?php if(empty($logs)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-bell-slash h1 opacity-25 d-block mb-2"></i>
                            No notifications found.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
