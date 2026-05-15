<?php 
require_once '../../includes/header.php'; 

$uid = $_SESSION['user_id'];

// Fetch Latest Workout Plan
$stmt = $pdo->prepare("SELECT description, created_at FROM complaints WHERE user_id = ? AND subject = 'WORKOUT' ORDER BY id DESC");
$stmt->execute([$uid]);
$plans = $stmt->fetchAll();
?>

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-lightning-fill text-warning me-2"></i>My Workout & Diet Plans</h5>
            
            <?php foreach($plans as $plan): ?>
            <div class="p-4 rounded-4 bg-light border-start border-4 border-warning mb-3">
                <small class="text-muted d-block mb-2">Assigned on: <?= date('d M Y, h:i A', strtotime($plan['created_at'])) ?></small>
                <pre class="mb-0 text-wrap fs-6 fw-normal" style="font-family: inherit; line-height: 1.6;"><?= htmlspecialchars($plan['description']) ?></pre>
            </div>
            <?php endforeach; ?>

            <?php if(empty($plans)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-clipboard-x display-4 opacity-25 d-block mb-3"></i>
                    <p>No workout plans have been assigned to you yet. Please consult your trainer.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
