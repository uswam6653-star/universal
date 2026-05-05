<?php 
require_once '../../includes/header.php'; 

$user_id = $_SESSION['user_id'];

// Get member metadata for plan and payment info
$stmt = $pdo->prepare("SELECT identity_no FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$meta = array_pad(explode('|', $stmt->fetchColumn() ?: ''), 11, '');

$plan_name = $meta[5] ?: 'No Plan';
$start_date = $meta[3] ?: 'N/A';
$end_date = $meta[8] ?: 'N/A';
$pay_status = $meta[7] ?: 'Unpaid';
$trainer = $meta[10] ?: 'None Assigned';

// Fetch all plans to show prices
$stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'gym_membership_plans'");
$stmt->execute();
$res = $stmt->fetch();
$all_plans = $res ? json_decode($res['setting_value'], true) : [];

$current_price = 0;
foreach($all_plans as $p) {
    if ($p['name'] == $plan_name) {
        $current_price = $p['price'];
        break;
    }
}
?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
            <div class="mb-3">
                <i class="bi bi-wallet2 display-4 text-primary"></i>
            </div>
            <h4 class="fw-bold mb-1">My Membership Fee</h4>
            <p class="text-muted small">Current Billing Status</p>
            
            <div class="mt-4 p-3 bg-light rounded-4">
                <h2 class="fw-bold mb-0 text-dark">Rs. <?= number_format($current_price) ?></h2>
                <span class="badge <?= $pay_status == 'Paid' ? 'bg-success' : 'bg-danger' ?> rounded-pill px-4 py-2 mt-2">
                    <?= $pay_status ?>
                </span>
            </div>
            
            <div class="mt-4 d-grid gap-2">
                <?php if($pay_status != 'Paid'): ?>
                    <button class="btn btn-primary rounded-pill py-3 fw-bold shadow-sm">
                        <i class="bi bi-credit-card me-1"></i> Pay with JazzCash/EasyPaisa
                    </button>
                <?php else: ?>
                    <button class="btn btn-outline-success rounded-pill py-3 fw-bold" disabled>
                        <i class="bi bi-check-circle me-1"></i> Payment Received
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
            <div class="card-header bg-transparent border-0 p-4 pb-0">
                <h5 class="fw-bold mb-0">Subscription Details</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="p-3 border rounded-4">
                            <label class="small text-muted d-block mb-1">Active Plan</label>
                            <h6 class="fw-bold mb-0"><?= htmlspecialchars($plan_name) ?></h6>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 border rounded-4">
                            <label class="small text-muted d-block mb-1">Assigned Trainer</label>
                            <h6 class="fw-bold mb-0"><?= htmlspecialchars($trainer) ?></h6>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 border rounded-4 bg-success bg-opacity-10 border-success-subtle">
                            <label class="small text-success d-block mb-1">Activation Date</label>
                            <h6 class="fw-bold mb-0"><?= $start_date ?></h6>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 border rounded-4 bg-danger bg-opacity-10 border-danger-subtle">
                            <label class="small text-danger d-block mb-1">Expiry Date</label>
                            <h6 class="fw-bold mb-0"><?= $end_date ?></h6>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-4 rounded-4 border-0 shadow-sm">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    Your membership is set to <?= (strtotime($end_date) < time()) ? 'expire soon' : 'renew automatically' ?>. Please contact the front desk for changes.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>