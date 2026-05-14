<?php 
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/db.php';

$uid = $_SESSION['user_id'];
$uname = $_SESSION['name'];

// 1. Handle Member Deletion
if (isset($_GET['delete_id'])) {
    $did = $_GET['delete_id'];
    // Delete from complaints and user (simplified for demo)
    $pdo->prepare("DELETE FROM complaints WHERE user_id = ? AND assigned_to = ?")->execute([$did, $uid]);
    $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'student'")->execute([$did]);
    header("Location: manage_members.php?msg=Member deleted");
    exit();
}

// 2. Handle Status Toggle
if (isset($_GET['toggle_status'])) {
    $tid = $_GET['toggle_status'];
    $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?")->execute([$tid]);
    header("Location: manage_members.php?msg=Status updated");
    exit();
}

// 3. Handle Add/Update Member
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_member'])) {
        $name = $_POST['name'];
        $age = $_POST['age'];
        $goal = $_POST['goal'];
        $plan = $_POST['plan'];
        $weight = $_POST['weight'];
        $email = strtolower(str_replace(' ', '', $name)) . rand(10,99) . "@gym.com";
        $reg = "REG-" . rand(1000, 9999);
        
        $meta = "$plan|0|$age|$goal|0|0|$uname|0|2026-12-31| |trainer:$uid|";
        $hash = password_hash('123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, registration_no, identity_no, is_active) VALUES (?, ?, ?, 'student', ?, ?, 1)");
        $stmt->execute([$name, $email, $hash, $reg, $meta]);
        $new_id = $pdo->lastInsertId();
        
        $pdo->prepare("INSERT INTO complaints (user_id, subject, description, assigned_to, status) VALUES (?, 'WORKOUT', ?, ?, 'Active')")
            ->execute([$new_id, "Standard $plan Routine", $uid]);
            
        $pdo->prepare("INSERT INTO sys_activity_logs (user_id, action, details) VALUES (?, 'PROGRESS', ?)")
            ->execute([$new_id, "Weight: {$weight}kg | Initial Check (Logged by Coach: $uname)"]);

        header("Location: manage_members.php?msg=Member added successfully");
        exit();
    }

    if (isset($_POST['update_member'])) {
        $mid = $_POST['member_id'];
        $name = $_POST['name'];
        $age = $_POST['age'];
        $goal = $_POST['goal'];
        $plan = $_POST['plan'];
        
        // Fetch current meta to preserve other indices
        $curr = $pdo->prepare("SELECT identity_no FROM users WHERE id = ?");
        $curr->execute([$mid]);
        $meta = explode('|', $curr->fetchColumn() ?? '');
        $meta = array_pad($meta, 11, '');
        
        $meta[0] = $plan;
        $meta[2] = $age;
        $meta[3] = $goal;
        $new_meta = implode('|', $meta);
        
        $pdo->prepare("UPDATE users SET name = ?, identity_no = ? WHERE id = ?")->execute([$name, $new_meta, $mid]);
        header("Location: manage_members.php?msg=Member details updated");
        exit();
    }
}

// 4. Fetch Members
$stmt = $pdo->prepare("
    SELECT u.*, c.created_at as assigned_date, 
           (SELECT details FROM sys_activity_logs WHERE user_id = u.id AND action = 'PROGRESS' ORDER BY created_at DESC LIMIT 1) as last_progress
    FROM complaints c
    JOIN users u ON c.user_id = u.id
    WHERE c.assigned_to = ? AND c.subject = 'WORKOUT'
    ORDER BY u.id DESC
");
$stmt->execute([$uid]);
$members = $stmt->fetchAll();

$activeCount = count(array_filter($members, fn($m) => $m['is_active']));

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row g-4">
    <!-- Stats Cards -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="small text-uppercase opacity-75 fw-bold mb-1">Total Clients</h6>
                    <h2 class="fw-bold mb-0"><?= count($members) ?></h2>
                </div>
                <i class="bi bi-people display-6 opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="small text-uppercase text-muted fw-bold mb-1">Active Now</h6>
                    <h2 class="fw-bold mb-0 text-success"><?= $activeCount ?></h2>
                </div>
                <i class="bi bi-person-check text-success display-6 opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="small text-uppercase text-muted fw-bold mb-1">Avg. Progress</h6>
                    <h2 class="fw-bold mb-0 text-primary">85%</h2>
                </div>
                <i class="bi bi-graph-up-arrow text-primary display-6 opacity-25"></i>
            </div>
        </div>
    </div>

    <!-- Main Table Section -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-0 p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h5 class="fw-bold mb-0">My Clients Management</h5>
                <div class="d-flex gap-3">
                    <input type="text" id="memberSearch" class="form-control form-control-sm rounded-pill px-3" style="width: 200px;" placeholder="Search name...">
                    <button class="btn btn-primary btn-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                        <i class="bi bi-plus-lg me-1"></i> Add Client
                    </button>
                </div>
            </div>
            
            <div class="card-body p-0">
                <?php if(isset($_GET['msg'])) echo "<div class='alert alert-info m-4 rounded-3 small'>{$_GET['msg']}</div>"; ?>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="memberTable">
                        <thead class="bg-light small fw-bold">
                            <tr>
                                <th class="ps-4">Name</th>
                                <th>Age</th>
                                <th>Goal</th>
                                <th>Plan</th>
                                <th>Latest Weight</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($members as $m): 
                                $meta = explode('|', $m['identity_no'] ?? '');
                                $age = $meta[2] ?? 'N/A';
                                $goal = $meta[3] ?? 'N/A';
                                $plan = $meta[0] ?? 'Standard';
                                preg_match('/Weight: ([\d.]+)kg/', $m['last_progress'] ?? '', $wt);
                                $weight = $wt[1] ?? 'N/A';
                            ?>
                            <tr>
                                <td class="ps-4 py-3 fw-bold text-dark"><?= htmlspecialchars($m['name']) ?></td>
                                <td><?= $age ?></td>
                                <td><span class="badge bg-info bg-opacity-10 text-info rounded-pill x-small"><?= htmlspecialchars($goal) ?></span></td>
                                <td class="small"><?= htmlspecialchars($plan) ?></td>
                                <td class="fw-bold text-primary"><?= $weight ?> kg</td>
                                <td>
                                    <a href="?toggle_status=<?= $m['id'] ?>" class="text-decoration-none">
                                        <span class="badge rounded-pill px-3 <?= $m['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= $m['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </a>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group rounded-3 overflow-hidden">
                                        <button class="btn btn-sm btn-light border-end" title="View" onclick='viewMember(<?= json_encode($m) ?>, "<?= $age ?>", "<?= $goal ?>", "<?= $plan ?>")'><i class="bi bi-eye text-primary"></i></button>
                                        <button class="btn btn-sm btn-light border-end" title="Edit" onclick='editMember(<?= json_encode($m) ?>, "<?= $age ?>", "<?= $goal ?>", "<?= $plan ?>")'><i class="bi bi-pencil text-success"></i></button>
                                        <a href="?delete_id=<?= $m['id'] ?>" class="btn btn-sm btn-light" title="Delete" onclick="return confirm('Remove this member?')"><i class="bi bi-trash text-danger"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-primary text-white border-0 p-4">
                <h5 class="fw-bold mb-0">Register New Member</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="small fw-bold">Full Name</label>
                        <input type="text" name="name" class="form-control rounded-3" required>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold">Age</label>
                        <input type="number" name="age" class="form-control rounded-3" required>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold">Current Weight (kg)</label>
                        <input type="number" name="weight" class="form-control rounded-3" required>
                    </div>
                    <div class="col-12">
                        <label class="small fw-bold">Fitness Goal</label>
                        <select name="goal" class="form-select rounded-3">
                            <option>Weight Loss</option>
                            <option>Muscle Gain</option>
                            <option>Strength</option>
                            <option>Fat Loss</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="small fw-bold">Training Plan</label>
                        <select name="plan" class="form-select rounded-3">
                            <option>Weight Loss (Basic)</option>
                            <option>Muscle Building (Pro)</option>
                            <option>Strength & Conditioning</option>
                            <option>Flexibility & Yoga</option>
                            <option>Premium Full Access</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_member" class="btn btn-primary rounded-pill px-4 shadow">Save Member</button>
            </div>
        </form>
    </div>
</div>

<!-- View Member Modal -->
<div class="modal fade" id="viewMemberModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-dark text-white p-4">
                <h5 class="modal-title fw-bold">Member Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="viewContent"></div>
        </div>
    </div>
</div>

<!-- Edit Member Modal -->
<div class="modal fade" id="editMemberModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-success text-white border-0 p-4">
                <h5 class="modal-title fw-bold">Edit Member Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="member_id" id="edit_id">
                <div class="mb-3">
                    <label class="small fw-bold">Full Name</label>
                    <input type="text" name="name" id="edit_name" class="form-control rounded-3" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="small fw-bold">Age</label>
                        <input type="number" name="age" id="edit_age" class="form-control rounded-3" required>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold">Training Plan</label>
                        <select name="plan" id="edit_plan" class="form-select rounded-3">
                            <option>Weight Loss (Basic)</option>
                            <option>Muscle Building (Pro)</option>
                            <option>Strength & Conditioning</option>
                            <option>Flexibility & Yoga</option>
                            <option>Premium Full Access</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="small fw-bold">Fitness Goal</label>
                    <select name="goal" id="edit_goal" class="form-select rounded-3">
                        <option>Weight Loss</option>
                        <option>Muscle Gain</option>
                        <option>Strength</option>
                        <option>Fat Loss</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="update_member" class="btn btn-success rounded-pill px-4">Update Member</button>
            </div>
        </form>
    </div>
</div>

<script>
function viewMember(m, age, goal, plan) {
    document.getElementById('viewContent').innerHTML = `
        <div class="text-center mb-4">
            <div class="avatar-lg bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold mx-auto mb-2" style="width:70px; height:70px; font-size: 1.5rem;">${m.name.charAt(0)}</div>
            <h4 class="fw-bold mb-0">${m.name}</h4>
            <span class="badge bg-light text-dark border rounded-pill">${m.registration_no}</span>
        </div>
        <div class="row g-3">
            <div class="col-6"><div class="p-3 bg-light rounded-4"><small class="text-muted d-block small fw-bold">Age</small><b>${age} Yrs</b></div></div>
            <div class="col-6"><div class="p-3 bg-light rounded-4"><small class="text-muted d-block small fw-bold">Goal</small><b>${goal}</b></div></div>
            <div class="col-12"><div class="p-3 bg-light rounded-4"><small class="text-muted d-block small fw-bold">Plan</small><b>${plan}</b></div></div>
            <div class="col-12"><div class="p-3 bg-light rounded-4"><small class="text-muted d-block small fw-bold">Email</small><b>${m.email}</b></div></div>
        </div>
    `;
    new bootstrap.Modal(document.getElementById('viewMemberModal')).show();
}

function editMember(m, age, goal, plan) {
    document.getElementById('edit_id').value = m.id;
    document.getElementById('edit_name').value = m.name;
    document.getElementById('edit_age').value = age;
    document.getElementById('edit_plan').value = plan;
    document.getElementById('edit_goal').value = goal;
    new bootstrap.Modal(document.getElementById('editMemberModal')).show();
}

document.getElementById('memberSearch').addEventListener('keyup', function() {
    let q = this.value.toLowerCase();
    document.querySelectorAll('#memberTable tbody tr').forEach(tr => {
        tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>

<style>
    .x-small { font-size: 0.7rem; }
    #memberTable tbody tr:hover { background-color: #f8f9fa; }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
