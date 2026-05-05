<?php 
require_once __DIR__ . '/../../includes/header.php'; 

$setting_key = 'gym_trainer_assignments';

// Fetch Members 
$members = $pdo->query("SELECT id, name, registration_no FROM users WHERE role = 'student' ORDER BY name ASC")->fetchAll();

// Fetch Trainers
$trainers = $pdo->query("SELECT id, name, email FROM users WHERE role = 'trainer' ORDER BY name ASC")->fetchAll();

// Fetch Assignment Data
$stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
$stmt->execute([$setting_key]);
$res = $stmt->fetch();

if (!$res) {
    $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, '[]')")->execute([$setting_key]);
    $assignments = [];
} else {
    $assignments = json_decode($res['setting_value'], true) ?: [];
}

// Handle CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_assignment'])) {
        $id = !empty($_POST['edit_id']) ? $_POST['edit_id'] : uniqid('TR_ASS_');
        
        $mem_name = '';
        foreach($members as $m) {
            if ($m['id'] == $_POST['member_id']) {
                $mem_name = $m['name'];
                break;
            }
        }
        
        $train_name = '';
        foreach($trainers as $t) {
            if ($t['id'] == $_POST['trainer_id']) {
                $train_name = $t['name'];
                break;
            }
        }

        $new_record = [
            'id' => $id,
            'member_id' => $_POST['member_id'],
            'member_name' => $mem_name,
            'trainer_id' => $_POST['trainer_id'],
            'trainer_name' => $train_name,
            'type' => $_POST['type'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'status' => $_POST['status']
        ];

        if (!empty($_POST['edit_id'])) {
            foreach ($assignments as $k => $v) {
                if ($v['id'] === $id) {
                    $assignments[$k] = $new_record;
                    break;
                }
            }
            $success = "Assignment updated!";
        } else {
            array_unshift($assignments, $new_record);
            $success = "Trainer assigned successfully!";
        }

        $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?")->execute([json_encode($assignments), $setting_key]);

        // --- NEW: Sync with User Metadata for Display Logic ---
        $stmt = $pdo->prepare("SELECT identity_no FROM users WHERE id = ?");
        $stmt->execute([$_POST['member_id']]);
        $meta = $stmt->fetchColumn() ?: '';
        $parts = array_pad(explode('|', $meta), 10, '');
        
        $parts[6] = $train_name; // Trainer Name
        $parts[8] = $_POST['end_date']; // End Date
        
        $new_meta = implode('|', $parts);
        $pdo->prepare("UPDATE users SET identity_no = ? WHERE id = ?")->execute([$new_meta, $_POST['member_id']]);
        // --- End Sync ---
    }
}

// Delete
if (isset($_GET['delete'])) {
    $del = $_GET['delete'];
    $assignments = array_filter($assignments, function($a) use ($del) { return $a['id'] !== $del; });
    $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?")->execute([json_encode(array_values($assignments)), $setting_key]);
    header("Location: assign_trainers.php?msg=Assignment deleted");
    exit;
}

// Filters
$f_member = $_GET['f_member'] ?? '';
$f_trainer = $_GET['f_trainer'] ?? '';
$f_date = $_GET['f_date'] ?? '';

$filtered = $assignments;

if ($f_member !== '') {
    $filtered = array_filter($filtered, function($a) use ($f_member) {
        return stripos($a['member_name'], $f_member) !== false;
    });
}
if ($f_trainer !== '') {
    $filtered = array_filter($filtered, function($a) use ($f_trainer) {
        return stripos($a['trainer_name'], $f_trainer) !== false;
    });
}
if ($f_date !== '') {
    $filtered = array_filter($filtered, function($a) use ($f_date) {
        return $a['start_date'] <= $f_date && $a['end_date'] >= $f_date;
    });
}

// Trainer Schedule Data
$schedule = [];
foreach ($trainers as $t) {
    $schedule[$t['name']] = [];
}
foreach ($assignments as $a) {
    if ($a['status'] === 'Active' && isset($schedule[$a['trainer_name']])) {
        $schedule[$a['trainer_name']][] = [
            'member' => $a['member_name'],
            'type' => $a['type']
        ];
    }
}

?>

<div class="row g-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-transparent border-0 p-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Trainer Assignments</h5>
                <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#assignModal" onclick="resetForm()">
                    <i class="bi bi-person-badge me-1"></i> Assign Trainer
                </button>
            </div>
            
            <div class="card-body px-4 pt-0">
                <?php if(isset($success)) echo "<div class='alert alert-success small py-2'>$success</div>"; ?>
                <?php if(isset($_GET['msg'])) echo "<div class='alert alert-info small py-2'>".htmlspecialchars($_GET['msg'])."</div>"; ?>

                <!-- Filters -->
                <form method="GET" class="row g-2 mb-4 bg-light p-3 rounded-4">
                    <div class="col-md-4">
                        <label class="small text-muted mb-1">Search Member</label>
                        <input type="text" name="f_member" class="form-control form-control-sm" placeholder="Member Name" value="<?= htmlspecialchars($f_member) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted mb-1">Search Trainer</label>
                        <input type="text" name="f_trainer" class="form-control form-control-sm" placeholder="Trainer Name" value="<?= htmlspecialchars($f_trainer) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted mb-1">Active On Date</label>
                        <input type="date" name="f_date" class="form-control form-control-sm" value="<?= htmlspecialchars($f_date) ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-sm btn-dark w-100"><i class="bi bi-search me-1"></i> Filter</button>
                    </div>
                    <div class="col-12 mt-2">
                        <a href="assign_trainers.php" class="small text-danger text-decoration-none">Clear Filters</a>
                    </div>
                </form>

                <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold" data-bs-toggle="tab" data-bs-target="#list">Assignment List</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#schedule">Trainer Schedule</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- List Tab -->
                    <div class="tab-pane fade show active" id="list">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light small fw-bold text-uppercase">
                                    <tr>
                                        <th class="ps-4">Member Name</th>
                                        <th>Assigned Trainer</th>
                                        <th>Training Type</th>
                                        <th>Timeline</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($filtered as $a): ?>
                                    <tr>
                                        <td class="ps-4"><strong><?= htmlspecialchars($a['member_name']) ?></strong></td>
                                        <td><i class="bi bi-person-video me-1 text-primary"></i> <?= htmlspecialchars($a['trainer_name']) ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($a['type']) ?></span></td>
                                        <td>
                                            <small class="text-success d-block">Start: <?= date('d M Y', strtotime($a['start_date'])) ?></small>
                                            <small class="text-danger d-block">End: <?= date('d M Y', strtotime($a['end_date'])) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge <?= $a['status'] == 'Active' ? 'bg-success' : 'bg-dark' ?>"><?= $a['status'] ?></span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-info rounded-pill-start" data-bs-toggle="modal" data-bs-target="#viewModal<?= $a['id'] ?>"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignModal" onclick='editAsgn(<?= json_encode($a) ?>)'><i class="bi bi-pencil"></i></button>
                                                <a href="?delete=<?= $a['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill-end" onclick="return confirm('Delete this assignment?')"><i class="bi bi-trash"></i></a>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- View Modal -->
                                    <div class="modal fade" id="viewModal<?= $a['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content border-0 rounded-4">
                                                <div class="modal-header border-0 p-4 pb-0">
                                                    <h6 class="fw-bold mb-0">Assignment Details</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="row g-3">
                                                        <div class="col-6"><label class="text-muted small">Member</label><p class="fw-bold mb-0"><?= htmlspecialchars($a['member_name']) ?></p></div>
                                                        <div class="col-6"><label class="text-muted small">Trainer</label><p class="fw-bold mb-0 text-primary"><?= htmlspecialchars($a['trainer_name']) ?></p></div>
                                                        <div class="col-12"><label class="text-muted small">Training Type</label><p class="mb-0"><span class="badge bg-dark"><?= htmlspecialchars($a['type']) ?></span></p></div>
                                                        <div class="col-6"><label class="text-muted small">Start Date</label><p class="mb-0 text-success"><?= date('d M Y', strtotime($a['start_date'])) ?></p></div>
                                                        <div class="col-6"><label class="text-muted small">End Date</label><p class="mb-0 text-danger"><?= date('d M Y', strtotime($a['end_date'])) ?></p></div>
                                                        <div class="col-12"><label class="text-muted small">Status</label><p class="mb-0"><span class="badge <?= $a['status'] == 'Active' ? 'bg-success' : 'bg-secondary' ?>"><?= $a['status'] ?></span></p></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                    <?php if(empty($filtered)): ?>
                                        <tr><td colspan="6" class="text-center text-muted py-4">No trainers assigned based on current filters.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Schedule Tab -->
                    <div class="tab-pane fade" id="schedule">
                        <div class="row g-4 mt-1">
                            <?php foreach($schedule as $t_name => $t_members): ?>
                            <div class="col-md-4">
                                <div class="card h-100 border shadow-sm">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0 fw-bold"><i class="bi bi-person-badge me-2"></i><?= htmlspecialchars($t_name) ?></h6>
                                    </div>
                                    <ul class="list-group list-group-flush small">
                                        <?php if(empty($t_members)): ?>
                                            <li class="list-group-item text-muted text-center py-4">No active assignments</li>
                                        <?php else: ?>
                                            <?php foreach($t_members as $tm): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <?= htmlspecialchars($tm['member']) ?>
                                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($tm['type']) ?></span>
                                            </li>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="asgnForm" class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-bold mb-0" id="mTitle">Assign Trainer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="edit_id" id="edit_id">
                
                <div class="mb-3">
                    <label class="small fw-bold mb-1">Select Member</label>
                    <select name="member_id" id="f_member" class="form-select rounded-3" required>
                        <option value="">-- Select Member --</option>
                        <?php foreach($members as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?> (<?= htmlspecialchars($m['registration_no']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="small fw-bold mb-1">Select Trainer</label>
                    <select name="trainer_id" id="f_trainer" class="form-select rounded-3" required>
                        <option value="">-- Select Trainer --</option>
                        <?php foreach($trainers as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="small fw-bold mb-1">Training Type</label>
                    <select name="type" id="f_type" class="form-select rounded-3">
                        <option>Weight Training</option>
                        <option>Cardio</option>
                        <option>Personal Training</option>
                        <option>CrossFit</option>
                        <option>Yoga</option>
                    </select>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="small fw-bold mb-1">Start Date</label>
                        <input type="date" name="start_date" id="f_start" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold mb-1">End Date</label>
                        <input type="date" name="end_date" id="f_end" class="form-control rounded-3" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="small fw-bold mb-1">Status</label>
                    <select name="status" id="f_status" class="form-select rounded-3">
                        <option value="Active">Active</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="submit" name="save_assignment" class="btn btn-primary w-100 rounded-pill fw-bold">Save Assignment</button>
            </div>
        </form>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('asgnForm').reset();
    document.getElementById('edit_id').value = '';
    document.getElementById('mTitle').innerText = 'Assign Trainer';
    document.getElementById('f_start').value = '<?= date('Y-m-d') ?>';
}

function editAsgn(data) {
    document.getElementById('mTitle').innerText = 'Edit Assignment';
    document.getElementById('edit_id').value = data.id;
    document.getElementById('f_member').value = data.member_id;
    document.getElementById('f_trainer').value = data.trainer_id;
    document.getElementById('f_type').value = data.type;
    document.getElementById('f_start').value = data.start_date;
    document.getElementById('f_end').value = data.end_date;
    document.getElementById('f_status').value = data.status;
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
