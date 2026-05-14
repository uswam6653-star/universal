<?php 
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/db.php';

// Fetch today's stats
$today = date('Y-m-d');
$stats = $pdo->query("SELECT status, count(*) as count FROM gym_attendance WHERE date = '$today' GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$total_in = $stats['IN'] ?? 0;
$total_out = $stats['OUT'] ?? 0;

$recent = $pdo->query("SELECT a.*, u.name, u.registration_no FROM gym_attendance a JOIN users u ON a.user_id = u.id WHERE a.date = '$today' ORDER BY a.created_at DESC LIMIT 10")->fetchAll();
$members = $pdo->query("SELECT id, name, registration_no FROM users WHERE role = 'student' ORDER BY name ASC")->fetchAll();

require_once __DIR__ . '/../../includes/header.php'; 
?>

<div class="container-fluid py-4">
    <div class="row g-4">
        <!-- Manual Attendance Entry -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-5">
                    <div class="text-center mb-5">
                        <div class="avatar-lg bg-primary bg-opacity-10 rounded-circle p-4 d-inline-block mb-3">
                            <i class="bi bi-person-check text-primary h1 mb-0"></i>
                        </div>
                        <h2 class="fw-bold text-dark">Manual Attendance Portal ✍️</h2>
                        <p class="text-muted">Scanner has been COMPLETELY REMOVED. Please select a member manually.</p>
                    </div>

                    <div id="alert-container"></div>

                    <div class="p-4 bg-light rounded-4 mb-4">
                        <label class="small fw-bold text-uppercase text-muted mb-3 d-block">Search & Select Member</label>
                        <select id="member-select" class="form-select form-select-lg rounded-pill border-0 shadow-sm px-4">
                            <option value="">-- Choose a Member --</option>
                            <?php foreach($members as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?> (<?= $m['registration_no'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <button onclick="markAttendance('IN')" class="btn btn-success w-100 py-3 rounded-4 fw-bold shadow-sm">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Mark Check-In
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button onclick="markAttendance('OUT')" class="btn btn-primary w-100 py-3 rounded-4 fw-bold shadow-sm">
                                <i class="bi bi-box-arrow-right me-2"></i> Mark Check-Out
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Stats & Activity -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-4">
                <h5 class="fw-bold mb-4">Live Activity Stats 📊</h5>
                <div class="row g-3 mb-4">
                    <div class="col-6 text-center">
                        <div class="bg-success bg-opacity-10 p-3 rounded-4">
                            <h2 class="fw-bold text-success mb-0" id="stat-in"><?= $total_in ?></h2>
                            <small class="fw-bold small">CHECK-INS</small>
                        </div>
                    </div>
                    <div class="col-6 text-center">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-4">
                            <h2 class="fw-bold text-primary mb-0" id="stat-out"><?= $total_out ?></h2>
                            <small class="fw-bold small">CHECK-OUTS</small>
                        </div>
                    </div>
                </div>

                <div class="activity-list overflow-auto" style="max-height: 400px;" id="activity-feed">
                    <?php foreach($recent as $r): ?>
                    <div class="p-3 mb-2 bg-light rounded-3 border-start border-4 <?= $r['status'] == 'IN' ? 'border-success' : 'border-primary' ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-0 small"><?= htmlspecialchars($r['name']) ?></h6>
                                <small class="text-muted" style="font-size: 10px;"><?= $r['registration_no'] ?> • <?= date('h:i A', strtotime($r['created_at'])) ?></small>
                            </div>
                            <span class="badge rounded-pill <?= $r['status'] == 'IN' ? 'bg-success' : 'bg-primary' ?>"><?= $r['status'] ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function markAttendance(type) {
        const userId = document.getElementById('member-select').value;
        if(!userId) {
            alert('Please select a member first!');
            return;
        }

        fetch('process_attendance.php', {
            method: 'POST',
            body: JSON.stringify({ user_id: userId, forced_status: type })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                alert(data.msg);
            }
        });
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
