<?php 
require_once '../../core/db.php';
require_once '../../core/session.php';

// ---- Load all JSON data ----
function getSettingJson($pdo, $key) {
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $res = $stmt->fetch();
    return $res ? (json_decode($res['setting_value'], true) ?: []) : [];
}

// ---- Filters ----
$f_month = $_GET['f_month'] ?? date('m');
$f_year  = $_GET['f_year']  ?? date('Y');
$f_ym    = $f_year . '-' . str_pad($f_month, 2, '0', STR_PAD_LEFT);

$attendance   = getSettingJson($pdo, 'gym_attendance');
$payments     = getSettingJson($pdo, 'gym_payments');
$assignments  = getSettingJson($pdo, 'gym_trainer_assignments');
$plans        = getSettingJson($pdo, 'gym_membership_plans');

// ---- Members Data ----
$all_members = $pdo->query("SELECT id, name, registration_no, identity_no FROM users WHERE role = 'student' ORDER BY name ASC")->fetchAll();
$all_trainers = $pdo->query("SELECT id, name FROM users WHERE role = 'trainer' ORDER BY name ASC")->fetchAll();

$today = date('Y-m-d');
$total_members = count($all_members);
$active_members = 0;
$expired_members = 0;
$new_members_month = 0;

foreach ($all_members as $m) {
    $parts = explode('|', $m['identity_no'] ?? '');
    while (count($parts) < 9) $parts[] = '';
    $end_date = $parts[8];
    if (!empty($end_date) && $end_date !== 'N/A') {
        if ($end_date >= $today) $active_members++;
        else $expired_members++;
    }
    // New members this month (by join date in parts[3])
    if (!empty($parts[3]) && substr($parts[3], 0, 7) === $f_ym) $new_members_month++;
}

// ---- Attendance Report (filtered by month/year) ----
$att_filtered = array_filter($attendance, fn($a) => substr($a['date'], 0, 7) === $f_ym);

$att_by_member = [];
foreach ($att_filtered as $a) {
    $key = $a['member_name'];
    if (!isset($att_by_member[$key])) $att_by_member[$key] = ['present' => 0, 'absent' => 0];
    if ($a['status'] === 'Present') $att_by_member[$key]['present']++;
    else $att_by_member[$key]['absent']++;
}
arsort($att_by_member);

// ---- Payments Report (filtered by month/year) ----
$pay_filtered = array_filter($payments, fn($p) => substr($p['payment_date'], 0, 7) === $f_ym);
$total_revenue  = array_sum(array_column(array_filter($pay_filtered, fn($p) => $p['status'] === 'Paid'), 'amount'));
$paid_count     = count(array_filter($pay_filtered, fn($p) => $p['status'] === 'Paid'));
$pending_amount = array_sum(array_column(array_filter($pay_filtered, fn($p) => $p['status'] === 'Pending'), 'amount'));
$pending_count  = count(array_filter($pay_filtered, fn($p) => $p['status'] === 'Pending'));

// ---- Trainer Report ----
$trainer_report = [];
foreach ($all_trainers as $t) $trainer_report[$t['name']] = 0;
foreach ($assignments as $a) {
    if ($a['status'] === 'Active' && isset($trainer_report[$a['trainer_name']])) {
        $trainer_report[$a['trainer_name']]++;
    }
}

// ---- Monthly Summary (last 6 months) ----
$monthly_summary = [];
for ($i = 5; $i >= 0; $i--) {
    $dt = new DateTime("first day of -$i month");
    $ym = $dt->format('Y-m');
    $label = $dt->format('M Y');

    $new_m = 0;
    foreach ($all_members as $m) {
        $parts = explode('|', $m['identity_no'] ?? '');
        if (!empty($parts[3]) && substr($parts[3], 0, 7) === $ym) $new_m++;
    }

    $att_m = count(array_filter($attendance, fn($a) => substr($a['date'], 0, 7) === $ym && $a['status'] === 'Present'));
    $rev_m = array_sum(array_column(array_filter($payments, fn($p) => substr($p['payment_date'], 0, 7) === $ym && $p['status'] === 'Paid'), 'amount'));

    $monthly_summary[] = ['label' => $label, 'new_members' => $new_m, 'attendance' => $att_m, 'revenue' => $rev_m];
}

// ---- Export CSV ----
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="gym_report_' . $f_ym . '.csv"');
    $fp = fopen('php://output', 'wb');

    fputcsv($fp, ['=== GYM MANAGEMENT ERP REPORT ===']);
    fputcsv($fp, ['Month', date('F Y', strtotime($f_ym . '-01'))]);
    fputcsv($fp, []);

    fputcsv($fp, ['--- MEMBERS ---']);
    fputcsv($fp, ['Total Members', 'Active', 'Expired', 'New This Month']);
    fputcsv($fp, [$total_members, $active_members, $expired_members, $new_members_month]);
    fputcsv($fp, []);

    fputcsv($fp, ['--- PAYMENTS ---']);
    fputcsv($fp, ['Total Revenue (Rs.)', 'Paid Count', 'Pending Amount (Rs.)', 'Pending Count']);
    fputcsv($fp, [$total_revenue, $paid_count, $pending_amount, $pending_count]);
    fputcsv($fp, []);

    fputcsv($fp, ['--- ATTENDANCE ---']);
    fputcsv($fp, ['Member Name', 'Total Present', 'Total Absent']);
    foreach ($att_by_member as $name => $data) {
        fputcsv($fp, [$name, $data['present'], $data['absent']]);
    }
    fputcsv($fp, []);

    fputcsv($fp, ['--- TRAINER REPORT ---']);
    fputcsv($fp, ['Trainer Name', 'Active Members Assigned']);
    foreach ($trainer_report as $name => $count) {
        fputcsv($fp, [$name, $count]);
    }

    fclose($fp);
    exit;
}

require_once __DIR__ . '/../../includes/header.php'; 
?>

<div class="row g-4">
    <div class="col-12">
        <!-- Header + Filter -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="fw-bold mb-0">Gym Management Reports</h4>
                        <p class="text-muted small mb-0">Showing data for <strong><?= date('F Y', strtotime($f_ym.'-01')) ?></strong></p>
                    </div>
                    <form method="GET" class="d-flex gap-2 align-items-end flex-wrap">
                        <div>
                            <label class="small text-muted d-block mb-1">Month</label>
                            <select name="f_month" class="form-select form-select-sm">
                                <?php for($i=1;$i<=12;$i++): ?>
                                    <option value="<?= $i ?>" <?= $f_month==$i?'selected':'' ?>><?= date('F', mktime(0,0,0,$i,1)) ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label class="small text-muted d-block mb-1">Year</label>
                            <select name="f_year" class="form-select form-select-sm">
                                <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
                                    <option value="<?= $y ?>" <?= $f_year==$y?'selected':'' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i> Apply</button>
                        </div>
                        <div>
                            <a href="?f_month=<?= $f_month ?>&f_year=<?= $f_year ?>&export=1" class="btn btn-success btn-sm"><i class="bi bi-download me-1"></i> Export CSV</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 1. Members Report -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-transparent border-0 p-4 pb-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-people me-2 text-primary"></i>Members Report</h6>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="bg-primary text-white rounded-4 p-3 text-center">
                            <div class="fs-1 fw-bold"><?= $total_members ?></div>
                            <small>Total Members</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="bg-success text-white rounded-4 p-3 text-center">
                            <div class="fs-1 fw-bold"><?= $active_members ?></div>
                            <small>Active Members</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="bg-danger text-white rounded-4 p-3 text-center">
                            <div class="fs-1 fw-bold"><?= $expired_members ?></div>
                            <small>Expired Members</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="bg-info text-white rounded-4 p-3 text-center">
                            <div class="fs-1 fw-bold"><?= $new_members_month ?></div>
                            <small>New This Month</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Payments Report -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-transparent border-0 p-4 pb-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-cash-stack me-2 text-success"></i>Payments Report — <?= date('F Y', strtotime($f_ym.'-01')) ?></h6>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="border rounded-4 p-3 text-center">
                            <p class="text-muted small mb-1">Total Revenue</p>
                            <h3 class="fw-bold text-success mb-0">Rs. <?= number_format($total_revenue) ?></h3>
                            <small class="text-muted"><?= $paid_count ?> paid transactions</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-4 p-3 text-center">
                            <p class="text-muted small mb-1">Paid Payments</p>
                            <h3 class="fw-bold text-primary mb-0"><?= $paid_count ?></h3>
                            <small class="text-muted">transactions completed</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded-4 p-3 text-center">
                            <p class="text-muted small mb-1">Pending Amount</p>
                            <h3 class="fw-bold text-warning mb-0">Rs. <?= number_format($pending_amount) ?></h3>
                            <small class="text-muted"><?= $pending_count ?> pending</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- 3. Attendance Report -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-transparent border-0 p-4 pb-2">
                        <h6 class="fw-bold mb-0"><i class="bi bi-calendar2-check me-2 text-warning"></i>Attendance Report — <?= date('F Y', strtotime($f_ym.'-01')) ?></h6>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <?php if(empty($att_by_member)): ?>
                            <p class="text-muted text-center py-4">No attendance records for this month.</p>
                        <?php else: ?>
                        <table class="table table-sm table-hover mb-0">
                            <thead class="bg-light small fw-bold"><tr><th>Member</th><th class="text-center">Present</th><th class="text-center">Absent</th></tr></thead>
                            <tbody>
                                <?php foreach($att_by_member as $name => $data): ?>
                                <tr>
                                    <td><?= htmlspecialchars($name) ?></td>
                                    <td class="text-center"><span class="badge bg-success"><?= $data['present'] ?></span></td>
                                    <td class="text-center"><span class="badge bg-danger"><?= $data['absent'] ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 4. Trainer Report -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-transparent border-0 p-4 pb-2">
                        <h6 class="fw-bold mb-0"><i class="bi bi-person-badge me-2 text-danger"></i>Trainer Report (Active Assignments)</h6>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <?php if(empty($trainer_report)): ?>
                            <p class="text-muted text-center py-4">No trainers found.</p>
                        <?php else: ?>
                        <table class="table table-sm table-hover mb-0">
                            <thead class="bg-light small fw-bold"><tr><th>Trainer Name</th><th class="text-center">Members Assigned</th></tr></thead>
                            <tbody>
                                <?php foreach($trainer_report as $name => $count): ?>
                                <tr>
                                    <td><i class="bi bi-person-video me-1 text-primary"></i><?= htmlspecialchars($name) ?></td>
                                    <td class="text-center">
                                        <span class="badge <?= $count > 0 ? 'bg-primary' : 'bg-secondary' ?>"><?= $count ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Monthly Summary Report -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 p-4 pb-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-bar-chart me-2 text-info"></i>Monthly Summary (Last 6 Months)</h6>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="bg-dark text-white text-center small fw-bold">
                            <tr>
                                <th>Month</th>
                                <th>New Members</th>
                                <th>Total Attendance</th>
                                <th>Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            <?php foreach($monthly_summary as $row): ?>
                            <tr <?= $row['label'] === date('M Y') ? 'class="table-warning fw-bold"' : '' ?>>
                                <td><?= $row['label'] ?></td>
                                <td><span class="badge bg-info"><?= $row['new_members'] ?></span></td>
                                <td><span class="badge bg-success"><?= $row['attendance'] ?></span></td>
                                <td class="text-success fw-bold">Rs. <?= number_format($row['revenue']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
