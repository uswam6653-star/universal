<?php 
require_once '../../includes/header.php'; 

$uid = $_SESSION['user_id'];

// Fetch attendance setting (JSON)
$stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'gym_attendance'");
$stmt->execute();
$res = $stmt->fetch();
$all_attendance = $res ? json_decode($res['setting_value'], true) : [];

// Filter for this user
$my_attendance = [];
foreach($all_attendance as $a) {
    if (isset($a['user_id']) && $a['user_id'] == $uid) {
        $my_attendance[] = $a;
    }
}

// Sort by date desc
usort($my_attendance, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

$total_present = 0;
foreach($my_attendance as $a) {
    if($a['status'] == 'Present') $total_present++;
}
?>

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-calendar-check text-success me-2"></i>My Attendance Record</h5>
            <p class="text-muted">Total Days Present: <strong><?= $total_present ?></strong></p>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($my_attendance as $a): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($a['date'])) ?></td>
                            <td>
                                <span class="badge <?= $a['status'] == 'Present' ? 'bg-success' : 'bg-danger' ?> rounded-pill">
                                    <?= htmlspecialchars($a['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($my_attendance)): ?>
                            <tr><td colspan="2" class="text-center py-4 text-muted">No attendance records found yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
