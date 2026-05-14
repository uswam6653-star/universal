<?php 
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/db.php';

$uid = $_SESSION['user_id'];
$uname = $_SESSION['name'];

// Handle Progress Log
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_progress'])) {
    $mid = $_POST['member_id'];
    $weight = $_POST['weight'];
    $chest = $_POST['chest'] ?: 'N/A';
    $waist = $_POST['waist'] ?: 'N/A';
    $notes = $_POST['notes'];
    
    $details = "Weight: {$weight}kg | Chest: {$chest}in | Waist: {$waist}in | Notes: {$notes} (Coach: {$uname})";
    
    $stmt = $pdo->prepare("INSERT INTO sys_activity_logs (user_id, action, details) VALUES (?, 'PROGRESS', ?)");
    $stmt->execute([$mid, $details]);
    header("Location: member_progress.php?user_id=$mid&msg=Assessment saved");
    exit();
}

// Fetch All Students for Demo
$stmt = $pdo->prepare("SELECT id, name, registration_no FROM users WHERE role = 'student' ORDER BY name ASC");
$stmt->execute();
$assignedMembers = $stmt->fetchAll();

// Fetch Data for Chart
$selected_mid = $_GET['user_id'] ?? ($assignedMembers[0]['id'] ?? 0);
$chartData = [];
if ($selected_mid) {
    $stmt = $pdo->prepare("SELECT created_at, details FROM sys_activity_logs WHERE user_id = ? AND action = 'PROGRESS' ORDER BY created_at ASC LIMIT 10");
    $stmt->execute([$selected_mid]);
    $logsForChart = $stmt->fetchAll();
    foreach($logsForChart as $row) {
        preg_match('/Weight: ([\d.]+)kg/', $row['details'], $m);
        if (isset($m[1])) {
            $chartData[] = ['date' => date('d M', strtotime($row['created_at'])), 'weight' => (float)$m[1]];
        }
    }
}

require_once __DIR__ . '/../../includes/header.php'; 
?>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
    .card-premium { background: #ffffff; border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .btn-gradient { 
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); 
        color: white; border: none; transition: transform 0.2s, box-shadow 0.2s;
    }
    .btn-gradient:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3); color: white; }
    .glass-table { 
        background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); 
        border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 20px; 
    }
    .input-icon-group { position: relative; }
    .input-icon-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
    .input-icon-group .form-control { padding-left: 45px; border-radius: 12px; border: 1px solid #e2e8f0; background: #f8fafc; }
    .status-badge { font-size: 0.75rem; font-weight: 700; padding: 5px 12px; border-radius: 50px; }
    .table thead th { border: none; font-size: 0.8rem; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em; }
    .table tbody tr { transition: background 0.2s; border-bottom: 1px solid rgba(0,0,0,0.03); }
    .table tbody tr:hover { background: rgba(99, 102, 241, 0.02); }
</style>

<div class="row g-4 mb-5">
    <!-- Left Column: Assessment Entry (40%) -->
    <div class="col-lg-4">
        <div class="card card-premium p-4">
            <h4 class="fw-bold mb-1">Progress Tracking</h4>
            <p class="text-muted small mb-4">Track physical metrics for your client.</p>
            
            <?php if(isset($_GET['msg'])) echo "<div class='alert alert-success rounded-3 small border-0 shadow-sm'>{$_GET['msg']}</div>"; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="small fw-bold mb-1">Select Client</label>
                    <select name="member_id" class="form-select border shadow-sm rounded-3" required onchange="window.location.href='?user_id='+this.value" style="cursor:pointer; background-color: #ffffff;">
                        <option value="">-- Choose Member --</option>
                        <?php foreach($assignedMembers as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= $selected_mid == $m['id'] ? 'selected' : '' ?>><?= $m['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="small fw-bold mb-1">Current Weight (kg)</label>
                    <div class="input-icon-group">
                        <i class="bi bi-speedometer2"></i>
                        <input type="number" step="0.1" name="weight" class="form-control" placeholder="0.0" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="small fw-bold mb-1">Chest (in)</label>
                        <div class="input-icon-group">
                            <i class="bi bi-rulers"></i>
                            <input type="number" step="0.1" name="chest" class="form-control" placeholder="0.0">
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="small fw-bold mb-1">Waist (in)</label>
                        <div class="input-icon-group">
                            <i class="bi bi-heptagon"></i>
                            <input type="number" step="0.1" name="waist" class="form-control" placeholder="0.0">
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="small fw-bold mb-1">Observation Notes</label>
                    <textarea name="notes" class="form-control rounded-3 border-0 bg-light" rows="3" placeholder="Any comments..."></textarea>
                </div>

                <button type="submit" name="log_progress" class="btn btn-gradient w-100 py-3 fw-bold rounded-pill shadow">Save Metrics</button>
            </form>
        </div>
    </div>

    <!-- Right Column: Progress Analytics (60%) -->
    <div class="col-lg-8">
        <div class="row g-4">
            <!-- Chart Section -->
            <div class="col-12">
                <div class="card card-premium p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0">Progress Analytics</h5>
                        <div class="badge bg-primary bg-opacity-10 text-primary px-3 rounded-pill py-2">Weight vs Date</div>
                    </div>
                    <canvas id="weightChart" height="120"></canvas>
                </div>
            </div>

            <!-- History Table Section -->
            <div class="col-12">
                <div class="glass-table p-4 overflow-hidden">
                    <h5 class="fw-bold mb-4">Progress History</h5>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Assessment Date</th>
                                    <th>Client Name</th>
                                    <th>Key Metrics</th>
                                    <th class="text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Fetch Global Progress Logs for Demo
                                $stmt = $pdo->prepare("
                                    SELECT a.created_at, u.name, u.registration_no, a.details
                                    FROM sys_activity_logs a
                                    JOIN users u ON a.user_id = u.id
                                    WHERE a.action = 'PROGRESS'
                                    ORDER BY a.created_at DESC LIMIT 6
                                ");
                                $stmt->execute();
                                $logs = $stmt->fetchAll();
                                
                                foreach($logs as $l): ?>
                                <tr>
                                    <td class="small fw-bold text-dark"><?= date('d M Y', strtotime($l['created_at'])) ?></td>
                                    <td>
                                        <div class="fw-bold small"><?= htmlspecialchars($l['name']) ?></div>
                                        <div class="text-muted x-small"><?= $l['registration_no'] ?></div>
                                    </td>
                                    <td>
                                        <div class="small text-muted text-truncate" style="max-width: 200px;"><?= htmlspecialchars($l['details']) ?></div>
                                    </td>
                                    <td class="text-end">
                                        <span class="status-badge bg-success bg-opacity-10 text-success">Improving</span>
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
</div>

<script>
const ctx = document.getElementById('weightChart').getContext('2d');
const gradient = ctx.createLinearGradient(0, 0, 0, 400);
gradient.addColorStop(0, 'rgba(99, 102, 241, 0.3)');
gradient.addColorStop(1, 'rgba(99, 102, 241, 0)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($chartData, 'date')) ?>,
        datasets: [{
            label: 'Member Weight (kg)',
            data: <?= json_encode(array_column($chartData, 'weight')) ?>,
            borderColor: '#6366f1',
            backgroundColor: gradient,
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#6366f1',
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { 
            y: { grid: { color: 'rgba(0,0,0,0.03)' }, beginAtZero: false },
            x: { grid: { display: false } }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
