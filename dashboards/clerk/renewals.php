<?php 
require_once '../../includes/header.php'; 

// Fetch Members from the users table
$stmt = $pdo->query("SELECT id, name, registration_no, identity_no FROM users WHERE role = 'student'");
$users = $stmt->fetchAll();

$renewals = [];
$today = date('Y-m-d');
$warning_date = date('Y-m-d', strtotime('+10 days'));

foreach ($users as $u) {
    $meta = array_pad(explode('|', $u['identity_no']), 11, '');
    $plan_name = $meta[5] ?: 'No Plan';
    $expiry_date = $meta[8] ?: ''; // We assume index 8 stores the expiry date as set in assign_trainers.php or plan assignment

    if ($expiry_date) {
        if ($expiry_date < $warning_date) {
            $renewals[] = [
                'id' => $u['id'],
                'name' => $u['name'],
                'reg_no' => $u['registration_no'] ?: 'MEM-' . $u['id'],
                'plan' => $plan_name,
                'expiry_date' => $expiry_date,
                'is_expired' => ($expiry_date < $today)
            ];
        }
    }
}

// Sort by expiry date
usort($renewals, function($a, $b) { return strcmp($a['expiry_date'], $b['expiry_date']); });
?>

<div class="row g-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-transparent border-0 p-4">
                <h5 class="fw-bold mb-0 text-primary">Gym Membership Renewals</h5>
                <p class="text-muted small mb-0">Showing athletes whose plans are expiring soon or have already expired.</p>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small fw-bold">
                            <tr>
                                <th class="ps-4">Member Name</th>
                                <th>Current Plan</th>
                                <th>Expiry Date</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($renewals as $r): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold"><?= htmlspecialchars($r['name']) ?></div>
                                    <small class="text-muted"><?= $r['reg_no'] ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark border px-3"><?= htmlspecialchars($r['plan']) ?></span></td>
                                <td>
                                    <span class="badge <?= $r['is_expired'] ? 'bg-danger' : 'bg-warning text-dark' ?> rounded-pill px-3">
                                        <?= date('d M Y', strtotime($r['expiry_date'])) ?>
                                        <?= $r['is_expired'] ? ' (EXPIRED)' : '' ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="../super_admin/assign_plan.php?user_id=<?= $r['id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                                        <i class="bi bi-arrow-repeat me-1"></i> Renew Plan
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($renewals)): ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted">🎉 Great! All members have active plans.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
