<?php 
require_once '../../core/db.php'; 
require_once '../../core/session.php';

// --- Helper for Permissions: Standardized with sys_pages IDs & Icons ---
$perm_options = [
    '28' => ['label' => 'Manage Athletes', 'sub' => 'Members list, profiles, plans', 'icon' => 'bi-people-fill'],
    '29' => ['label' => 'Manage Gym Staff', 'sub' => 'Employees, trainers, admins', 'icon' => 'bi-person-badge-fill'],
    '23' => ['label' => 'Attendance Tracker', 'sub' => 'Daily check-ins & logs', 'icon' => 'bi-calendar-check-fill'],
    '21' => ['label' => 'Plan Assignments', 'sub' => 'Renew/Assign member plans', 'icon' => 'bi-card-list'],
    '24' => ['label' => 'Trainer Assignments', 'sub' => 'Link trainers to athletes', 'icon' => 'bi-person-gear'],
    '25' => ['label' => 'Payments & Billing', 'sub' => 'Invoices, tax, billing logs', 'icon' => 'bi-cash-stack'],
    '26' => ['label' => 'Analytics & Reports', 'sub' => 'Business health & downloads', 'icon' => 'bi-graph-up-arrow'],
    '22' => ['label' => 'Expiry Alerts', 'sub' => 'Automatic membership renewal', 'icon' => 'bi-alarm-fill'],
    '20' => ['label' => 'System Settings', 'sub' => 'Configure plans & defaults', 'icon' => 'bi-gear-fill'],
    '4'  => ['label' => 'Role Management', 'sub' => 'Control granular access', 'icon' => 'bi-shield-lock-fill']
];

// 1. Handle Update Permissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_perms'])) {
    $uid = $_POST['user_id'];
    $selected_perms = isset($_POST['perms']) ? implode(',', $_POST['perms']) : '';

    $stmt = $pdo->prepare("SELECT identity_no FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $meta = $stmt->fetchColumn();
    $parts = explode('|', $meta ?? '');
    while (count($parts) < 11) $parts[] = '';
    
    $parts[9] = $selected_perms;
    $new_meta = implode('|', $parts);

    $pdo->prepare("UPDATE users SET identity_no = ? WHERE id = ?")->execute([$new_meta, $uid]);
    $success_msg = "Permissions updated successfully! ✨";
}

// 2. Handle New Role Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_role'])) {
    $role_name = trim($_POST['role_name'] ?? '');
    $role_key = strtolower(str_replace(' ', '_', $role_name));
    
    if(!empty($role_name)) {
        $pdo->prepare("INSERT IGNORE INTO sys_roles (role_key, role_name) VALUES (?, ?)")->execute([$role_key, $role_name]);
        $success_msg = "Naya Role '$role_name' add ho gaya! 🛠️";
    }
}

require_once __DIR__ . '/../../includes/header.php'; 

// Fetch all staff (non-students)
$staff = $pdo->query("
    SELECT u.id, u.name, u.registration_no, u.role, u.identity_no, r.role_name 
    FROM users u
    LEFT JOIN sys_roles r ON u.role = r.role_key
    WHERE u.role != 'student' AND u.role != 'super_admin'
    ORDER BY u.name ASC
")->fetchAll();

?>

<div class="row g-4">
    <div class="col-12">
        <?php if(isset($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0 rounded-4 shadow-sm">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(isset($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 rounded-4 shadow-sm">
                <i class="bi bi-check-circle-fill me-2"></i><?= $success_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 p-4">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="fw-bold mb-0">Staff Permissions Management (Authorized Personnel)</h5>
                        <p class="text-muted small mb-0">Kaunsa staff member kya dekh sakta hai, yahin se control karein.</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 rounded-start-pill"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="staffSearch" class="form-control border-start-0 rounded-end-pill" placeholder="Staff Name ya ID dhoondein...">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light small fw-bold text-uppercase">
                            <tr>
                                <th class="ps-4">Staff Name & ID</th>
                                <th>Designation / Role</th>
                                <th>Assigned Permissions</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="staffTableBody">
                            <?php foreach($staff as $s): 
                                $parts = explode('|', $s['identity_no'] ?? '');
                                $user_perms = array_filter(explode(',', $parts[9] ?? ''));
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <strong><?= htmlspecialchars($s['name']) ?></strong><br>
                                    <small class="text-muted">ID: <?= htmlspecialchars($s['registration_no']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle">
                                        <?= htmlspecialchars($s['role_name'] ?: ucfirst($s['role'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="text-muted small"><i class="bi bi-shield-shaded me-1"></i> Standard Role Access</span>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#permModal<?= $s['id'] ?>">
                                        <i class="bi bi-shield-lock me-1"></i> Manage
                                    </button>
                                </td>
                            </tr>

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Permission Modals (Moved out of table for professional layout) -->
        <?php foreach($staff as $s): 
            $parts = explode('|', $s['identity_no'] ?? '');
            $user_perms = array_filter(explode(',', $parts[9] ?? ''));
        ?>
        <div class="modal fade" id="permModal<?= $s['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content border-0 rounded-4 shadow">
                    <div class="modal-header border-bottom p-3">
                        <h6 class="fw-bold mb-0">Assign Permissions: <?= htmlspecialchars($s['name']) ?></h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-0">
                        <input type="hidden" name="user_id" value="<?= $s['id'] ?>">
                        <div class="p-3 bg-light border-bottom mb-0 small text-muted">
                            <i class="bi bi-info-circle me-1"></i> These settings override default role-based access for this specific user.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <tbody>
                                    <?php foreach($perm_options as $id => $opt): ?>
                                    <tr>
                                        <td class="ps-3" width="40"><i class="bi <?= $opt['icon'] ?> text-primary fs-5"></i></td>
                                        <td>
                                            <div class="fw-bold fs-6"><?= $opt['label'] ?></div>
                                            <div class="text-muted small" style="font-size: 0.75rem;"><?= $opt['sub'] ?></div>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input" type="checkbox" name="perms[]" value="<?= $id ?>" id="p<?= $s['id'].$id ?>" <?= in_array($id, $user_perms) ? 'checked' : '' ?>>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer border-top p-3">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="save_perms" class="btn btn-primary rounded-pill px-4 fw-bold">Update Permissions</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-5 text-center">
            <p class="text-muted small">Nayi staff category ya role banani hai? <a href="#" class="text-primary fw-bold" data-bs-toggle="collapse" data-bs-target="#roleCreator">Add New Role</a></p>
            <div class="collapse" id="roleCreator">
                 <div class="card border-0 shadow-sm rounded-4 text-start mt-3">
                    <div class="card-body p-4">
                        <form method="POST" class="row g-3 align-items-end">
                            <div class="col-md-9">
                                <label class="small fw-bold mb-1">New Role Name (Designation)</label>
                                <input type="text" name="role_name" class="form-control rounded-3 border-primary-subtle" placeholder="e.g. Sales Executive / Manager" required>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" name="create_role" class="btn btn-dark w-100 rounded-3">
                                    <i class="bi bi-plus-circle me-1"></i> Create
                                </button>
                            </div>
                        </form>
                    </div>
                 </div>
            </div>
        </div>
    </div>
</div>

<script>
// Simple Staff Search Logic
document.getElementById('staffSearch').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#staffTableBody tr');
    
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>

<style>
    .modal-content { border-radius: 1rem !important; overflow: hidden; border: none !important; box-shadow: 0 1rem 3rem rgba(0,0,0,0.175) !important; }
    .badge { font-weight: 500; }
    .table-hover tbody tr:hover { background-color: rgba(var(--bs-primary-rgb), 0.05); }
    .form-check-input { cursor: pointer; transform: scale(1.1); }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>