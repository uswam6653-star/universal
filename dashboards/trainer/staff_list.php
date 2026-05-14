<?php 
require_once __DIR__ . '/../../includes/header.php'; 

$uid = $_SESSION['user_id'];

// Fetch All Staff/Trainers excluding current user
$stmt = $pdo->prepare("SELECT id, name, role, email FROM users WHERE role IN ('trainer', 'hod', 'super_admin') AND id != ? ORDER BY name ASC");
$stmt->execute([$uid]);
$staff = $stmt->fetchAll();
?>

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white d-flex flex-row align-items-center">
            <div class="rounded-circle bg-info bg-opacity-10 p-3 me-4 text-info h3 mb-0">
                <i class="bi bi-person-badge"></i>
            </div>
            <div>
                <h3 class="fw-bold mb-0 text-dark">Gym Staff Directory</h3>
                <p class="text-muted mb-0">Connect with other trainers and administration staff.</p>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="row g-3">
            <?php foreach($staff as $s): 
                $roleLabel = [
                    'trainer' => 'Fitness Trainer',
                    'hod' => 'Senior Trainer',
                    'super_admin' => 'Administrator'
                ][$s['role']] ?? $s['role'];
                
                $roleColor = [
                    'trainer' => 'primary',
                    'hod' => 'success',
                    'super_admin' => 'dark'
                ][$s['role']] ?? 'secondary';
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 hover-shadow transition-all bg-white">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-md bg-<?= $roleColor ?> text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3" style="width: 50px; height: 50px;">
                            <?= strtoupper(substr($s['name'], 0, 1)) ?>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($s['name']) ?></h6>
                            <span class="badge bg-<?= $roleColor ?> bg-opacity-10 text-<?= $roleColor ?> x-small rounded-pill"><?= $roleLabel ?></span>
                        </div>
                    </div>
                    <div class="small text-muted mb-3">
                        <i class="bi bi-envelope me-1"></i> <?= $s['email'] ?><br>
                        <i class="bi bi-telephone me-1"></i> +92 300 1234567
                    </div>
                    <div class="d-grid">
                        <button class="btn btn-sm btn-outline-primary rounded-pill">Send Message</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
    .x-small { font-size: 0.7rem; }
    .hover-shadow:hover { box-shadow: 0 .5rem 1rem rgba(0,0,0,.08) !important; }
    .transition-all { transition: all 0.2s ease; }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
