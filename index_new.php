<?php 
require_once 'includes/header.php'; 

$total = $pdo->query("SELECT COUNT(*) FROM complaints")->fetchColumn();
$pending = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'pending'")->fetchColumn();
$resolved = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'resolved'")->fetchColumn();

$recent = $pdo->query("SELECT c.*, u.name as st FROM complaints c JOIN users u ON c.user_id = u.id ORDER BY c.created_at DESC LIMIT 5")->fetchAll();
?>

<div class="row">
    <div class="col-lg-4 col-6">
        <div class="small-box bg-primary rounded-4 shadow-sm text-white">
            <div class="inner p-4"><h3><?= $total ?></h3><p>Total Complaints</p></div>
            <div class="icon"><i class="bi bi-chat-left-text"></i></div>
            <a href="dashboards/admin/manage_complaints_v3.php" class="small-box-footer rounded-bottom-4">View All <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-warning rounded-4 shadow-sm text-dark">
            <div class="inner p-4"><h3><?= $pending ?></h3><p>Pending Review</p></div>
            <div class="icon"><i class="bi bi-clock-history"></i></div>
            <a href="dashboards/admin/manage_complaints_v3.php?status=pending" class="small-box-footer rounded-bottom-4 text-dark">Take Action <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>
    <div class="col-lg-4 col-12">
        <div class="small-box bg-success rounded-4 shadow-sm text-white">
            <div class="inner p-4"><h3><?= $resolved ?></h3><p>Resolved Cases</p></div>
            <div class="icon"><i class="bi bi-check2-circle"></i></div>
            <a href="dashboards/admin/reports_v3.php" class="small-box-footer rounded-bottom-4">View Reports <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mt-4">
    <div class="card-header bg-transparent border-0 p-4"><h3 class="card-title fw-bold mb-0">Recent Activity</h3></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light"><tr><th class="ps-4">Student</th><th>Subject</th><th>Status</th><th>Time</th></tr></thead>
                <tbody>
                    <?php foreach($recent as $r): ?>
                    <tr>
                        <td class="ps-4"><strong><?= htmlspecialchars($r['st']) ?></strong></td>
                        <td><?= htmlspecialchars($r['subject']) ?></td>
                        <td><span class="badge border-0 rounded-pill px-3 <?= ['pending'=>'bg-warning','resolved'=>'bg-success'][$r['status']] ?? 'bg-info' ?>"><?= strtoupper($r['status']) ?></span></td>
                        <td><small class="text-muted"><?= date('H:i', strtotime($r['created_at'])) ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
