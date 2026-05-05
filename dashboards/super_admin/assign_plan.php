<?php 
require_once __DIR__ . '/../../includes/header.php'; 

// Fetch Plans from settings
$stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'gym_membership_plans'");
$stmt->execute();
$res = $stmt->fetch();
$plans = $res ? json_decode($res['setting_value'], true) : [];

// Fetch Gym Members
$members = $pdo->query("SELECT id, name, registration_no, is_active, identity_no FROM users WHERE role = 'student' ORDER BY name ASC")->fetchAll();

// Fetch Trainers
$trainers = $pdo->query("SELECT name FROM users WHERE role = 'trainer'")->fetchAll(PDO::FETCH_COLUMN);

// Metadata Helper
// Format: 0:phone, 1:gender, 2:dob, 3:join, 4:address, 5:type, 6:photo, 7:pay, 8:end_date, 9:perms, 10:trainer
function updateMemberPlanMetadata($current, $new_plan, $start_date, $end_date, $pay_status, $trainer) {
    $parts = array_pad(explode('|', $current), 11, '');
    
    $parts[3] = $start_date;
    $parts[5] = $new_plan;
    $parts[7] = $pay_status;
    $parts[8] = $end_date;
    $parts[10] = $trainer; 
    
    return implode('|', $parts);
}

// Handle Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_plan'])) {
    $user_id = $_POST['user_id'];
    $plan_name = $_POST['plan_name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $pay_status = $_POST['pay_status'];
    $trainer = $_POST['trainer'];

    // Get current user metadata
    $stmt = $pdo->prepare("SELECT identity_no FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $curr_meta = $stmt->fetchColumn() ?: '';

    $new_meta = updateMemberPlanMetadata($curr_meta, $plan_name, $start_date, $end_date, $pay_status, $trainer);

    // Update User
    $pdo->prepare("UPDATE users SET identity_no = ? WHERE id = ?")->execute([$new_meta, $user_id]);
    
    // --- NEW: Log Trainer Assignment if selected ---
    if (!empty($trainer)) {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'gym_trainer_assignments'");
        $stmt->execute();
        $res = $stmt->fetch();
        $assign_logs = $res ? json_decode($res['setting_value'], true) : [];
        if (!is_array($assign_logs)) $assign_logs = [];
        
        $new_log = [
            'id' => uniqid('TR_PLAN_'),
            'member_id' => $user_id,
            'member_name' => '', // Will be fetched via JOIN or loop if needed, but display logic handles it
            'trainer_id' => 0, // In this simple log, we just have the name from post
            'trainer_name' => $trainer,
            'type' => 'Membership Plan Support',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'status' => 'Active'
        ];
        
        // Find member name for log
        foreach($members as $m) { if($m['id'] == $user_id) { $new_log['member_name'] = $m['name']; break; } }
        
        array_unshift($assign_logs, $new_log);
        $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'gym_trainer_assignments'")->execute([json_encode($assign_logs)]);
    }
    // --- End Sync ---

    $success = "Membership Plan effectively assigned and details updated!";
    
    // Refresh member list to show updated data
    $members = $pdo->query("SELECT id, name, registration_no, is_active, identity_no FROM users WHERE role = 'student' ORDER BY name ASC")->fetchAll();
}
?>

<div class="row g-4">
    <!-- Assignment Form -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 p-4 pb-0">
                <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-person-check me-2"></i>Assign Plan to Member</h5>
            </div>
            <div class="card-body p-4">
                <?php if(isset($success)) echo "<div class='alert alert-success small py-2'>$success</div>"; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="small fw-bold mb-1">Select Member</label>
                        <select name="user_id" class="form-select rounded-3" required onchange="populateDefaults(this)">
                            <option value="">-- Choose Member --</option>
                            <?php foreach($members as $m): 
                                $m_meta = array_pad(explode('|', $m['identity_no']), 9, '');
                            ?>
                                <option value="<?= $m['id'] ?>" 
                                    data-plan="<?= htmlspecialchars($m_meta[5]) ?>"
                                    data-start="<?= htmlspecialchars($m_meta[3]) ?>"
                                    data-end="<?= htmlspecialchars($m_meta[8]) ?>"
                                    data-pay="<?= htmlspecialchars($m_meta[7]) ?>"
                                    data-trainer="<?= htmlspecialchars($m_meta[10] ?? '') ?>">
                                    <?= htmlspecialchars($m['name']) ?> (<?= $m['registration_no'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold mb-1">Select Membership Plan</label>
                        <select name="plan_name" id="plan_select" class="form-select rounded-3" required onchange="calculateEndDate()">
                            <option value="" data-days="0">-- Choose Plan --</option>
                            <?php foreach($plans as $p): ?>
                                <option value="<?= htmlspecialchars($p['name']) ?>" data-days="<?= htmlspecialchars($p['duration']) ?>">
                                    <?= htmlspecialchars($p['name']) ?> (<?= $p['duration'] ?> Days - Rs. <?= $p['price'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="small fw-bold mb-1">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required onchange="calculateEndDate()">
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold mb-1">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control rounded-3" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold mb-1">Payment Status</label>
                        <select name="pay_status" id="pay_status" class="form-select rounded-3">
                            <option>Paid</option>
                            <option>Unpaid</option>
                            <option>Pending</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="small fw-bold mb-1">Assign Trainer</label>
                        <select name="trainer" id="trainer" class="form-select rounded-3">
                            <option value="">None</option>
                            <?php foreach($trainers as $t): ?>
                                <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" name="assign_plan" class="btn btn-primary w-100 rounded-pill fw-bold">Assign & Update</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Assigned Members List -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 p-4">
                <h5 class="fw-bold mb-0">Current Assignments Overview</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small fw-bold text-uppercase sticky-top">
                            <tr>
                                <th class="ps-4">Member Name</th>
                                <th>Current Plan</th>
                                <th>Timeline</th>
                                <th>Payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($members as $m): 
                                $meta = array_pad(explode('|', $m['identity_no']), 9, '');
                                $plan = $meta[5] ?: 'No Plan';
                                $start = $meta[3] ?: '-';
                                $end = $meta[8] ?: '-';
                                $pay = $meta[7] ?: 'Unpaid';
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <strong><?= htmlspecialchars($m['name']) ?></strong><br>
                                    <small class="text-muted"><?= $m['registration_no'] ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-dark"><?= htmlspecialchars($plan) ?></span><br>
                                    <small class="text-muted"><i class="bi bi-person me-1"></i><?= htmlspecialchars(($meta[10] ?? '') ?: 'No Trainer') ?></small>
                                </td>
                                <td>
                                    <small class="d-block text-success">Start: <?= $start ?></small>
                                    <small class="d-block text-danger">End: <?= $end ?></small>
                                </td>
                                <td>
                                    <span class="badge <?= $pay == 'Paid' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= htmlspecialchars($pay) ?></span>
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

<script>
function populateDefaults(selectEl) {
    if (!selectEl.value) {
        document.getElementById('planForm').reset();
        return;
    }
    const opt = selectEl.options[selectEl.selectedIndex];
    
    // Auto-select plan if it matches
    const planSelect = document.getElementById('plan_select');
    for(let i=0; i<planSelect.options.length; i++) {
        if(planSelect.options[i].value === opt.dataset.plan) {
            planSelect.selectedIndex = i;
            break;
        }
    }
    
    document.getElementById('start_date').value = opt.dataset.start || new Date().toISOString().split('T')[0];
    document.getElementById('end_date').value = opt.dataset.end || '';
    
    const paySelect = document.getElementById('pay_status');
    for(let i=0; i<paySelect.options.length; i++) {
        if(paySelect.options[i].value === opt.dataset.pay) {
            paySelect.selectedIndex = i;
            break;
        }
    }
    
    const trainSelect = document.getElementById('trainer');
    for(let i=0; i<trainSelect.options.length; i++) {
        if(trainSelect.options[i].value === opt.dataset.trainer) {
            trainSelect.selectedIndex = i;
            break;
        }
    }
}

function calculateEndDate() {
    const start_date = document.getElementById('start_date').value;
    const planSelect = document.getElementById('plan_select');
    const selectedOpt = planSelect.options[planSelect.selectedIndex];
    
    if (start_date && selectedOpt.value) {
        const days = parseInt(selectedOpt.dataset.days);
        if (days > 0) {
            const result = new Date(start_date);
            result.setDate(result.getDate() + days);
            document.getElementById('end_date').value = result.toISOString().split('T')[0];
        }
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
