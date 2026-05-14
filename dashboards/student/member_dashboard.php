<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../includes/header.php'; 

$uid = $_SESSION['user_id'];

// Fetch Member Data
$member = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$member->execute([$uid]);
$userData = $member->fetch();

// Parse Metadata (Index 0: Plan, Index 8: Expiry, Index 10: Trainer ID)
$meta = explode('|', $userData['identity_no'] ?? '');
$planName = $meta[0] ?? 'Basic Membership';
$expiryDate = $meta[8] ?? 'N/A';
$trainerMeta = $meta[10] ?? '';
$trainerId = 0;
if (strpos($trainerMeta, 'trainer:') !== false) {
    $trainerId = (int)str_replace('trainer:', '', $trainerMeta);
} else {
    $trainerId = (int)$trainerMeta;
}

// Fetch Trainer Name
$trainerName = 'Not Assigned';
if ($trainerId) {
    $tStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $tStmt->execute([$trainerId]);
    $trainerName = $tStmt->fetchColumn() ?: 'Not Assigned';
}

// Fetch Latest Workout
$workout = $pdo->prepare("SELECT description FROM complaints WHERE user_id = ? AND subject = 'WORKOUT' ORDER BY id DESC LIMIT 1");
$workout->execute([$uid]);
$workoutPlan = $workout->fetchColumn() ?: "Consult your trainer for a workout plan.";

// Fetch Recent Payments
$payments = $pdo->prepare("SELECT details, created_at FROM sys_activity_logs WHERE user_id = ? AND action = 'PAYMENT' ORDER BY created_at DESC LIMIT 3");
$payments->execute([$uid]);
$paymentLogs = $payments->fetchAll();
?>

<div class="row g-4">
    <!-- Profile & Plan Header -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-5 bg-dark text-white position-relative overflow-hidden">
            <div class="position-absolute top-0 end-0 p-5 opacity-10">
                <i class="bi bi-fire display-1"></i>
            </div>
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="fw-bold mb-1">Hello, <?= htmlspecialchars($_SESSION['name']) ?>! 👋</h1>
                    <p class="lead opacity-75 mb-4">You're on the <strong><?= htmlspecialchars($planName) ?></strong> plan.</p>
                    <div class="d-flex gap-3 align-items-center">
                        <span class="badge bg-primary px-3 rounded-pill py-2">Member ID: <?= $userData['registration_no'] ?></span>
                        <span class="badge bg-warning text-dark px-3 rounded-pill py-2">Expires: <?= $expiryDate ?></span>
                        <?php 
                        $lastPay = $paymentLogs[0]['details'] ?? '';
                        $isPaid = (strpos($lastPay, 'Status: Paid') !== false);
                        ?>
                        <span class="badge <?= $isPaid ? 'bg-success' : 'bg-danger' ?> px-3 rounded-pill py-2">
                            Payment: <?= $isPaid ? 'Paid ✅' : 'Pending ⏳' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Workout & Diet Plans -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent border-0 p-4 pb-0 d-flex justify-content-between">
                <h5 class="fw-bold mb-0"><i class="bi bi-lightning-fill text-warning me-2"></i>My Workout & Diet Plan</h5>
                <span class="small text-muted">Assigned by <?= $trainerName ?></span>
            </div>
            <div class="card-body p-4">
                <div class="p-4 rounded-4 bg-light border-start border-4 border-warning">
                    <pre class="mb-0 text-wrap fs-6 fw-normal" style="font-family: inherit; line-height: 1.6;"><?= htmlspecialchars($workoutPlan) ?></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Stats -->
    <div class="col-lg-4">
        <div class="row g-4">
            <!-- Trainer Info -->
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h6 class="text-muted small fw-bold text-uppercase mb-3">Your Coach</h6>
                    <div class="d-flex align-items-center">
                        <div class="avatar-md bg-info bg-opacity-10 rounded-circle p-3 me-3">
                            <i class="bi bi-person-badge display-6 text-info"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0"><?= $trainerName ?></h5>
                            <small class="text-muted">Personal Trainer</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Preview -->
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h6 class="text-muted small fw-bold text-uppercase mb-3">Quick Stats</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Attendance this month:</span>
                        <span class="fw-bold text-success">18 Days</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success" style="width: 75%"></div>
                    </div>
                </div>
            </div>

            <!-- Payment History -->
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <h6 class="text-muted small fw-bold text-uppercase mb-3">Recent Payments</h6>
                    <?php foreach($paymentLogs as $p): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2 small">
                            <span class="text-truncate" style="max-width: 150px;"><?= $p['details'] ?></span>
                            <span class="text-muted"><?= date('d M', strtotime($p['created_at'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if(empty($paymentLogs)): ?>
                        <p class="text-muted small mb-0">No recent payment activity.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
