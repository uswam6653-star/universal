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
    header("Location: assign_workout.php?msg=suc|Assignment removed");
    exit();
}

// --- DATA FETCHING ---
$templates = $pdo->query("SELECT id, name, level FROM workout_templates ORDER BY name ASC")->fetchAll();
$clients = $pdo->query("SELECT id, name, registration_no FROM users WHERE role = 'student' ORDER BY name ASC")->fetchAll();
$trainers = $pdo->query("SELECT id, name FROM users WHERE role IN ('trainer', 'hod') ORDER BY name ASC")->fetchAll();

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
            <h2 class="fw-bold text-dark">Assign Workout Plan 📋</h2>
            <p class="text-muted">Manage your clients' fitness journey step-by-step.</p>
        </div>
    </div>

    <?php 
    if ($msg || isset($_GET['msg'])) {
        $m = explode('|', $msg ?: $_GET['msg']);
        $type = $m[0] == 'suc' ? 'success' : 'danger';
        echo "<div class='alert alert-$type rounded-4 border-0 shadow-sm mb-4'><i class='bi bi-info-circle-fill me-2'></i>{$m[1]}</div>";
    }
    ?>

    <div class="row g-4">
        <!-- LEFT SIDE: Assign Form -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h5 class="fw-bold mb-4 text-primary"><i class="bi bi-plus-circle me-2"></i>New Assignment</h5>
                
                <form method="POST" id="assignForm">
                    <!-- Step 1: Member -->
                    <div class="mb-4">
                        <label class="small fw-bold text-uppercase text-muted mb-2"><i class="bi bi-person me-1"></i> Step 1: Select Member</label>
                        <select name="client_id" class="form-select rounded-3 select2-basic" required>
                            <option value="">Search Member...</option>
                            <?php foreach($clients as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= $c['registration_no'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Step 2: Workout Plan -->
                    <div class="mb-4">
                        <label class="small fw-bold text-uppercase text-muted mb-2"><i class="bi bi-clipboard2-pulse me-1"></i> Step 2: Workout Plan</label>
                        <select name="tpl_id" class="form-select rounded-3" required>
                            <option value="">Choose Template...</option>
                            <?php foreach($templates as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?> (<?= $t['level'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Step 3: Dates -->
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="small fw-bold text-uppercase text-muted mb-2"><i class="bi bi-calendar-event me-1"></i> Start Date</label>
                            <input type="date" name="start_date" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-uppercase text-muted mb-2"><i class="bi bi-calendar-check me-1"></i> End Date</label>
                            <input type="date" name="end_date" class="form-control rounded-3" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                        </div>
                    </div>

                    <!-- Step 4 & 5: Trainer & Payment -->
                    <div class="row g-3 mb-5">
                        <div class="col-6">
                            <label class="small fw-bold text-uppercase text-muted mb-2"><i class="bi bi-person-badge me-1"></i> Step 4: Trainer</label>
                            <select name="trainer_id" class="form-select rounded-3">
                                <option value="">Select Trainer...</option>
                                <?php foreach($trainers as $tr): ?>
                                    <option value="<?= $tr['id'] ?>" <?= $tr['id'] == $uid ? 'selected' : '' ?>><?= htmlspecialchars($tr['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-uppercase text-muted mb-2"><i class="bi bi-cash-coin me-1"></i> Step 5: Payment</label>
                            <select name="payment_status" class="form-select rounded-3">
                                <option value="Unpaid">Unpaid</option>
                                <option value="Paid">Paid</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" name="assign_now" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow">
                        Assign Plan <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- RIGHT SIDE: Overview -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0 text-dark">Assignments Overview</h5>
                    <div class="btn-group btn-group-sm">
                        <a href="?f=All" class="btn btn-<?= $filter=='All'?'dark':'light' ?> rounded-pill-start px-3">All</a>
                        <a href="?f=Active" class="btn btn-<?= $filter=='Active'?'dark':'light' ?> px-3">Active</a>
                        <a href="?f=Unpaid" class="btn btn-<?= $filter=='Unpaid'?'dark':'light' ?> rounded-pill-end px-3">Unpaid</a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle border-0">
                        <thead class="bg-light small fw-bold text-uppercase">
                            <tr>
                                <th class="border-0 ps-3">Member</th>
                                <th class="border-0">Plan & Trainer</th>
                                <th class="border-0">Dates</th>
                                <th class="border-0">Payment</th>
                                <th class="border-0 text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($assignments as $a): 
                                preg_match('/PLAN: (.*?) \|/', $a['description'], $pm);
                                $pName = $pm[1] ?? 'Custom';
                            ?>
                            <tr class="border-bottom border-light">
                                <td class="ps-3 py-3">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($a['client_name']) ?></div>
                                    <small class="text-muted">ID: <?= $a['user_id'] ?></small>
                                </td>
                                <td>
                                    <div class="small fw-bold"><?= htmlspecialchars($pName) ?></div>
                                    <small class="text-muted">Coach: <?= htmlspecialchars($a['trainer_name']) ?></small>
                                </td>
                                <td>
                                    <div class="x-small fw-bold text-primary"><?= date('d M', strtotime($a['created_at'])) ?> - <?= date('d M', strtotime($a['end_date'])) ?></div>
                                    <small class="text-muted x-small"><?= date('Y', strtotime($a['created_at'])) ?></small>
                                </td>
                                <td>
                                    <span class="badge rounded-pill x-small px-3 <?= $a['payment_status']=='Paid'?'bg-success':'bg-danger' ?>">
                                        <?= $a['payment_status'] ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm rounded-circle" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                                        <ul class="dropdown-menu border-0 shadow-sm rounded-3">
                                            <li><a class="dropdown-item small" href="#"><i class="bi bi-pencil me-2"></i> Edit Plan</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item small text-danger" href="?remove=<?= $a['id'] ?>" onclick="return confirm('Remove assignment?')"><i class="bi bi-trash me-2"></i> Remove</a></li>
                                        </ul>
                                    </div>
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
    .rounded-pill-start { border-top-left-radius: 50rem !important; border-bottom-left-radius: 50rem !important; }
    .rounded-pill-end { border-top-right-radius: 50rem !important; border-bottom-right-radius: 50rem !important; }
    .transition-all { transition: all 0.3s ease; }
    .form-select, .form-control { border-color: #f0f0f0; }
    .form-select:focus, .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.1); }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
