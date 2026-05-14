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
                        <p class="text-muted">Select a member to mark their check-in or check-out.</p>
                    </div>

                    <div id="alert-container"></div>

                    <div class="p-4 bg-light rounded-4 mb-4">
                        <label class="small fw-bold text-uppercase text-muted mb-3 d-block">Search & Select Member</label>
                        <div class="input-group input-group-lg shadow-sm rounded-pill overflow-hidden bg-white">
                            <span class="input-group-text border-0 bg-white ps-4"><i class="bi bi-search"></i></span>
                            <select id="member-select" class="form-select border-0 py-3 select2-basic">
                                <option value="">-- Choose a Member --</option>
                                <?php foreach($members as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?> (<?= $m['registration_no'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
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

                    <div class="mt-5 border-top pt-4">
                        <h6 class="fw-bold text-muted mb-3">Quick Search Tips:</h6>
                        <ul class="small text-muted">
                            <li>You can search by Member Name or Registration ID.</li>
                            <li>System automatically prevents duplicate check-ins within 2 minutes.</li>
                            <li>Membership expiry is checked automatically during check-in.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Stats & Activity -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-4">
                <h5 class="fw-bold mb-4 d-flex justify-content-between align-items-center">
                    Today's Attendance Stats 📊
                    <span class="badge bg-danger rounded-pill pulse-badge x-small">LIVE</span>
                </h5>

                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div class="bg-success bg-opacity-10 p-3 rounded-4 text-center border border-success border-opacity-10">
                            <h2 class="fw-bold text-success mb-0" id="stat-in"><?= $total_in ?></h2>
                            <small class="text-success fw-bold x-small">IN SESSIONS</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-4 text-center border border-primary border-opacity-10">
                            <h2 class="fw-bold text-primary mb-0" id="stat-out"><?= $total_out ?></h2>
                            <small class="text-primary fw-bold x-small">COMPLETED</small>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mb-3">Recent Activity Feed</h6>
                <div class="activity-list overflow-auto" style="max-height: 500px;" id="activity-feed">
                    <?php foreach($recent as $r): ?>
                    <div class="activity-item d-flex align-items-center p-3 mb-2 bg-light rounded-3 border-start border-4 <?= $r['status'] == 'IN' ? 'border-success' : 'border-primary' ?>">
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-0 small"><?= htmlspecialchars($r['name']) ?></h6>
                            <small class="text-muted x-small"><?= $r['registration_no'] ?> • <?= date('h:i A', strtotime($r['created_at'])) ?></small>
                        </div>
                        <span class="badge rounded-pill x-small <?= $r['status'] == 'IN' ? 'bg-success' : 'bg-primary' ?>"><?= $r['status'] ?></span>
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
            showAlert('Please select a member first!', 'warning');
            return;
        }

        fetch('process_attendance.php', {
            method: 'POST',
            body: JSON.stringify({ user_id: userId, forced_status: type })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                showAlert(`${data.name}: ${data.msg}`, 'success');
                updateUI(data);
            } else {
                showAlert(data.msg, 'danger');
            }
        });
    }

    function showAlert(msg, type) {
        const container = document.getElementById('alert-container');
        container.innerHTML = `<div class="alert alert-${type} rounded-4 border-0 shadow-sm animate__animated animate__fadeInDown">${msg}</div>`;
        setTimeout(() => {
            const alert = container.querySelector('.alert');
            if(alert) {
                alert.classList.replace('animate__fadeInDown', 'animate__fadeOutUp');
                setTimeout(() => container.innerHTML = '', 500);
            }
        }, 3000);
    }

    function updateUI(data) {
        const feed = document.getElementById('activity-feed');
        const item = document.createElement('div');
        const color = data.status === 'IN' ? 'success' : 'primary';
        item.className = `activity-item d-flex align-items-center p-3 mb-2 bg-light rounded-3 border-start border-4 border-${color} animate__animated animate__slideInLeft`;
        item.innerHTML = `
            <div class="flex-grow-1">
                <h6 class="fw-bold mb-0 small">${data.name}</h6>
                <small class="text-muted x-small">${data.time}</small>
            </div>
            <span class="badge rounded-pill x-small bg-${color}">${data.status}</span>
        `;
        feed.prepend(item);
        
        // Update counters
        if(data.status === 'IN') {
            document.getElementById('stat-in').innerText = parseInt(document.getElementById('stat-in').innerText) + 1;
        } else {
            document.getElementById('stat-out').innerText = parseInt(document.getElementById('stat-out').innerText) + 1;
        }
    }
</script>

<style>
    .x-small { font-size: 0.7rem; }
    .pulse-badge { animation: pulse 2s infinite; }
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
