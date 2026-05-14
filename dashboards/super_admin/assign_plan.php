<?php 
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/db.php';

$uid = $_SESSION['user_id'];
$urole = $_SESSION['role'];

// --- LOGIC HANDLING ---
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_now'])) {
    $mid = $_POST['client_id'];
    $tpl_id = $_POST['tpl_id'];
    $s_date = $_POST['start_date'];
    $e_date = $_POST['end_date'];
    $trainer_id = $_POST['trainer_id'] ?: $uid;
    $p_status = $_POST['payment_status'];
    
    // Check for duplicate/active plan
    $chk = $pdo->prepare("SELECT id FROM complaints WHERE user_id = ? AND subject = 'WORKOUT' AND status = 'Active'");
    $chk->execute([$mid]);
    if ($chk->fetch()) {
        $msg = "err|Member already has an active workout plan!";
    } else {
        $tpl = $pdo->prepare("SELECT * FROM workout_templates WHERE id = ?");
        $tpl->execute([$tpl_id]);
        $t = $tpl->fetch();
        
        $desc = "PLAN: {$t['name']} | LEVEL: {$t['level']} | DUR: {$t['duration']} \n\nEXERCISES:\n{$t['exercises']}";
        
        $stmt = $pdo->prepare("INSERT INTO complaints (user_id, subject, description, assigned_to, status, payment_status, created_at, end_date) VALUES (?, 'WORKOUT', ?, ?, 'Active', ?, ?, ?)");
        $stmt->execute([$mid, $desc, $trainer_id, $p_status, $s_date, $e_date]);
        $msg = "suc|Plan successfully assigned to the client!";
    }
}

// Handle Remove
if (isset($_GET['remove'])) {
    $pdo->prepare("DELETE FROM complaints WHERE id = ?")->execute([$_GET['remove']]);
    header("Location: assign_plan.php?msg=suc|Assignment removed");
    exit();
}

// --- DATA FETCHING ---
$templates = $pdo->query("SELECT id, name, level FROM workout_templates ORDER BY name ASC")->fetchAll();
$clients = $pdo->query("SELECT id, name, registration_no FROM users WHERE role = 'student' ORDER BY name ASC")->fetchAll();
$trainers = $pdo->query("SELECT id, name FROM users WHERE role IN ('trainer', 'hod', 'admin', 'super_admin') ORDER BY name ASC")->fetchAll();

$filter = $_GET['f'] ?? 'All';
$sql = "SELECT c.*, u.name as client_name, t.name as trainer_name 
        FROM complaints c 
        JOIN users u ON c.user_id = u.id 
        LEFT JOIN users t ON c.assigned_to = t.id 
        WHERE c.subject = 'WORKOUT'";

if ($filter == 'Active') $sql .= " AND c.status = 'Active'";
if ($filter == 'Unpaid') $sql .= " AND c.payment_status = 'Unpaid'";
if ($filter == 'Completed') $sql .= " AND c.status = 'Completed'";

$sql .= " ORDER BY c.id DESC";
$assignments = $pdo->query($sql)->fetchAll();

require_once __DIR__ . '/../../includes/header.php'; 
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="text-danger fw-bold">V3 - LATEST VERSION UPDATED</h1>
            <h2 class="fw-bold text-primary">Assign Workout Plan 📋</h2>
            <p class="text-muted">Professional 5-Step Member Assignment Portal</p>
        </div>
    </div>

    <?php 
    if ($msg || isset($_GET['msg'])) {
        $m = explode('|', $msg ?: $_GET['msg']);
        $type = $m[0] == 'suc' ? 'success' : 'danger';
        echo "<div class='alert alert-$type rounded-4 border-0 shadow-sm mb-4'><i class='bi bi-check-circle-fill me-2'></i>{$m[1]}</div>";
    }
    ?>

    <div class="row g-4">
        <!-- LEFT SIDE: Assign Form -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-pencil-square me-2"></i> Assign Plan to Member</h5>
                
                <form method="POST" id="assignForm">
                    <!-- Step 1: Member -->
                    <div class="mb-4">
                        <label class="small fw-bold text-dark mb-2">Step 1: Select Member</label>
                        <select name="client_id" class="form-select rounded-3 shadow-sm" required>
                            <option value="">-- Choose Member --</option>
                            <?php foreach($clients as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= $c['registration_no'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Step 2: Membership Plan -->
                    <div class="mb-4">
                        <label class="small fw-bold text-dark mb-2">Step 2: Select Membership Plan</label>
                        <select name="tpl_id" class="form-select rounded-3 shadow-sm" required>
                            <option value="">-- Choose Membership --</option>
                            <?php 
                            $plans_raw = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'gym_membership_plans'")->fetch();
                            $all_m_plans = $plans_raw ? json_decode($plans_raw['setting_value'], true) : [];
                            foreach($all_m_plans as $pl): 
                            ?>
                                <option value="<?= htmlspecialchars($pl['name']) ?>"><?= htmlspecialchars($pl['name']) ?> (Rs. <?= $pl['price'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Step 3: Dates -->
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="small fw-bold text-dark mb-2">Start Date</label>
                            <input type="date" name="start_date" class="form-control rounded-3 shadow-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-dark mb-2">End Date</label>
                            <input type="date" name="end_date" class="form-control rounded-3 shadow-sm" required>
                        </div>
                    </div>

                    <!-- Step 4 & 5: Payment & Trainer -->
                    <div class="row g-3 mb-5">
                        <div class="col-6">
                            <label class="small fw-bold text-dark mb-2">Payment Status</label>
                            <select name="payment_status" class="form-select rounded-3 shadow-sm">
                                <option value="Paid">Paid</option>
                                <option value="Unpaid">Unpaid</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-dark mb-2">Assign Trainer</label>
                            <select name="trainer_id" class="form-select rounded-3 shadow-sm">
                                <option value="">None</option>
                                <?php foreach($trainers as $tr): ?>
                                    <option value="<?= $tr['id'] ?>" <?= $tr['id'] == $uid ? 'selected' : '' ?>><?= htmlspecialchars($tr['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" name="assign_now" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow-lg">
                        Assign & Update <i class="bi bi-arrow-right-circle ms-2"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- RIGHT SIDE: Overview -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0 text-dark">Current Assignments Overview</h5>
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm rounded-pill px-3 dropdown-toggle" data-bs-toggle="dropdown">Filter: <?= $filter ?></button>
                        <ul class="dropdown-menu border-0 shadow rounded-3">
                            <li><a class="dropdown-item small" href="?f=All">All Plans</a></li>
                            <li><a class="dropdown-item small" href="?f=Active">Active</a></li>
                            <li><a class="dropdown-item small" href="?f=Unpaid">Unpaid</a></li>
                        </ul>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 500px;">
                    <table class="table table-hover align-middle border-0">
                        <thead class="bg-light sticky-top small fw-bold text-uppercase">
                            <tr>
                                <th class="border-0 ps-3">Member Name</th>
                                <th class="border-0">Current Plan</th>
                                <th class="border-0">Timeline</th>
                                <th class="border-0">Payment</th>
                                <th class="border-0 text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($assignments as $a): 
                                preg_match('/PLAN: (.*?) \|/', $a['description'], $pm);
                                $pName = $pm[1] ?? 'No Plan';
                            ?>
                            <tr class="border-bottom border-light">
                                <td class="ps-3 py-4">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($a['client_name']) ?></div>
                                    <small class="text-muted x-small"><?= htmlspecialchars($a['registration_no'] ?? 'ID: '.$a['user_id']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-dark rounded-pill px-3 mb-1"><?= htmlspecialchars($pName) ?></span><br>
                                    <small class="text-muted x-small"><i class="bi bi-person me-1"></i> <?= htmlspecialchars($a['trainer_name'] ?: 'None') ?></small>
                                </td>
                                <td>
                                    <div class="x-small text-success fw-bold">Start: <?= date('d/m/Y', strtotime($a['created_at'])) ?></div>
                                    <div class="x-small text-danger fw-bold">End: <?= date('d/m/Y', strtotime($a['end_date'] ?: '+30 days')) ?></div>
                                </td>
                                <td>
                                    <span class="badge rounded-pill x-small px-3 <?= $a['payment_status']=='Paid'?'bg-success':'bg-warning text-dark' ?>">
                                        <?= $a['payment_status'] ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="?remove=<?= $a['id'] ?>" class="btn btn-light btn-sm rounded-circle text-danger" onclick="return confirm('Remove assignment?')"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($assignments)): ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">No assignments found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .x-small { font-size: 0.7rem; }
    .transition-all { transition: all 0.3s ease; }
    .form-select, .form-control { border: 1px solid #dee2e6; padding: 0.75rem 1rem; }
    .form-select:focus, .form-control:focus { border-color: #0d6efd; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1); }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
