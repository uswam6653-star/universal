<?php 
require_once __DIR__ . '/../../includes/header.php'; 

// --- JSON Configuration Handling ---
$setting_key = 'gym_membership_plans';

// Fetch existing plans
$stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
$stmt->execute([$setting_key]);
$result = $stmt->fetch();

if (!$result) {
    $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, '[]')")->execute([$setting_key]);
    $plans = [];
} else {
    $plans = json_decode($result['setting_value'], true) ?: [];
}

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_plan'])) {
        $id = !empty($_POST['edit_id']) ? $_POST['edit_id'] : uniqid('PLAN_');
        
        $new_plan = [
            'id' => $id,
            'plan_id' => strtoupper(trim($_POST['plan_id'])),
            'name' => trim($_POST['name']),
            'duration' => trim($_POST['duration']),
            'price' => trim($_POST['price']),
            'facilities' => isset($_POST['facilities']) ? $_POST['facilities'] : [],
            'description' => trim($_POST['description']),
            'is_popular' => isset($_POST['is_popular']) ? 1 : 0,
            'status' => $_POST['status']
        ];

        if (!empty($_POST['edit_id'])) {
            foreach ($plans as $k => $p) {
                if ($p['id'] === $id) { $plans[$k] = $new_plan; break; }
            }
            $success = "Plan updated successfully!";
        } else {
            array_unshift($plans, $new_plan);
            $success = "New plan added successfully!";
        }
        $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?")->execute([json_encode(array_values($plans)), $setting_key]);
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $del_id = $_GET['delete'];
    $plans = array_filter($plans, function($p) use ($del_id) { return $p['id'] !== $del_id; });
    $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?")->execute([json_encode(array_values($plans)), $setting_key]);
    header("Location: manage_plans.php?msg=Plan deleted");
    exit;
}

$active_count = count(array_filter($plans, function($p) { return $p['status'] === 'Active'; }));
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <div class="alert alert-danger fw-bold shadow-sm rounded-pill px-4 py-2 mb-3">DIAGNOSTIC: VERIFIED LATEST VERSION (CARDS UI ACTIVE)</div>
        <h4 class="fw-bold mb-0">Gym Membership Plans</h4>
        <p class="text-muted small mb-0">Define and manage various subscriptions for your athletes</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <div class="btn-group shadow-sm rounded-pill overflow-hidden">
            <div class="btn btn-dark border-0 px-4 py-2 small">Total: <?= count($plans) ?></div>
            <div class="btn btn-success border-0 px-4 py-2 small">Active: <?= $active_count ?></div>
            <button class="btn btn-primary border-0 px-4 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#planModal" onclick="resetForm()">
                <i class="bi bi-plus-lg me-1"></i> Create Plan
            </button>
        </div>
    </div>
</div>

<?php if(isset($success) || isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?= $success ?? htmlspecialchars($_GET['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <?php foreach($plans as $p): 
        $is_pop = isset($p['is_popular']) && $p['is_popular'] == 1;
    ?>
    <div class="col-xl-4 col-md-6">
        <div class="card h-100 border-0 shadow-sm rounded-4 position-relative overflow-hidden plan-card <?= $is_pop ? 'border-top border-primary border-4' : '' ?>">
            <?php if($is_pop): ?>
                <div class="position-absolute bg-primary text-white text-uppercase small fw-bold px-3 py-1 shadow-sm" style="top: 15px; right: -30px; transform: rotate(45deg); width: 120px; text-align: center;">Popular</div>
            <?php endif; ?>
            
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <span class="badge bg-light text-dark border mb-2"><?= htmlspecialchars($p['plan_id']) ?></span>
                        <h5 class="fw-bold mb-0"><?= htmlspecialchars($p['name']) ?></h5>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-link link-dark p-0" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                            <li><a class="dropdown-item" href="#" onclick='editPlan(<?= json_encode($p) ?>)' data-bs-toggle="modal" data-bs-target="#planModal"><i class="bi bi-pencil me-2 text-primary"></i> Edit</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="?delete=<?= $p['id'] ?>" onclick="return confirm('Delete this plan?')"><i class="bi bi-trash me-2"></i> Delete</a></li>
                        </ul>
                    </div>
                </div>

                <div class="mb-4">
                    <h2 class="fw-bold mb-0">Rs. <?= number_format($p['price']) ?></h2>
                    <span class="text-muted small">for <?= htmlspecialchars($p['duration']) ?> Days</span>
                </div>

                <div class="mb-4">
                    <h6 class="small fw-bold text-uppercase text-secondary mb-3">Features Included:</h6>
                    <ul class="list-unstyled mb-0">
                        <?php 
                        $facilities = (isset($p['facilities']) && is_array($p['facilities'])) ? $p['facilities'] : [];
                        $all_facs = ['Gym Access' => 'bi-check-circle', 'Cardio Area' => 'bi-heart-pulse', 'Personal Trainer' => 'bi-person-badge', 'Diet Plan' => 'bi-card-list', 'Locker Room' => 'bi-lock'];
                        foreach($all_facs as $name => $icon): ?>
                            <li class="mb-2 d-flex align-items-center <?= in_array($name, $facilities) ? 'text-dark' : 'text-muted opacity-50' ?>">
                                <i class="bi <?= in_array($name, $facilities) ? $icon . ' text-success' : 'bi-x-circle' ?> me-2"></i>
                                <small class="<?= in_array($name, $facilities) ? 'fw-bold' : '' ?>"><?= $name ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <?php if($p['description']): ?>
                    <p class="small text-muted mb-4 border-top pt-3"><?= nl2br(htmlspecialchars($p['description'])) ?></p>
                <?php endif; ?>

                <div class="d-flex align-items-center justify-content-between pt-2">
                    <span class="badge rounded-pill <?= $p['status'] == 'Active' ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' ?> px-3">
                        <?= $p['status'] ?>
                    </span>
                    <small class="text-muted">ID: #<?= substr($p['id'], -4) ?></small>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if(empty($plans)): ?>
    <div class="col-12">
        <div class="text-center py-5 bg-white rounded-4 shadow-sm">
            <i class="bi bi-calendar-event display-1 text-light mb-3 d-block"></i>
            <h5 class="text-muted">No Membership Plans Yet</h5>
            <button class="btn btn-primary mt-2 rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#planModal" onclick="resetForm()">Create Your First Plan</button>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="planModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" id="planForm" class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            <div class="modal-header bg-dark text-white p-4">
                <h5 class="modal-title fw-bold" id="modalTitle">Create Membership Plan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="small fw-bold mb-2">Plan Code (Short)</label>
                        <input type="text" name="plan_id" id="f_plan_id" class="form-control rounded-3 bg-light border-0" placeholder="e.g. GYM-3M" required>
                    </div>
                    <div class="col-md-8">
                        <label class="small fw-bold mb-2">Display Name</label>
                        <input type="text" name="name" id="f_name" class="form-control rounded-3 bg-light border-0" placeholder="e.g. 3 Months Professional Pack" required>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="small fw-bold mb-2">Duration (Days)</label>
                        <div class="input-group">
                            <input type="number" name="duration" id="f_duration" class="form-control rounded-3 bg-light border-0" placeholder="90" required>
                            <span class="input-group-text bg-light border-0 small">Days</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold mb-2">Member Fee / Price</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0 small">Rs.</span>
                            <input type="number" name="price" id="f_price" class="form-control rounded-3 bg-light border-0" placeholder="12000" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold mb-2">Initial Status</label>
                        <select name="status" id="f_status" class="form-select rounded-3 bg-light border-0">
                            <option>Active</option>
                            <option>Inactive</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <div class="bg-light p-3 rounded-4">
                            <label class="small fw-bold d-block mb-3"><i class="bi bi-star-fill text-warning me-1"></i> Special Features & Facilities</label>
                            <div class="row g-3">
                                <?php foreach(['Gym Access', 'Cardio Area', 'Personal Trainer', 'Diet Plan', 'Locker Room'] as $f): ?>
                                <div class="col-md-4 col-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input fac-check" type="checkbox" name="facilities[]" value="<?= $f ?>" id="chk_<?= str_replace(' ', '', $f) ?>">
                                        <label class="form-check-label small" for="chk_<?= str_replace(' ', '', $f) ?>"><?= $f ?></label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mt-4">
                        <div class="form-check form-switch p-0 ms-0">
                            <label class="form-check-label small fw-bold me-3" for="f_popular">Mark as Best Seller / Popular</label>
                            <input class="form-check-input ms-0 mt-0" type="checkbox" name="is_popular" id="f_popular">
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="small fw-bold mb-2">Advanced Description</label>
                        <textarea name="description" id="f_desc" class="form-control rounded-3 bg-light border-0" rows="3" placeholder="Additional details about this membership..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="submit" name="save_plan" class="btn btn-primary rounded-pill px-5 fw-bold shadow">Save Membership Plan</button>
            </div>
        </form>
    </div>
</div>

<style>
.plan-card { transition: all 0.3s cubic-bezier(.25,.8,.25,1); cursor: default; }
.plan-card:hover { transform: translateY(-7px); box-shadow: 0 1rem 3rem rgba(0,0,0,.1) !important; }
.badge { font-weight: 600; }
</style>

<script>
function resetForm() {
    document.getElementById('planForm').reset();
    document.getElementById('edit_id').value = "";
    document.getElementById('modalTitle').innerText = "Create Membership Plan";
}

function editPlan(data) {
    document.getElementById('modalTitle').innerText = "Modify Membership Plan";
    document.getElementById('edit_id').value = data.id;
    document.getElementById('f_plan_id').value = data.plan_id;
    document.getElementById('f_name').value = data.name;
    document.getElementById('f_duration').value = data.duration;
    document.getElementById('f_price').value = data.price;
    document.getElementById('f_status').value = data.status;
    document.getElementById('f_desc').value = data.description;
    document.getElementById('f_popular').checked = (data.is_popular && data.is_popular == 1);
    
    // Reset facilities first
    document.querySelectorAll('.fac-check').forEach(cb => cb.checked = false);
    // Set checked facilities
    const facs = data.facilities || [];
    facs.forEach(f => {
        const cb = Array.from(document.querySelectorAll('.fac-check')).find(c => c.value === f);
        if(cb) cb.checked = true;
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
