<?php 
require_once __DIR__ . '/../../includes/header.php'; 

// Fetch Members with plan data
$all_members = $pdo->query("SELECT id, name, registration_no, identity_no FROM users WHERE role = 'student' ORDER BY name ASC")->fetchAll();

// Fetch Plans
$plans_raw = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'gym_membership_plans'")->fetch();
$all_plans = $plans_raw ? json_decode($plans_raw['setting_value'], true) : [];

// ---- Handle Renew ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['renew_member'])) {
    $uid = $_POST['renew_uid'];
    $new_start = $_POST['new_start'];
    $new_end = $_POST['new_end'];
    $new_plan = $_POST['new_plan'];

    // Load current identity_no for this member
    $stmt = $pdo->prepare("SELECT identity_no FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $row = $stmt->fetch();
    $parts = $row ? explode('|', $row['identity_no']) : [];
    while (count($parts) < 10) $parts[] = '';
    
    // index 5 = plan type, index 8 = end_date
    $parts[5] = $new_plan;
    $parts[8] = $new_end;
    $new_meta = implode('|', $parts);

    $pdo->prepare("UPDATE users SET identity_no = ? WHERE id = ?")->execute([$new_meta, $uid]);
    
    // Also add a payment record for the renewal
    $pay_key = 'gym_payments';
    $pay_stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $pay_stmt->execute([$pay_key]);
    $pay_res = $pay_stmt->fetch();
    $payments = $pay_res ? json_decode($pay_res['setting_value'], true) : [];
    
    // Find member name
    $mem_name_stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $mem_name_stmt->execute([$uid]);
    $mem_name = $mem_name_stmt->fetchColumn();
    
    // Find plan price
    $plan_price = 0;
    foreach ($all_plans as $pl) {
        if ($pl['name'] === $new_plan) { $plan_price = $pl['price']; break; }
    }
    
    $new_payment = [
        'id' => 'INV-REN-' . strtoupper(substr(uniqid(), -5)),
        'member_id' => $uid,
        'member_name' => $mem_name,
        'plan_name' => $new_plan . ' (Renewal)',
        'amount' => $plan_price,
        'payment_date' => $new_start,
        'method' => 'Cash',
        'status' => 'Pending'
    ];
    array_unshift($payments, $new_payment);
    
    if ($pay_res) {
        $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?")->execute([json_encode($payments), $pay_key]);
    } else {
        $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)")->execute([$pay_key, json_encode($payments)]);
    }
    
    $success = "Membership renewed for $mem_name successfully!";
}

// ---- Decode member plan data ----
$today = new DateTime();
$members_data = [];

foreach ($all_members as $m) {
    if (empty($m['identity_no'])) continue;
    
    $parts = explode('|', $m['identity_no']);
    while (count($parts) < 10) $parts[] = '';
    
    $plan_name = $parts[5] ?: 'Monthly'; // Corrected index for plan
    $start_date_str = $parts[3] ?: '';
    $end_date_str = $parts[8] ?: ''; // Expiry date

    if (empty($end_date_str)) continue; 

    try {
        $end_dt = new DateTime($end_date_str);
        $diff = $today->diff($end_dt);
        $days_left = (int)$diff->format('%r%a');
        
        if ($days_left < 0) $status = 'Expired';
        elseif ($days_left <= 7) $status = 'Expiring Soon';
        else $status = 'Active';

        $members_data[] = [
            'id' => $m['id'],
            'name' => $m['name'],
            'reg_no' => $m['registration_no'],
            'plan' => $plan_name,
            'start' => $start_date_str,
            'end' => $end_date_str,
            'days_left' => $days_left,
            'status' => $status
        ];
    } catch (Exception $e) {
        continue;
    }
}

// Sort: Expired first
usort($members_data, fn($a, $b) => $a['days_left'] <=> $b['days_left']);

// ---- Filters ----
$f_name = $_GET['f_name'] ?? '';
$f_status = $_GET['f_status'] ?? '';

$filtered = $members_data;
if ($f_name) $filtered = array_filter($filtered, fn($m) => stripos($m['name'], $f_name) !== false);
if ($f_status) $filtered = array_filter($filtered, fn($m) => $m['status'] === $f_status);

// Counts
$count_expired = count(array_filter($members_data, fn($m) => $m['status'] === 'Expired'));
$count_expiring = count(array_filter($members_data, fn($m) => $m['status'] === 'Expiring Soon'));
$count_active = count(array_filter($members_data, fn($m) => $m['status'] === 'Active'));

?>

<div class="row g-4">
    <div class="col-12">
        <?php if(isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 rounded-4 shadow-sm py-3 mb-4">
                <i class="bi bi-check-circle-fill me-2"></i><?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Summary -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 rounded-4 shadow-sm bg-danger text-white">
                    <div class="card-body p-4 d-flex justify-content-between">
                        <div><p class="mb-1 opacity-75">Expired</p><h2 class="fw-bold mb-0"><?= $count_expired ?></h2></div>
                        <i class="bi bi-x-circle display-4 opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 rounded-4 shadow-sm bg-warning text-dark">
                    <div class="card-body p-4 d-flex justify-content-between">
                        <div><p class="mb-1 opacity-75">Expiring Week</p><h2 class="fw-bold mb-0"><?= $count_expiring ?></h2></div>
                        <i class="bi bi-alarm display-4 opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 rounded-4 shadow-sm bg-success text-white">
                    <div class="card-body p-4 d-flex justify-content-between">
                        <div><p class="mb-1 opacity-75">Valid/Active</p><h2 class="fw-bold mb-0"><?= $count_active ?></h2></div>
                        <i class="bi bi-check-circle display-4 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white p-4 border-0">
                <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-bell-fill me-2 text-warning"></i> Membership Expiry Alerts</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="GET" class="row g-2 mb-4">
                    <div class="col-md-6">
                        <input type="text" name="f_name" class="form-control rounded-pill ps-4" placeholder="Search by name..." value="<?= htmlspecialchars($f_name) ?>">
                    </div>
                    <div class="col-md-4">
                        <select name="f_status" class="form-select rounded-pill ps-4">
                            <option value="">All Statuses</option>
                            <option value="Expired" <?= $f_status=='Expired'?'selected':'' ?>>Expired</option>
                            <option value="Expiring Soon" <?= $f_status=='Expiring Soon'?'selected':'' ?>>Expiring Soon</option>
                            <option value="Active" <?= $f_status=='Active'?'selected':'' ?>>Active</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">Filter</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light small fw-bold text-uppercase">
                            <tr>
                                <th class="ps-4">Member</th>
                                <th>Plan</th>
                                <th>Expiry Date</th>
                                <th>Days Remaining</th>
                                <th class="text-end pe-4">Manage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($filtered as $m): ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold d-block"><?= htmlspecialchars($m['name']) ?></span>
                                    <small class="text-muted"><?= $m['reg_no'] ?></small>
                                </td>
                                <td><span class="badge bg-dark-subtle text-dark"><?= $m['plan'] ?></span></td>
                                <td><span class="fw-bold text-<?= $m['status'] == 'Expired' ? 'danger' : ($m['status'] == 'Expiring Soon' ? 'warning' : 'success') ?>"><?= date('d M Y', strtotime($m['end'])) ?></span></td>
                                <td>
                                    <?php if($m['days_left'] < 0): ?>
                                        <span class="text-danger fw-bold"><?= abs($m['days_left']) ?> days ago</span>
                                    <?php else: ?>
                                        <span class="text-success fw-bold"><?= $m['days_left'] ?> days left</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                        <button class="btn btn-sm btn-white text-success" data-bs-toggle="modal" data-bs-target="#renewModal<?= $m['id'] ?>" title="Renew"><i class="bi bi-arrow-repeat"></i></button>
                                        <button class="btn btn-sm btn-white text-info" onclick="sendEnglishReminder('<?= addslashes($m['name']) ?>', '<?= $m['days_left'] ?>')"><i class="bi bi-bell-fill"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Renew Modal -->
                            <div class="modal fade" id="renewModal<?= $m['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <form method="POST" class="modal-content border-0 rounded-4">
                                        <div class="modal-header border-0 p-4 bg-success text-white">
                                            <h5 class="fw-bold mb-0">Renew Membership</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <input type="hidden" name="renew_uid" value="<?= $m['id'] ?>">
                                            <div class="mb-3">
                                                <label class="small fw-bold mb-1">Select Plan</label>
                                                <select name="new_plan" class="form-select rounded-3" required>
                                                    <option value="<?= $m['plan'] ?>"><?= $m['plan'] ?> (Current)</option>
                                                    <?php foreach($all_plans as $pl): ?>
                                                        <option value="<?= $pl['name'] ?>"><?= $pl['name'] ?> (Rs. <?= $pl['price'] ?>)</option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <label class="small fw-bold mb-1">New Start Date</label>
                                                    <input type="date" name="new_start" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required>
                                                </div>
                                                <div class="col-6">
                                                    <label class="small fw-bold mb-1">New Expiry Date</label>
                                                    <input type="date" name="new_end" class="form-control rounded-3" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0 p-4 pt-0">
                                            <button type="submit" name="renew_member" class="btn btn-success w-100 rounded-pill fw-bold">Confirm Renewal</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php if(empty($filtered)): ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">No expiry alerts found matching your criteria.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="alertToast"></div>

<script>
function sendEnglishReminder(name, days) {
    let msg = days < 0 
        ? `${name}'s membership expired ${Math.abs(days)} days ago. Please follow up for renewal.` 
        : `${name}'s membership will expire in ${days} days. Notify them for renewal!`;
    
    let toast = `<div class="toast show align-items-center text-bg-primary border-0 mb-2" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-bold"><i class="bi bi-info-circle me-2"></i> ${msg}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>`;
    document.getElementById('alertToast').innerHTML = toast;
    setTimeout(() => { document.getElementById('alertToast').innerHTML = ''; }, 5000);
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
