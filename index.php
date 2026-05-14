<?php
require_once __DIR__ . '/core/session.php';

// Role-based Redirection
if ($_SESSION['role'] === 'student') {
    header("Location: " . BASE_URL . "dashboards/student/member_dashboard.php");
    exit;
} elseif ($_SESSION['role'] === 'hod') {
    header("Location: " . BASE_URL . "dashboards/hod/department_stats.php");
    exit;
} elseif ($_SESSION['role'] === 'trainer') {
    header("Location: " . BASE_URL . "dashboards/trainer/dashboard.php");
    exit;
}
// Admin (super_admin) stays here on index.php

require_once 'includes/header.php'; 

// ---- Fetch Integrated Data ----
$totalMembers = $pdo->query("SELECT count(*) FROM users WHERE role='student'")->fetchColumn();
$totalStaff = $pdo->query("SELECT count(*) FROM users WHERE role NOT IN ('student', 'suspended', 'super_admin')")->fetchColumn();

// 2. Active Memberships (Expiry Check from Metadata)
$all_members = $pdo->query("SELECT id, name, registration_no, identity_no, is_active FROM users WHERE role = 'student'")->fetchAll();
$activeCount = 0;
$today = date('Y-m-d');
foreach ($all_members as $m) {
    $parts = explode('|', $m['identity_no']);
    $end = $parts[8] ?? '';
    if (!empty($end) && $end >= $today) $activeCount++;
}

// 3. Monthly Revenue (Paid Status from Metadata)
$monthlyRev = 0;
$thisMonth = date('Y-m');
foreach ($all_members as $m) {
    $parts = explode('|', $m['identity_no']);
    $payStatus = $parts[7] ?? '';
    $joinDate = $parts[3] ?? '';
    if (strcasecmp($payStatus, 'Paid') == 0 && substr($joinDate, 0, 7) === $thisMonth) {
        $monthlyRev += 5000; // Simulated average amount
    }
}

// 4. Attendance Today
$stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'gym_attendance'");
$stmt->execute();
$att_data = $stmt->fetch();
$attendance = $att_data ? json_decode($att_data['setting_value'], true) : [];
$todayAtt = 0;
foreach ($attendance as $a) {
    if (isset($a['date']) && $a['date'] === $today && ($a['status'] ?? '') === 'Present') $todayAtt++;
}

// Recent Data for Table
$recentReg = array_slice($all_members, 0, 5);
?>

<div class="row g-3">
    <!-- Stat Cards -->
    <div class="col-lg-2 col-md-4 col-6">
        <div class="small-box bg-primary rounded-4 shadow-sm text-white h-100">
            <div class="inner p-3">
                <h3 class="fw-bold mb-1"><?= $totalMembers ?></h3>
                <p class="small mb-0">Total Members</p>
            </div>
            <div class="icon" style="top:10px; right:10px;"><i class="bi bi-people-fill opacity-25" style="font-size: 50px;"></i></div>
            <a href="dashboards/super_admin/manage_members.php" class="small-box-footer rounded-bottom-4" style="font-size: 0.75rem;">More info <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>
    
    <div class="col-lg-2 col-md-4 col-6">
        <div class="small-box bg-info rounded-4 shadow-sm text-white h-100">
            <div class="inner p-3">
                <h3 class="fw-bold mb-1"><?= $totalStaff ?></h3>
                <p class="small mb-0">Total Staff</p>
            </div>
            <div class="icon" style="top:10px; right:10px;"><i class="bi bi-briefcase-fill opacity-25" style="font-size: 50px;"></i></div>
            <a href="dashboards/super_admin/manage_staff.php" class="small-box-footer rounded-bottom-4" style="font-size: 0.75rem;">More info <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>

    <div class="col-lg-2 col-md-4 col-6">
        <div class="small-box bg-success rounded-4 shadow-sm text-white h-100">
            <div class="inner p-3">
                <h3 class="fw-bold mb-1"><?= $activeCount ?></h3>
                <p class="small mb-0">Active Members</p>
            </div>
            <div class="icon" style="top:10px; right:10px;"><i class="bi bi-shield-check opacity-25" style="font-size: 50px;"></i></div>
            <a href="dashboards/super_admin/manage_members.php" class="small-box-footer rounded-bottom-4" style="font-size: 0.75rem;">More info <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 col-6">
        <div class="small-box bg-danger rounded-4 shadow-sm text-white h-100">
            <div class="inner p-3">
                <h3 class="fw-bold mb-1"><?= $todayAtt ?></h3>
                <p class="small mb-0">Daily Attendance</p>
            </div>
            <div class="icon" style="top:10px; right:10px;"><i class="bi bi-geo-alt-fill opacity-25" style="font-size: 50px;"></i></div>
            <a href="dashboards/super_admin/gym_attendance.php" class="small-box-footer rounded-bottom-4" style="font-size: 0.75rem;">View Logs <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 col-12">
        <div class="small-box bg-warning rounded-4 shadow-sm text-dark h-100">
            <div class="inner p-3">
                <h3 class="fw-bold mb-1">Rs. <?= number_format($monthlyRev) ?></h3>
                <p class="small mb-0"><?= date('F') ?> Revenue</p>
            </div>
            <div class="icon" style="top:10px; right:10px;"><i class="bi bi-currency-dollar opacity-25" style="font-size: 50px;"></i></div>
            <a href="dashboards/super_admin/gym_payments.php" class="small-box-footer rounded-bottom-4 text-dark" style="font-size: 0.75rem;">Manage Billing <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-8">
        <!-- Simple Recent Registration Table -->
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent border-0 p-4 pb-0 d-flex justify-content-between">
                <h5 class="fw-bold mb-0">Recent Registrations</h5>
                <a href="dashboards/super_admin/manage_members.php" class="small text-primary text-decoration-none">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small fw-bold">
                            <tr>
                                <th class="ps-4">Member Name</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recentReg as $r): ?>
                            <tr>
                                <td class="ps-4">
                                    <h6 class="mb-0 fw-bold"><?= htmlspecialchars($r['name']) ?></h6>
                                    <small class="text-muted"><?= $r['registration_no'] ?></small>
                                </td>
                                <td><span class="badge <?= $r['is_active'] ? 'bg-success' : 'bg-danger' ?> rounded-pill">Active</span></td>
                                <td class="text-end pe-4">
                                    <a href="dashboards/super_admin/manage_members.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <!-- Motivation Box -->
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-primary bg-gradient text-white">
            <div class="card-body p-4 d-flex flex-column justify-content-center">
                <div class="mb-3"><i class="bi bi-lightning-charge-fill display-4 text-warning"></i></div>
                <h4 class="fw-bold mb-3">Gym Motivation</h4>
                <p class="fs-6 opacity-75 italic mb-0">"The only bad workout is the one that didn't happen. Success starts with self-discipline."</p>
                <hr class="opacity-25 my-4">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-white bg-opacity-25 rounded-circle me-3 d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
                        <i class="bi bi-award-fill"></i>
                    </div>
                    <div>
                        <small class="d-block opacity-75">Daily Tip</small>
                        <span class="fw-bold">Keep Pushing!</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
