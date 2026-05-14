<?php 
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/db.php';

$uid = $_SESSION['user_id'];
$urole = $_SESSION['role'];

// --- LOGIC HANDLING ---

// 1. Handle Template CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_template'])) {
        $name = $_POST['name'];
        $level = $_POST['level'];
        $duration = $_POST['duration'];
        $exercises = $_POST['exercises'];
        $tid = $_POST['template_id'] ?? null;

        if ($tid) {
            $pdo->prepare("UPDATE workout_templates SET name=?, level=?, duration=?, exercises=? WHERE id=?")
                ->execute([$name, $level, $duration, $exercises, $tid]);
        } else {
            $pdo->prepare("INSERT INTO workout_templates (name, level, duration, exercises, created_by) VALUES (?, ?, ?, ?, ?)")
                ->execute([$name, $level, $duration, $exercises, $uid]);
        }
        header("Location: workout_plans.php?msg=Template saved");
        exit();
    }

    // 2. Handle Plan Assignment
    if (isset($_POST['assign_plan'])) {
        $mid = $_POST['client_id'];
        $tpl_id = $_POST['tpl_id'];
        $start_date = $_POST['start_date'] ?: date('Y-m-d');
        $notes = $_POST['notes'];
        
        $tpl = $pdo->prepare("SELECT * FROM workout_templates WHERE id = ?");
        $tpl->execute([$tpl_id]);
        $t = $tpl->fetch();
        
        $desc = "PLAN: {$t['name']} | LEVEL: {$t['level']} | DUR: {$t['duration']} | START: $start_date \n\nEXERCISES:\n{$t['exercises']} \n\nCOACH NOTES: $notes";
        
        $chk = $pdo->prepare("SELECT id FROM complaints WHERE user_id = ? AND subject = 'WORKOUT'");
        $chk->execute([$mid]);
        if ($row = $chk->fetch()) {
            $pdo->prepare("UPDATE complaints SET description = ?, assigned_to = ?, status = 'Active', created_at = ? WHERE id = ?")
                ->execute([$desc, $uid, $start_date, $row['id']]);
        } else {
            $pdo->prepare("INSERT INTO complaints (user_id, subject, description, assigned_to, status, created_at) VALUES (?, 'WORKOUT', ?, ?, 'Active', ?)")
                ->execute([$mid, $desc, $uid, $start_date]);
        }
        header("Location: workout_plans.php?msg=Plan assigned successfully");
        exit();
    }

    // 3. Duplicate Template
    if (isset($_POST['duplicate_tpl'])) {
        $tid = $_POST['tpl_id'];
        $pdo->prepare("INSERT INTO workout_templates (name, level, duration, exercises, created_by) SELECT CONCAT(name, ' (Copy)'), level, duration, exercises, ? FROM workout_templates WHERE id = ?")
            ->execute([$uid, $tid]);
        header("Location: workout_plans.php?msg=Template duplicated");
        exit();
    }
}

// 4. Handle Deletions
if (isset($_GET['delete_tpl'])) {
    $pdo->prepare("DELETE FROM workout_templates WHERE id = ?")->execute([$_GET['delete_tpl']]);
    header("Location: workout_plans.php?msg=Template removed");
    exit();
}
if (isset($_GET['remove_assign'])) {
    $pdo->prepare("DELETE FROM complaints WHERE id = ?")->execute([$_GET['remove_assign']]);
    header("Location: workout_plans.php?msg=Assignment removed");
    exit();
}

// --- DATA FETCHING ---
$templates = $pdo->query("SELECT * FROM workout_templates ORDER BY name ASC")->fetchAll();
$clients = $pdo->query("SELECT id, name FROM users WHERE role = 'student' ORDER BY name ASC")->fetchAll();

$assignments = $pdo->prepare("
    SELECT c.id as assign_id, u.name as client_name, c.description, c.created_at, c.status
    FROM complaints c
    JOIN users u ON c.user_id = u.id
    WHERE c.assigned_to = ? AND c.subject = 'WORKOUT'
    ORDER BY c.id DESC
");
$assignments->execute([$uid]);
$assignList = $assignments->fetchAll();

require_once __DIR__ . '/../../includes/header.php'; 
?>

<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="row mb-5 align-items-center">
        <div class="col-md-7">
            <h1 class="fw-bold text-dark mb-1">Workout Plans 📋</h1>
            <p class="text-muted lead mb-0">Design, manage, and assign professional workout routines.</p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex gap-2 justify-content-md-end">
            <button class="btn btn-outline-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#assignModal">
                <i class="bi bi-person-plus me-2"></i> Assign to Client
            </button>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#templateModal">
                <i class="bi bi-plus-lg me-2"></i> Create Template
            </button>
        </div>
    </div>

    <?php if(isset($_GET['msg'])) echo "<div class='alert alert-success rounded-4 border-0 shadow-sm mb-4'><i class='bi bi-check-circle-fill me-2'></i>{$_GET['msg']}</div>"; ?>

    <!-- Assignments Redirect -->
    <div class="alert alert-primary rounded-4 border-0 shadow-sm mb-5 p-4 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-1">Looking for Assignments? 🚀</h5>
            <p class="mb-0 small">Client assignment management has been moved to its own dedicated portal for better tracking.</p>
        </div>
        <a href="../trainer/assign_workout.php" class="btn btn-primary rounded-pill px-4 fw-bold">Open Assignment Portal</a>
    </div>

    <!-- Plan Templates Grid -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Workout Templates</h4>
        <div class="d-flex gap-2">
            <input type="text" id="tplSearch" class="form-control form-control-sm rounded-pill px-3" placeholder="Search templates..." onkeyup="filterTpl()">
            <select id="diffFilter" class="form-select form-select-sm rounded-pill" style="width: 150px;" onchange="filterTpl()">
                <option value="All">All Levels</option>
                <option>Beginner</option><option>Intermediate</option><option>Advanced</option>
            </select>
        </div>
    </div>

    <div class="row g-4" id="tplGrid">
        <?php foreach($templates as $t): 
            $exCount = count(array_filter(explode("\n", $t['exercises'])));
        ?>
        <div class="col-md-6 col-xl-4 tpl-item" data-name="<?= strtolower($t['name']) ?>" data-diff="<?= $t['level'] ?>">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 transition-all hover-lift">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="fw-bold mb-1"><?= htmlspecialchars($t['name']) ?></h5>
                        <span class="badge bg-primary bg-opacity-10 text-primary x-small rounded-pill"><?= $t['level'] ?></span>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm rounded-circle" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                        <ul class="dropdown-menu border-0 shadow rounded-3">
                            <li><a class="dropdown-item small" href="#" onclick='editTpl(<?= json_encode($t) ?>)'><i class="bi bi-pencil me-2"></i> Edit</a></li>
                            <li>
                                <form method="POST" onsubmit="return confirm('Duplicate this template?')">
                                    <input type="hidden" name="tpl_id" value="<?= $t['id'] ?>">
                                    <button type="submit" name="duplicate_tpl" class="dropdown-item small"><i class="bi bi-files me-2"></i> Duplicate</button>
                                </form>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item small text-danger" href="?delete_tpl=<?= $t['id'] ?>" onclick="return confirm('Delete template?')"><i class="bi bi-trash me-2"></i> Delete</a></li>
                        </ul>
                    </div>
                </div>
                
                <p class="text-muted small mb-4 opacity-75"><i class="bi bi-clock me-1"></i> <?= $t['duration'] ?> | <i class="bi bi-list-task me-1"></i> <?= $exCount ?> Exercises</p>
                
                <div class="d-flex gap-2 mt-auto">
                    <button class="btn btn-dark rounded-pill flex-grow-1 btn-sm fw-bold" onclick='openAssignModal(<?= $t['id'] ?>)'>Assign to Client</button>
                    <button class="btn btn-outline-dark btn-sm rounded-circle" onclick='favoriteTpl(this)'><i class="bi bi-star"></i></button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Template Modal -->
<div class="modal fade" id="templateModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-primary text-white border-0 p-4">
                <h5 class="fw-bold mb-0" id="tplTitle">Create Plan Template</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="template_id" id="tpl_id_field">
                <div class="mb-3">
                    <label class="small fw-bold">Plan Name</label>
                    <input type="text" name="name" id="tpl_name" class="form-control rounded-3" placeholder="e.g. Muscle Gain 101" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="small fw-bold">Difficulty Level</label>
                        <select name="level" id="tpl_level" class="form-select rounded-3">
                            <option>Beginner</option><option>Intermediate</option><option>Advanced</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold">Duration</label>
                        <input type="text" name="duration" id="tpl_duration" class="form-control rounded-3" placeholder="e.g. 8 Weeks">
                    </div>
                </div>
                <div>
                    <label class="small fw-bold">Exercises (One per line)</label>
                    <textarea name="exercises" id="tpl_ex" class="form-control rounded-3" rows="8" placeholder="Bench Press: 3x10..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="save_template" class="btn btn-primary rounded-pill px-4 shadow">Save Template</button>
            </div>
        </form>
    </div>
</div>

<!-- Assign Modal -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-dark text-white border-0 p-4">
                <h5 class="fw-bold mb-0">Assign Plan to Client</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="small fw-bold">Select Client</label>
                    <select name="client_id" class="form-select rounded-3" required>
                        <?php foreach($clients as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold">Select Plan Template</label>
                    <select name="tpl_id" id="assign_tpl_select" class="form-select rounded-3" required>
                        <?php foreach($templates as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= $t['name'] ?> (<?= $t['level'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="small fw-bold">Start Date</label>
                        <input type="date" name="start_date" class="form-control rounded-3" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold">Assigned By</label>
                        <input type="text" name="assigned_by_name" class="form-control rounded-3" value="Coach <?= $_SESSION['name'] ?>">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="small fw-bold">Private Coach Notes</label>
                    <textarea name="notes" class="form-control rounded-3" rows="2" placeholder="Any special instructions..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="submit" name="assign_plan" class="btn btn-dark rounded-pill w-100 fw-bold shadow">Confirm Assignment</button>
            </div>
        </form>
    </div>
</div>

<!-- View Plan Modal -->
<div class="modal fade" id="viewPlanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow overflow-hidden">
            <div class="modal-header bg-info text-white p-4">
                <h5 class="modal-title fw-bold">Plan Full Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <pre id="viewContent" class="mb-0 text-wrap p-3 bg-white rounded-3 shadow-sm" style="font-family: inherit; font-size: 0.9rem; line-height: 1.6;"></pre>
            </div>
        </div>
    </div>
</div>

<script>
function editTpl(t) {
    document.getElementById('tplTitle').innerText = 'Update Template';
    document.getElementById('tpl_id_field').value = t.id;
    document.getElementById('tpl_name').value = t.name;
    document.getElementById('tpl_level').value = t.level;
    document.getElementById('tpl_duration').value = t.duration;
    document.getElementById('tpl_ex').value = t.exercises;
    new bootstrap.Modal(document.getElementById('templateModal')).show();
}

function openAssignModal(tplId) {
    document.getElementById('assign_tpl_select').value = tplId;
    new bootstrap.Modal(document.getElementById('assignModal')).show();
}

function viewPlan(content) {
    document.getElementById('viewContent').innerText = content;
    new bootstrap.Modal(document.getElementById('viewPlanModal')).show();
}

function filterTpl() {
    let q = document.getElementById('tplSearch').value.toLowerCase();
    let diff = document.getElementById('diffFilter').value;
    
    document.querySelectorAll('.tpl-item').forEach(item => {
        let name = item.getAttribute('data-name');
        let d = item.getAttribute('data-diff');
        let mSearch = name.includes(q);
        let mDiff = (diff === 'All' || d === diff);
        item.style.display = (mSearch && mDiff) ? 'block' : 'none';
    });
}

function favoriteTpl(btn) {
    let icon = btn.querySelector('i');
    if(icon.classList.contains('bi-star')) {
        icon.classList.replace('bi-star', 'bi-star-fill');
        icon.classList.add('text-warning');
    } else {
        icon.classList.replace('bi-star-fill', 'bi-star');
        icon.classList.remove('text-warning');
    }
}
</script>

<style>
    .x-small { font-size: 0.7rem; }
    .hover-lift:hover { transform: translateY(-5px); box-shadow: 0 1rem 3rem rgba(0,0,0,.08) !important; cursor: default; }
    .transition-all { transition: all 0.3s ease; }
    pre { white-space: pre-wrap; }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
