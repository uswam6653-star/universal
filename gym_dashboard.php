<?php
require_once 'includes/header.php';

// ---- Fetch Integrated Data ----
function getJsonData($pdo, $key) {
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $res = $stmt->fetch();
    return $res ? json_decode($res['setting_value'], true) : [];
}

// 1. Members and Staff Counts
$totalMembers = $pdo->query("SELECT count(*) FROM users WHERE role='student'")->fetchColumn();
$totalStaff = $pdo->query("SELECT count(*) FROM users WHERE role NOT IN ('student', 'suspended')")->fetchColumn();

// 2. Active Memberships (Expiry Check)
$all_members = $pdo->query("SELECT id, name, registration_no, identity_no, is_active FROM users WHERE role = 'student'")->fetchAll();
$activeCount = 0;
$today = date('Y-m-d');
foreach ($all_members as $m) {
    $parts = explode('|', $m['identity_no']);
    $end = $parts[8] ?? '';
    if (!empty($end) && $end >= $today) $activeCount++;
}

// 3. Monthly Revenue (Paid Status from Metadata + Optional Payments JSON)
$monthlyRev = 0;
$thisMonth = date('Y-m');
foreach ($all_members as $m) {
    $parts = explode('|', $m['identity_no']);
    $payStatus = $parts[7] ?? '';
    $joinDate = $parts[3] ?? '';
    if (strcasecmp($payStatus, 'Paid') == 0 && substr($joinDate, 0, 7) === $thisMonth) {
        $monthlyRev += 5000; // Simulated average amount since we use metadata
    }
}

// 4. Attendance Today (Simulated or from JSON)
$attendance = getJsonData($pdo, 'gym_attendance');
$todayAtt = 0;
foreach ($attendance as $a) {
    if (isset($a['date']) && $a['date'] === $today && ($a['status'] ?? '') === 'Present') $todayAtt++;
}

// Recent Members (Limit 5)
$recentMembers = array_slice($all_members, 0, 5);
?>

<style>
    :root {
        --gym-primary: #1e1b4b;
        --gym-accent: #6366f1;
        --gym-gold: #fbbf24;
    }

    .dashboard-hero {
        background: linear-gradient(135deg, var(--gym-primary) 0%, #312e81 100%);
        border-radius: 30px;
        color: white;
        padding: 50px;
        margin-bottom: 40px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }

    .hero-pattern {
        position: absolute;
        top: 0; right: 0;
        width: 300px; height: 300px;
        background: radial-gradient(circle, rgba(99, 102, 241, 0.2) 0%, transparent 70%);
        border-radius: 50%;
        transform: translate(30%, -30%);
    }

    .stat-card-premium {
        background: white;
        border: none;
        border-radius: 20px;
        padding: 25px;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        display: flex;
        align-items: center;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .stat-card-premium:hover {
        transform: translateY(-12px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    .stat-icon-wrap {
        width: 65px;
        height: 65px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        margin-right: 20px;
    }

    .icon-members { background: #eef2ff; color: #4338ca; }
    .icon-staff { background: #fff7ed; color: #c2410c; }
    .icon-active { background: #f0fdf4; color: #15803d; }
    .icon-attendance { background: #fee2e2; color: #991b1b; }
    .icon-revenue { background: #fffbeb; color: #b45309; }

    .pulse-dot {
        width: 10px; height: 10px;
        background: #ef4444;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
        animation: pulse-animation 2s infinite;
    }

    @keyframes pulse-animation {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }

    .action-btn {
        border-radius: 15px;
        padding: 15px 25px;
        font-weight: 700;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .action-btn-primary {
        background: var(--gym-accent);
        color: white;
    }

    .action-btn-primary:hover {
        background: transparent;
        border-color: var(--gym-accent);
        color: var(--gym-accent);
    }
</style>

<div class="container-fluid pb-5">
    <!-- Hero Section -->
    <div class="dashboard-hero">
        <div class="hero-pattern"></div>
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1 class="display-4 fw-bold mb-3">Gym Management Pro ⚔️</h1>
                <p class="fs-5 opacity-75 mb-4">Your Gym Management System is live! Effortlessly manage members, staff, and track revenue all in one professional dashboard.</p>
                <div class="d-flex gap-3">
                    <a href="dashboards/super_admin/manage_members.php" class="btn action-btn action-btn-primary shadow">
                        <i class="bi bi-person-plus-fill me-2"></i> Add New Member
                    </a>
                    <a href="dashboards/super_admin/manage_staff.php" class="btn action-btn btn-outline-light">
                        <i class="bi bi-briefcase me-2"></i> Manage Staff
                    </a>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block text-center text-white-50">
                <i class="bi bi-trophy display-1 pulse"></i>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="stat-card-premium">
                <div class="stat-icon-wrap icon-members shadow-sm"><i class="bi bi-people-fill"></i></div>
                <div>
                    <h2 class="fw-bold mb-0"><?= $totalMembers ?></h2>
                    <small class="text-muted fw-bold text-uppercase">Total Members</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card-premium">
                <div class="stat-icon-wrap icon-staff shadow-sm"><i class="bi bi-briefcase-fill"></i></div>
                <div>
                    <h2 class="fw-bold mb-0"><?= $totalStaff ?></h2>
                    <small class="text-muted fw-bold text-uppercase">Total Staff</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card-premium p-3">
                <div class="stat-icon-wrap icon-active shadow-sm" style="width:50px; height:50px; font-size: 20px;"><i class="bi bi-lightning-charge-fill"></i></div>
                <div>
                    <h3 class="fw-bold mb-0"><?= $activeCount ?></h3>
                    <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 10px;">Active</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card-premium p-3">
                <div class="stat-icon-wrap icon-attendance shadow-sm" style="width:50px; height:50px; font-size: 20px;"><i class="bi bi-geo-alt-fill"></i></div>
                <div>
                    <h3 class="fw-bold mb-0"><?= $todayAtt ?></h3>
                    <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 10px;">Check-ins</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card-premium">
                <div class="stat-icon-wrap icon-revenue shadow-sm"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <h2 class="fw-bold mb-0">Rs. <?= number_format($monthlyRev) ?></h2>
                    <small class="text-muted fw-bold text-uppercase"><?= date('F') ?> Revenue</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity & Tools -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white p-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Member Registrations</h5>
                    <a href="dashboards/super_admin/manage_members.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">View All Members</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light small fw-bold">
                                <tr>
                                    <th class="ps-4">Member</th>
                                    <th>Plan</th>
                                    <th>Join Date</th>
                                    <th class="text-end pe-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recentMembers as $rm): 
                                    $meta = explode('|', $rm['identity_no']);
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm rounded-circle bg-primary-subtle text-primary me-3 d-flex align-items-center justify-content-center fw-bold" style="width:35px; height:35px;">
                                                <?= substr($rm['name'], 0, 1) ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold"><?= $rm['name'] ?></h6>
                                                <small class="text-muted"><?= $rm['registration_no'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-dark-subtle text-dark"><?= $meta[5] ?: 'Monthly' ?></span></td>
                                    <td><small class="text-muted"><?= $meta[3] ?: 'N/A' ?></small></td>
                                    <td class="text-end pe-4">
                                        <span class="badge <?= $rm['is_active'] ? 'bg-success' : 'bg-danger' ?> rounded-pill">
                                            <?= $rm['is_active'] ? 'Active' : 'Expired' ?>
                                        </span>
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
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-light">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Quick Links ⚡</h5>
                    <div class="d-grid gap-3">
                        <a href="dashboards/super_admin/manage_roles.php" class="btn btn-white text-start shadow-sm p-3 rounded-4 border-0">
                            <div class="d-flex justify-content-between">
                                <span><i class="bi bi-shield-lock-fill me-2 text-warning"></i> Manage Roles</span>
                                <i class="bi bi-chevron-right small text-muted"></i>
                            </div>
                        </a>
                        <a href="dashboards/super_admin/manage_pages.php" class="btn btn-white text-start shadow-sm p-3 rounded-4 border-0">
                            <div class="d-flex justify-content-between">
                                <span><i class="bi bi-list-stars me-2 text-info"></i> Sidebar Settings</span>
                                <i class="bi bi-chevron-right small text-muted"></i>
                            </div>
                        </a>
                        <a href="dashboards/super_admin/assign_plan.php" class="btn btn-white text-start shadow-sm p-3 rounded-4 border-0">
                            <div class="d-flex justify-content-between">
                                <span><i class="bi bi-card-checklist me-2 text-success"></i> Assign Plans</span>
                                <i class="bi bi-chevron-right small text-muted"></i>
                            </div>
                        </a>
                        <div class="bg-primary-subtle p-3 rounded-4 mt-2">
                             <p class="small text-primary fw-bold mb-0"><i class="bi bi-lightning-fill me-1"></i> Performance Tip:</p>
                             <p class="small text-muted mb-0">Track member progress daily to ensure maximum retention and motivation levels.</p>
                        </div>
                        <div class="mt-4 p-3 rounded-4 border border-warning-subtle bg-warning-subtle bg-opacity-10">
                            <h6 class="fw-bold text-warning-emphasis mb-1"><i class="bi bi-quote me-1"></i> Success Quote</h6>
                            <p class="small text-muted mb-0 italic">"The only place where success comes before work is in the dictionary."</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
