<?php 
require_once '../../core/db.php'; 
require_once '../../core/session.php';

$setting_key = 'gym_payments';

// 1. Fetch Current Data
$stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
$stmt->execute([$setting_key]);
$res = $stmt->fetch();
$payments = $res ? (json_decode($res['setting_value'], true) ?: []) : [];

// 2. Fetch Members & Plans for forms
$members = $pdo->query("SELECT id, name, registration_no FROM users WHERE role = 'student' ORDER BY name ASC")->fetchAll();
$plans_raw = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'gym_membership_plans'")->fetch();
$all_plans = $plans_raw ? json_decode($plans_raw['setting_value'], true) : [];

// 3. Handle Delete logic BEFORE header
if (isset($_GET['delete'])) {
    $del = $_GET['delete'];
    $payments = array_filter($payments, function($p) use ($del) { return ($p['id'] ?? '') !== $del; });
    $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?")->execute([json_encode(array_values($payments)), $setting_key]);
    header("Location: gym_payments.php?msg=Payment deleted");
    exit;
}

// 4. Handle Save/Edit logic BEFORE header
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payment'])) {
    $id = !empty($_POST['edit_id']) ? $_POST['edit_id'] : ('INV-' . strtoupper(substr(uniqid(), -6)));
    $mem_name = '';
    foreach($members as $m) {
        if ($m['id'] == $_POST['member_id']) {
            $mem_name = $m['name'];
            break;
        }
    }

    $new_record = [
        'id' => $id,
        'member_id' => $_POST['member_id'],
        'member_name' => $_POST['member_name'] ?? $mem_name, // Use provided member_name or fetched one
        'plan_name' => $_POST['plan_name'],
        'amount' => $_POST['amount'],
        'payment_date' => $_POST['payment_date'],
        'method' => $_POST['method'],
        'status' => $_POST['status']
    ];

    if (!empty($_POST['edit_id'])) {
        foreach ($payments as $k => $v) {
            if (($v['id'] ?? '') === $id) { $payments[$k] = $new_record; break; }
        }
        $success_msg = "Payment updated!";
    } else {
        array_unshift($payments, $new_record);
        $success_msg = "Payment added successfully!";
    }

    $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?")->execute([json_encode($payments), $setting_key]);

    // --- LINKING: Log activity for Member Dashboard ---
    $log_details = "Invoice: $id | Plan: {$_POST['plan_name']} | Amount: Rs. {$_POST['amount']} | Status: {$_POST['status']}";
    $pdo->prepare("INSERT INTO sys_activity_logs (user_id, action, details, created_at) VALUES (?, 'PAYMENT', ?, ?)")
        ->execute([$_POST['member_id'], $log_details, $_POST['payment_date']]);

    // --- LINKING: Update User Plan Metadata ---
    $uStmt = $pdo->prepare("SELECT identity_no FROM users WHERE id = ?");
    $uStmt->execute([$_POST['member_id']]);
    $uMeta = explode('|', $uStmt->fetchColumn() ?? '');
    $uMeta = array_pad($uMeta, 11, '');
    $uMeta[0] = $_POST['plan_name']; // Update Plan Name at index 0
    $newMeta = implode('|', $uMeta);
    $pdo->prepare("UPDATE users SET identity_no = ? WHERE id = ?")->execute([$newMeta, $_POST['member_id']]);
}

require_once __DIR__ . '/../../includes/header.php'; 

// Filters
$f_member = $_GET['f_member'] ?? '';
$f_status = $_GET['f_status'] ?? '';
$f_date = $_GET['f_date'] ?? '';

$filtered = $payments;
if ($f_member !== '') $filtered = array_filter($filtered, fn($p) => stripos($p['member_name'] ?? '', $f_member) !== false);
if ($f_status !== '') $filtered = array_filter($filtered, fn($p) => ($p['status'] ?? '') === $f_status);
if ($f_date !== '') $filtered = array_filter($filtered, fn($p) => ($p['payment_date'] ?? '') === $f_date);

// Pending List
$paid_ids = array_column(array_filter($payments, fn($p) => ($p['status'] ?? '') === 'Paid'), 'member_id');
$pending_members = array_filter($members, fn($m) => !in_array($m['id'], $paid_ids));


?>

<div class="row g-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-transparent border-0 p-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Payments & Billing</h5>
                <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#payModal" onclick="resetForm()">
                    <i class="bi bi-cash-coin me-1"></i> Add Payment
                </button>
            </div>
            
            <div class="card-body px-4 pt-0">
                <?php if(isset($success_msg)) echo "<div class='alert alert-success small py-2'>$success_msg</div>"; ?>
                <?php if(isset($_GET['msg'])) echo "<div class='alert alert-info small py-2'>".htmlspecialchars($_GET['msg'])."</div>"; ?>

                <!-- Summary Cards -->
                <?php
                $paid_recs = array_filter($payments, fn($p) => ($p['status'] ?? '') === 'Paid');
                $pending_recs = array_filter($payments, fn($p) => ($p['status'] ?? '') === 'Pending');
                
                $total_paid = array_sum(array_map(fn($p) => (float)($p['amount'] ?? 0), $paid_recs));
                $total_pending = array_sum(array_map(fn($p) => (float)($p['amount'] ?? 0), $pending_recs));
                
                $count_paid = count($paid_recs);
                $count_pending = count($pending_recs);
                ?>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card bg-success text-white border-0 rounded-4">
                            <div class="card-body">
                                <p class="small mb-1 opacity-75">Total Collected</p>
                                <h4 class="fw-bold mb-0">Rs. <?= number_format($total_paid) ?></h4>
                                <small><?= $count_paid ?> payments</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white border-0 rounded-4">
                            <div class="card-body">
                                <p class="small mb-1 opacity-75">Pending Amount</p>
                                <h4 class="fw-bold mb-0">Rs. <?= number_format($total_pending) ?></h4>
                                <small><?= $count_pending ?> pending</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-primary text-white border-0 rounded-4">
                            <div class="card-body">
                                <p class="small mb-1 opacity-75">Total Payments</p>
                                <h4 class="fw-bold mb-0"><?= count($payments) ?></h4>
                                <small>All time</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white border-0 rounded-4">
                            <div class="card-body">
                                <p class="small mb-1 opacity-75">Members Not Paid</p>
                                <h4 class="fw-bold mb-0"><?= count($pending_members) ?></h4>
                                <small>No payment recorded</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <form method="GET" class="row g-2 mb-4 bg-light p-3 rounded-4">
                    <div class="col-md-4">
                        <label class="small text-muted mb-1">Search Member</label>
                        <input type="text" name="f_member" class="form-control form-control-sm" placeholder="Member Name" value="<?= htmlspecialchars($f_member) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted mb-1">Payment Status</label>
                        <select name="f_status" class="form-select form-select-sm">
                            <option value="">All Status</option>
                            <option value="Paid" <?= $f_status=='Paid'?'selected':'' ?>>Paid</option>
                            <option value="Pending" <?= $f_status=='Pending'?'selected':'' ?>>Pending</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted mb-1">Payment Date</label>
                        <input type="date" name="f_date" class="form-control form-control-sm" value="<?= htmlspecialchars($f_date) ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-sm btn-dark w-100"><i class="bi bi-search me-1"></i> Filter</button>
                    </div>
                    <div class="col-12 mt-2"><a href="gym_payments.php" class="small text-danger text-decoration-none">Clear Filters</a></div>
                </form>

                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item"><button class="nav-link active fw-bold" data-bs-toggle="tab" data-bs-target="#hist">Payment History</button></li>
                    <li class="nav-item"><button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#pending">Pending List <span class="badge bg-danger ms-1"><?= count($pending_members) ?></span></button></li>
                </ul>

                <div class="tab-content">
                    <!-- Payment History Tab -->
                    <div class="tab-pane fade show active" id="hist">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light small fw-bold text-uppercase">
                                    <tr>
                                        <th class="ps-4">Invoice ID</th>
                                        <th>Member</th>
                                        <th>Plan</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($filtered as $p): ?>
                                    <tr>
                                        <td class="ps-4"><code><?= htmlspecialchars($p['id'] ?? 'N/A') ?></code></td>
                                        <td><strong><?= htmlspecialchars($p['member_name'] ?? 'Unknown') ?></strong></td>
                                        <td><?= htmlspecialchars($p['plan_name'] ?? 'Standard') ?></td>
                                        <td class="fw-bold text-success">Rs. <?= number_format((float)($p['amount'] ?? 0)) ?></td>
                                        <td><?= !empty($p['payment_date']) ? date('d M Y', strtotime($p['payment_date'])) : 'N/A' ?></td>
                                        <td><span class="badge <?= $p['method'] === 'Not Paid' ? 'bg-danger' : 'bg-secondary' ?>"><?= htmlspecialchars($p['method'] ?? 'Cash') ?></span></td>
                                        <td><span class="badge <?= ($p['status'] ?? '') =='Paid' ? 'bg-success' : 'bg-warning' ?>"><?= htmlspecialchars($p['status'] ?? 'Pending') ?></span></td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#inv<?= $p['id'] ?? '' ?>"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#payModal" onclick='editPay(<?= json_encode($p) ?>)'><i class="bi bi-pencil"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary" onclick="window.open('print_receipt.php?id=<?= $p['id'] ?? '' ?>', '_blank')"><i class="bi bi-printer"></i></button>
                                                <a href="?delete=<?= $p['id'] ?? '' ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete payment?')"><i class="bi bi-trash"></i></a>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Invoice Modal -->
                                    <div class="modal fade" id="inv<?= $p['id'] ?? '' ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content border-0 rounded-4" id="invPrint<?= $p['id'] ?? '' ?>">
                                                <div class="modal-header border-0 p-4 pb-2 bg-primary text-white rounded-top-4">
                                                    <div>
                                                        <h5 class="fw-bold mb-0">🏋️ Gym Management ERP</h5>
                                                        <small class="opacity-75">Payment Invoice</small>
                                                    </div>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="row g-2 mb-3">
                                                        <div class="col-6"><small class="text-muted">Invoice ID</small><p class="fw-bold mb-0"><code><?= $p['id'] ?? 'N/A' ?></code></p></div>
                                                        <div class="col-6 text-end"><small class="text-muted">Date</small><p class="fw-bold mb-0"><?= !empty($p['payment_date']) ? date('d M Y', strtotime($p['payment_date'])) : 'N/A' ?></p></div>
                                                    </div>
                                                    <hr>
                                                    <div class="row g-2 mb-3">
                                                        <div class="col-6"><small class="text-muted">Member Name</small><p class="fw-bold mb-0"><?= htmlspecialchars($p['member_name'] ?? 'Unknown') ?></p></div>
                                                        <div class="col-6"><small class="text-muted">Membership Plan</small><p class="fw-bold mb-0"><?= htmlspecialchars($p['plan_name'] ?? 'Standard') ?></p></div>
                                                    </div>
                                                    <hr>
                                                    <div class="row g-2">
                                                        <div class="col-4"><small class="text-muted">Amount</small><h4 class="text-success fw-bold mb-0">Rs. <?= number_format((float)($p['amount'] ?? 0)) ?></h4></div>
                                                        <div class="col-4"><small class="text-muted">Method</small><p class="mb-0"><span class="badge <?= $p['method'] === 'Not Paid' ? 'bg-danger' : 'bg-dark' ?>"><?= htmlspecialchars($p['method'] ?? 'Cash') ?></span></p></div>
                                                        <div class="col-4"><small class="text-muted">Status</small><p class="mb-0"><span class="badge <?= ($p['status'] ?? '') =='Paid' ? 'bg-success' : 'bg-warning' ?>"><?= htmlspecialchars($p['status'] ?? 'Pending') ?></span></p></div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-0 p-4 pt-0">
                                                    <button onclick="window.open('print_receipt.php?id=<?= $p['id'] ?? '' ?>', '_blank')" class="btn btn-primary w-100 rounded-pill"><i class="bi bi-printer me-2"></i>Print Invoice</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                    <?php if(empty($filtered)): ?>
                                        <tr><td colspan="8" class="text-center text-muted py-4">No payment records found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pending Tab -->
                    <div class="tab-pane fade" id="pending">
                        <div class="alert alert-warning small mb-3">Yeh woh members hain jinki database mein koi bhi <strong>Paid</strong> payment record nahi hai.</div>
                        <table class="table table-bordered align-middle">
                            <thead class="bg-danger text-white">
                                <tr><th>Member Name</th><th>Registration No</th><th>Action</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($pending_members as $m): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
                                    <td><code><?= htmlspecialchars($m['registration_no']) ?></code></td>
                                    <td>
                                        <button class="btn btn-sm btn-success rounded-pill" data-bs-toggle="modal" data-bs-target="#payModal" onclick="quickPay('<?= $m['id'] ?>', '<?= addslashes($m['name']) ?>')">
                                            <i class="bi bi-cash-coin me-1"></i> Record Payment
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($pending_members)): ?>
                                    <tr><td colspan="3" class="text-center text-muted py-3">All members have payment records! ✅</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="payModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="payForm" class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-bold mb-0" id="mTitle">Add Payment</h5>
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
                    <label class="small fw-bold mb-1">Membership Plan</label>
                    <select name="plan_name" id="f_plan" class="form-select rounded-3">
                        <option value="Walk-in">Walk-in / No Plan</option>
                        <?php foreach($all_plans as $pl): ?>
                            <option value="<?= htmlspecialchars($pl['name']) ?>"><?= htmlspecialchars($pl['name']) ?> (Rs. <?= htmlspecialchars($pl['price']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="small fw-bold mb-1">Amount (Rs.)</label>
                    <input type="number" name="amount" id="f_amount" class="form-control rounded-3" placeholder="e.g. 5000" required min="0">
                </div>

                <div class="mb-3">
                    <label class="small fw-bold mb-1">Payment Date</label>
                    <input type="date" name="payment_date" id="f_date_inp" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="small fw-bold mb-1">Payment Method</label>
                        <select name="method" id="f_method" class="form-select rounded-3">
                            <option>Cash</option>
                            <option>Card</option>
                            <option>Online Transfer</option>
                            <option>Not Paid</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold mb-1">Status</label>
                        <select name="status" id="f_status" class="form-select rounded-3">
                            <option value="Paid">Paid</option>
                            <option value="Pending">Pending</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="submit" name="save_payment" class="btn btn-primary w-100 rounded-pill fw-bold">Save Payment</button>
            </div>
        </form>
    </div>
</div>

<!-- Print logic removed for separate page -->

<script>
function resetForm() {
    document.getElementById('payForm').reset();
    document.getElementById('edit_id').value = '';
    document.getElementById('mTitle').innerText = 'Add Payment';
    document.getElementById('f_date_inp').value = '<?= date('Y-m-d') ?>';
}

function editPay(data) {
    document.getElementById('mTitle').innerText = 'Edit Payment';
    document.getElementById('edit_id').value = data.id;
    document.getElementById('f_member').value = data.member_id;
    document.getElementById('f_plan').value = data.plan_name;
    document.getElementById('f_amount').value = data.amount;
    document.getElementById('f_date_inp').value = data.payment_date;
    document.getElementById('f_method').value = data.method;
    document.getElementById('f_status').value = data.status;
}

function quickPay(memberId, memberName) {
    resetForm();
    document.getElementById('f_member').value = memberId;
}

function quickPay(memberId, memberName) {
    resetForm();
    document.getElementById('f_member').value = memberId;
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
