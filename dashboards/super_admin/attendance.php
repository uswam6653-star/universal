<?php 
require_once __DIR__ . '/../../includes/header.php'; 

// Role and User Context
$role = $_SESSION['role'];
$uid = $_SESSION['user_id'];

// Handling Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_attendance'])) {
        $mid = $_POST['member_id'];
        $status = $_POST['status']; // 'Present' or 'Absent'
        
        // Check if already marked today
        $check = $pdo->prepare("SELECT id FROM sys_activity_logs WHERE user_id = ? AND action = 'ATTENDANCE' AND DATE(created_at) = CURRENT_DATE");
        $check->execute([$mid]);
        
        if ($check->fetch()) {
            $pdo->prepare("UPDATE sys_activity_logs SET details = ? WHERE user_id = ? AND action = 'ATTENDANCE' AND DATE(created_at) = CURRENT_DATE")->execute([$status, $mid]);
            $success = "Attendance updated for the member.";
        } else {
            $pdo->prepare("INSERT INTO sys_activity_logs (user_id, action, details) VALUES (?, 'ATTENDANCE', ?)")->execute([$mid, $status]);
            $success = "Attendance marked successfully!";
        }
    }
}

// Filtering Inputs
$f_date = $_GET['f_date'] ?? date('Y-m-d');
$f_trainer = $_GET['f_trainer'] ?? '';
$f_search = $_GET['f_search'] ?? '';

// Build Query based on constraints
$query = "
    SELECT u.id, u.name, u.roll_no, a.details as status, a.created_at as check_in, t.name as trainer_name
    FROM users u
    LEFT JOIN sys_activity_logs a ON u.id = a.user_id AND a.action = 'ATTENDANCE' AND DATE(a.created_at) = :f_date
    LEFT JOIN complaints c ON u.id = c.user_id AND c.subject = 'WORKOUT'
    LEFT JOIN users t ON c.assigned_to = t.id
    WHERE u.role = 'student'
";

$params = [':f_date' => $f_date];

if (!empty($f_trainer)) {
    $query .= " AND c.assigned_to = :f_trainer";
    $params[':f_trainer'] = $f_trainer;
}

if (!empty($f_search)) {
    $query .= " AND (u.name LIKE :f_search OR u.roll_no LIKE :f_search)";
    $params[':f_search'] = "%$f_search%";
}

// Special check for Trainer role: only show their assigned members
if ($role == 'hod') {
    $query .= " AND c.assigned_to = :my_uid";
    $params[':my_uid'] = $uid;
}

$query .= " GROUP BY u.id ORDER BY u.name ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$attendanceList = $stmt->fetchAll();

// Statistics Calculation
$totalM = count($attendanceList);
$presentM = 0; $absentM = 0;
foreach($attendanceList as $al) {
    if($al['status'] == 'Present') $presentM++;
    elseif($al['status'] == 'Absent') $absentM++;
}
$perc = ($totalM > 0) ? round(($presentM / $totalM) * 100) : 0;

$trainers = $pdo->query("SELECT id, name FROM users WHERE role = 'hod'")->fetchAll();
?>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-dark text-white text-center">
            <h1 class="fw-bold mb-0"><?= $totalM ?></h1>
            <small class="text-uppercase fw-bold opacity-50">Total Members</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-primary text-white text-center">
            <h1 class="fw-bold mb-0 text-white"><?= $presentM ?></h1>
            <small class="text-uppercase fw-bold opacity-50">Present Today</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-danger text-white text-center">
            <h1 class="fw-bold mb-0"><?= $absentM ?></h1>
            <small class="text-uppercase fw-bold opacity-50">Absent</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-success text-white text-center">
            <h1 class="fw-bold mb-0"><?= $perc ?>%</h1>
            <small class="text-uppercase fw-bold opacity-50">Attendance Ratio</small>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-transparent border-0 p-4 pb-0">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">Attendance Controls & Filters</h5>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-sm btn-outline-dark rounded-pill px-3"><i class="bi bi-printer me-1"></i>Print PDF</button>
            </div>
        </div>
    </div>
    <div class="card-body p-4">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="small fw-bold">Select Date</label>
                <input type="date" name="f_date" class="form-control rounded-3" value="<?= $f_date ?>">
            </div>
            <?php if($role != 'hod'): ?>
            <div class="col-md-3">
                <label class="small fw-bold">By Trainer</label>
                <select name="f_trainer" class="form-select rounded-3">
                    <option value="">All Trainers</option>
                    <?php foreach($trainers as $tr): ?>
                        <option value="<?= $tr['id'] ?>" <?= ($f_trainer == $tr['id']) ? 'selected' : '' ?>><?= $tr['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-4">
                <label class="small fw-bold">Search Member</label>
                <input type="text" name="f_search" class="form-control rounded-3" placeholder="Name or ID..." value="<?= $f_search ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100 rounded-3">Apply Filters</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-uppercase small fw-bold">
                    <tr>
                        <th class="ps-4">Member Info</th>
                        <th>Assigned Trainer</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Quick Mark</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($attendanceList as $a): ?>
                    <tr>
                        <td class="ps-4">
                            <strong><?= htmlspecialchars($a['name']) ?></strong><br>
                            <small class="text-muted">ID: <?= $a['roll_no'] ?></small>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= $a['trainer_name'] ?: 'No Trainer' ?></span></td>
                        <td><?= $a['check_in'] ? date('h:i A', strtotime($a['check_in'])) : '--:--' ?></td>
                        <td>
                            <?php if($a['status'] == 'Present'): ?>
                                <span class="badge bg-success rounded-pill px-3">PRESENT</span>
                            <?php elseif($a['status'] == 'Absent'): ?>
                                <span class="badge bg-danger rounded-pill px-3">ABSENT</span>
                            <?php else: ?>
                                <span class="badge bg-secondary opacity-50 rounded-pill px-3">NOT MARKED</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="member_id" value="<?= $a['id'] ?>">
                                <div class="btn-group btn-group-sm">
                                    <button type="submit" name="mark_attendance" value="Present" class="btn btn-outline-success border-2 rounded-start-pill px-3" name="status" title="Mark Present">P</button>
                                    <button type="submit" name="mark_attendance" value="Absent" class="btn btn-outline-danger border-2 rounded-middle px-3" name="status" title="Mark Absent">A</button>
                                    <a href="?view_history=<?= $a['id'] ?>" class="btn btn-outline-dark border-2 rounded-end-pill px-3" title="View History"><i class="bi bi-clock-history"></i></a>
                                </div>
                                <input type="hidden" name="status" value=""> 
                            </form>
                            <script>
                                // Fixing button value submission
                                document.querySelectorAll('button[name="mark_attendance"]').forEach(btn => {
                                    btn.addEventListener('click', function() {
                                        this.form.querySelector('input[name="status"]').value = this.value;
                                    });
                                });
                            </script>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
// Member Specific History Logic
if(isset($_GET['view_history'])): 
    $view_id = $_GET['view_history'];
    $history = $pdo->prepare("SELECT * FROM sys_activity_logs WHERE user_id = ? AND action = 'ATTENDANCE' ORDER BY created_at DESC");
    $history->execute([$view_id]);
    $h_data = $history->fetchAll();
?>
<div class="modal fade show d-block" style="background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0 p-4"><h5 class="fw-bold mb-0">Attendance History</h5><a href="attendance.php" class="btn-close"></a></div>
            <div class="modal-body p-4 pt-0">
                <table class="table table-sm">
                    <thead><tr><th>Date</th><th>Time</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach($h_data as $hd): ?>
                        <tr><td><?= date('d M Y', strtotime($hd['created_at'])) ?></td><td><?= date('h:i A', strtotime($hd['created_at'])) ?></td><td><?= $hd['details'] ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
