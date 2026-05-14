<?php 
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/db.php';

$uid = $_SESSION['user_id'];
$setting_key = 'gym_membership_plans';

// 1. Fetch Current Plans
$stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
$stmt->execute([$setting_key]);
$res = $stmt->fetch();
$plans = $res ? (json_decode($res['setting_value'], true) ?: []) : [];

// 2. Handle CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_plan'])) {
        $id = $_POST['plan_id'] ?: 'PLAN-' . strtoupper(substr(uniqid(), -4));
        $features = isset($_POST['features']) ? $_POST['features'] : [];
        
        $new_plan = [
            'id' => $id,
            'name' => $_POST['name'],
            'price' => $_POST['price'],
            'duration' => $_POST['duration'],
            'badge' => $_POST['badge'],
            'features' => $features,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'is_popular' => isset($_POST['is_popular']) ? 1 : 0
        ];

        if (!empty($_POST['plan_id'])) {
            foreach ($plans as $k => $v) {
                if ($v['id'] === $id) { $plans[$k] = $new_plan; break; }
            }
        } else {
            $plans[] = $new_plan;
        }
        $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?")->execute([json_encode($plans), $setting_key]);
        header("Location: gym_membership_plans.php?msg=Plan saved successfully");
        exit();
    }

    if (isset($_POST['duplicate_plan'])) {
        $id = $_POST['id_to_dup'];
        foreach ($plans as $p) {
            if ($p['id'] === $id) {
                $new_p = $p;
                $new_p['id'] = 'PLAN-' . strtoupper(substr(uniqid(), -4));
                $new_p['name'] .= ' (Copy)';
                $plans[] = $new_p;
                break;
            }
        }
        $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?")->execute([json_encode($plans), $setting_key]);
        header("Location: gym_membership_plans.php?msg=Plan duplicated");
        exit();
    }
}

if (isset($_GET['delete'])) {
    $del = $_GET['delete'];
    $plans = array_filter($plans, fn($p) => $p['id'] !== $del);
    $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?")->execute([json_encode(array_values($plans)), $setting_key]);
    header("Location: gym_membership_plans.php?msg=Plan deleted");
    exit();
}

if (isset($_GET['toggle'])) {
    $tid = $_GET['toggle'];
    foreach ($plans as $k => $v) {
        if ($v['id'] === $tid) { $plans[$k]['is_active'] = !$v['is_active']; break; }
    }
    $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?")->execute([json_encode($plans), $setting_key]);
    header("Location: gym_membership_plans.php?msg=Status updated");
    exit();
}

$active_count = count(array_filter($plans, fn($p) => $p['is_active']));

require_once __DIR__ . '/../../includes/header.php'; 
?>

<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="row mb-5 align-items-center">
        <div class="col-md-7">
            <h1 class="fw-bold text-dark mb-1">Gym Membership Plans 💎</h1>
            <p class="text-muted lead mb-0">Create and manage premium subscription packages for your members.</p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0">
            <button class="btn btn-primary rounded-pill px-4 shadow-sm py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#planModal" onclick="resetForm()">
                <i class="bi bi-plus-lg me-2"></i> Create New Plan
            </button>
        </div>
    </div>

    <!-- Stats Bar -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                        <i class="bi bi-layers text-primary h4 mb-0"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Total Plans</small>
                        <h4 class="fw-bold mb-0"><?= count($plans) ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-success bg-opacity-10 rounded-circle p-2 me-3">
                        <i class="bi bi-check-circle text-success h4 mb-0"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block">Active Plans</small>
                        <h4 class="fw-bold mb-0"><?= $active_count ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if(isset($_GET['msg'])) echo "<div class='alert alert-success rounded-4 border-0 shadow-sm mb-4'><i class='bi bi-info-circle-fill me-2'></i>{$_GET['msg']}</div>"; ?>

    <!-- Plan Cards Grid -->
    <div class="row g-4">
        <?php foreach($plans as $p): ?>
        <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 transition-all hover-lift position-relative overflow-hidden <?= $p['is_popular'] ? 'popular-highlight' : '' ?>">
                <?php if($p['is_popular']): ?>
                    <div class="popular-tag text-uppercase">Most Popular</div>
                <?php endif; ?>
                
                <div class="card-body p-4 pt-5">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h4 class="fw-bold mb-1"><?= htmlspecialchars($p['name']) ?></h4>
                            <span class="badge rounded-pill <?= $p['is_active'] ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary' ?> x-small">
                                <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>
                        <div class="text-end">
                            <h3 class="fw-bold text-primary mb-0">Rs. <?= number_format($p['price']) ?></h3>
                            <small class="text-muted">/ <?= $p['duration'] ?></small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <?php if(!empty($p['badge'])): ?>
                            <span class="badge bg-warning text-dark rounded-pill px-3 py-2 x-small fw-bold"><i class="bi bi-star-fill me-1"></i> <?= $p['badge'] ?></span>
                        <?php endif; ?>
                    </div>

                    <ul class="list-unstyled mb-5">
                        <?php 
                        $all_f = ['Gym Access' => 'bi-door-open', 'Personal Trainer' => 'bi-person-check', 'Diet Plan' => 'bi-basket', 'Cardio Access' => 'bi-heart-pulse'];
                        foreach($all_f as $f_name => $f_icon): 
                            $has = in_array($f_name, $p['features'] ?? []);
                        ?>
                        <li class="mb-3 d-flex align-items-center <?= $has ? '' : 'text-muted opacity-50' ?>">
                            <i class="bi <?= $f_icon ?> me-3 h5 mb-0 <?= $has ? 'text-primary' : '' ?>"></i>
                            <span class="<?= $has ? 'fw-bold' : '' ?>"><?= $f_name ?></span>
                            <?php if($has): ?>
                                <i class="bi bi-check2 ms-auto text-success"></i>
                            <?php else: ?>
                                <i class="bi bi-x ms-auto text-danger"></i>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-dark btn-sm rounded-pill flex-grow-1 py-2 fw-bold" onclick='editPlan(<?= json_encode($p) ?>)'>
                            <i class="bi bi-pencil me-1"></i> Edit
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm rounded-circle p-2" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                            <ul class="dropdown-menu border-0 shadow rounded-3">
                                <li><a class="dropdown-item small" href="?toggle=<?= $p['id'] ?>"><i class="bi <?= $p['is_active'] ? 'bi-slash-circle' : 'bi-check-circle' ?> me-2"></i> <?= $p['is_active'] ? 'Deactivate' : 'Activate' ?></a></li>
                                <li>
                                    <form method="POST">
                                        <input type="hidden" name="id_to_dup" value="<?= $p['id'] ?>">
                                        <button type="submit" name="duplicate_plan" class="dropdown-item small"><i class="bi bi-files me-2"></i> Duplicate</button>
                                    </form>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item small text-danger" href="?delete=<?= $p['id'] ?>" onclick="return confirm('Delete this plan?')"><i class="bi bi-trash me-2"></i> Delete</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Plan Modal -->
<div class="modal fade" id="planModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-primary text-white p-4 border-0">
                <h5 class="fw-bold mb-0" id="modalTitle">Create Membership Plan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="plan_id" id="f_id">
                <div class="mb-3">
                    <label class="small fw-bold mb-1">Plan Name</label>
                    <input type="text" name="name" id="f_name" class="form-control rounded-3" placeholder="e.g. Gold Membership" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="small fw-bold mb-1">Price (Rs.)</label>
                        <input type="number" name="price" id="f_price" class="form-control rounded-3" placeholder="5000" required>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold mb-1">Duration</label>
                        <input type="text" name="duration" id="f_duration" class="form-control rounded-3" placeholder="e.g. 30 Days" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold mb-1">Badge (Optional)</label>
                    <input type="text" name="badge" id="f_badge" class="form-control rounded-3" placeholder="e.g. Most Popular">
                </div>
                
                <label class="small fw-bold mb-2">Included Features</label>
                <div class="row g-2 mb-4">
                    <?php foreach(['Gym Access', 'Personal Trainer', 'Diet Plan', 'Cardio Access'] as $feat): ?>
                    <div class="col-6">
                        <div class="form-check p-2 rounded-3 border">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="features[]" value="<?= $feat ?>" id="chk_<?= str_replace(' ', '', $feat) ?>">
                            <label class="form-check-label small fw-bold" for="chk_<?= str_replace(' ', '', $feat) ?>"><?= $feat ?></label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex gap-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="f_active" checked>
                        <label class="form-check-label small fw-bold">Active</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_popular" id="f_popular">
                        <label class="form-check-label small fw-bold text-primary">Mark as Popular</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="submit" name="save_plan" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow">Save Membership Plan</button>
            </div>
        </form>
    </div>
</div>

<style>
    .x-small { font-size: 0.75rem; }
    .hover-lift:hover { transform: translateY(-7px); box-shadow: 0 1.5rem 4rem rgba(0,0,0,.15) !important; }
    .transition-all { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .popular-highlight { border: 2px solid #0d6efd !important; }
    .popular-tag { position: absolute; top: 12px; right: -30px; background: #0d6efd; color: white; padding: 5px 40px; transform: rotate(45deg); font-size: 0.65rem; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
    .avatar-sm { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; }
</style>

<script>
function resetForm() {
    document.getElementById('modalTitle').innerText = 'Create Membership Plan';
    document.getElementById('f_id').value = '';
    document.getElementById('f_name').value = '';
    document.getElementById('f_price').value = '';
    document.getElementById('f_duration').value = '';
    document.getElementById('f_badge').value = '';
    document.querySelectorAll('.form-check-input').forEach(i => { if(i.type==='checkbox') i.checked = (i.id === 'f_active'); });
}

function editPlan(p) {
    document.getElementById('modalTitle').innerText = 'Edit Plan: ' + p.name;
    document.getElementById('f_id').value = p.id;
    document.getElementById('f_name').value = p.name;
    document.getElementById('f_price').value = p.price;
    document.getElementById('f_duration').value = p.duration;
    document.getElementById('f_badge').value = p.badge;
    document.getElementById('f_active').checked = p.is_active;
    document.getElementById('f_popular').checked = p.is_popular;
    
    // Features
    document.querySelectorAll('input[name="features[]"]').forEach(chk => {
        chk.checked = p.features ? p.features.includes(chk.value) : false;
    });
    
    new bootstrap.Modal(document.getElementById('planModal')).show();
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
