<?php 
require_once __DIR__ . '/../../includes/header.php'; 

// Fetch Stats for Today
$today = date('Y-m-d');
$stats = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN check_in IS NOT NULL THEN 1 END) as total_present,
        COUNT(CASE WHEN check_out IS NOT NULL THEN 1 END) as total_checkouts,
        COUNT(CASE WHEN status = 'Late' THEN 1 END) as total_late
    FROM gym_attendance 
    WHERE date = ?
");
$stats->execute([$today]);
$s = $stats->fetch();

// Recent Logs (Last 15)
$logs = $pdo->prepare("
    SELECT a.*, u.name, u.role, u.avatar, u.registration_no 
    FROM gym_attendance a
    JOIN users u ON a.user_id = u.id
    WHERE a.date = ?
    ORDER BY a.created_at DESC LIMIT 15
");
$logs->execute([$today]);
$recent_logs = $logs->fetchAll();
?>

<div class="row g-4 mb-4">
    <div class="col-md-8">
        <!-- Attendance Scanner Module -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
            <div class="card-header bg-dark p-4 border-0 text-white d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0">Biometric & QR Attendance Terminal</h5>
                    <small class="opacity-50">Authorized entries only</small>
                </div>
                <div class="nav nav-pills" id="scanner-tabs">
                    <button class="btn btn-sm btn-outline-light rounded-pill px-3 active me-2" data-bs-toggle="pill" data-bs-target="#qr-scanner" onclick="stopScanner(); resetUI();"><i class="bi bi-qr-code-scan me-1"></i> QR Mode</button>
                    <button class="btn btn-sm btn-outline-light rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#fingerprint-scanner" onclick="stopScanner(); resetUI();"><i class="bi bi-fingerprint me-1"></i> Biometric Mode</button>
                </div>
            </div>
            <div class="card-body p-5 bg-light d-flex flex-column align-items-center">
                <div class="tab-content w-100 text-center" id="scannerContent">
                    <!-- QR Scanner Tab -->
                    <div class="tab-pane fade show active" id="qr-scanner">
                        <div id="reader-container" class="mx-auto position-relative border border-2 border-primary rounded-4 shadow-sm mb-4" style="width: 350px; height: 350px; overflow: hidden; background: #000;">
                            <div id="qr-reader" style="width: 100%; height: 100%;"></div>
                            <div id="scan-overlay" class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-white" style="background: rgba(0,0,0,0.4); pointer-events: none;">
                                <i class="bi bi-camera fs-1 mb-2"></i>
                                <span class="small fw-bold">Camera Ready</span>
                            </div>
                        </div>
                        <button id="start-btn" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm" onclick="startScanner()"><i class="bi bi-camera-video me-2"></i> Open Scanner</button>
                    </div>

                    <!-- Fingerprint Scanner Tab -->
                    <div class="tab-pane fade" id="fingerprint-scanner">
                        <div class="biometric-outer mx-auto d-flex align-items-center justify-content-center position-relative mb-4" style="width: 250px; height: 250px;">
                            <!-- Animated Fingerprint UI -->
                            <div class="scan-pulse position-absolute w-100 h-100 rounded-circle bg-primary opacity-25"></div>
                            <button id="fingerprint-btn" class="btn btn-outline-primary rounded-circle border-4 p-5 shadow-lg position-relative z-1 hover-scale" onclick="simulateFingerprint()">
                                <i class="bi bi-fingerprint" style="font-size: 80px;"></i>
                            </button>
                            <!-- Simulated Laser Scale Card -->
                            <div class="scanner-laser"></div>
                        </div>
                        <p class="text-muted fw-bold">Place your finger on the sensor</p>
                        <div class="input-group input-group-sm mx-auto" style="max-width: 200px;">
                            <input type="text" id="manual_id" class="form-control rounded-start-pill text-center" placeholder="Or Enter ID/Email">
                            <button class="btn btn-dark rounded-end-pill px-3" onclick="processAttendance(document.getElementById('manual_id').value, 'Fingerprint')">GO</button>
                        </div>
                    </div>
                </div>

                <!-- Result Card (Populates After Scan) -->
                <div id="scan-result" class="mt-4 p-4 rounded-4 w-100 d-none text-start border shadow-sm bg-white" style="max-width: 500px;">
                    <div class="d-flex align-items-center">
                        <img id="res-avatar" src="" class="rounded-circle border me-3 shadow-sm" width="70" height="70" style="object-fit: cover;">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between">
                                <h5 id="res-name" class="fw-bold mb-0 text-primary"></h5>
                                <span id="res-action" class="badge rounded-pill px-3"></span>
                            </div>
                            <div class="small fw-bold" id="res-role"></div>
                            <div class="small text-muted mt-1" id="res-time"></div>
                        </div>
                    </div>
                    <div id="res-msg" class="alert mt-3 mb-0 small py-2 fw-bold text-center"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats & Quick Information -->
    <div class="col-md-4">
        <div class="row g-4">
            <div class="col-12 text-center">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center glass-card bg-primary text-white">
                    <h5 class="fw-bold mb-3 small opacity-75">Live Status</h5>
                    <div class="d-flex justify-content-around">
                        <div>
                            <h3 class="fw-bold mb-0"><?= $s['total_present'] ?></h3>
                            <small class="opacity-50">In</small>
                        </div>
                        <div class="border-start border-white border-opacity-25 ps-3">
                            <h3 class="fw-bold mb-0"><?= $s['total_checkouts'] ?></h3>
                            <small class="opacity-50">Out</small>
                        </div>
                        <div class="border-start border-white border-opacity-25 ps-3">
                            <h3 class="fw-bold mb-0"><?= $s['total_late'] ?></h3>
                            <small class="opacity-50">Late</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                    <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Check-ins</h6>
                    <div class="activity-feed">
                        <?php foreach($recent_logs as $l): ?>
                        <div class="d-flex align-items-center mb-3 pb-3 border-bottom border-light">
                            <img src="<?= !empty($l['avatar']) ? BASE_URL.$l['avatar'] : BASE_URL.'assets/img/avatar.png' ?>" class="rounded-circle me-2 border" width="35" height="35" style="object-fit: cover;">
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="small fw-bold text-truncate"><?= htmlspecialchars($l['name']) ?></div>
                                <div class="text-muted d-flex justify-content-between" style="font-size: 10px;">
                                    <span><?= strtoupper($l['method']) ?> · <?= htmlspecialchars($l['role']) ?></span>
                                    <span class="text-success"><?= date('h:i A', strtotime($l['check_in'])) ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if(empty($recent_logs)): ?>
                            <div class="text-center text-muted py-4 small">No entries recorded yet today.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Logs Section -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="card-header bg-white p-4 border-0">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">Monthly Attendance Report</h5>
            <div class="d-flex gap-2">
                <input type="month" class="form-control form-control-sm rounded-pill px-3" value="<?= date('Y-m') ?>">
                <button class="btn btn-sm btn-dark rounded-pill px-3">Export</button>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 text-center">
            <thead class="bg-light small fw-bold text-uppercase">
                <tr>
                    <th class="ps-4 text-start">Member/Staff Name</th>
                    <th>Designation</th>
                    <th>Total Presents</th>
                    <th>Working Hours</th>
                    <th>Monthly Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Grouped Monthly stats
                $month = date('Y-m');
                $mStats = $pdo->prepare("
                    SELECT u.name, u.role, u.avatar, 
                    COUNT(a.id) as presents,
                    SUM(TIMESTAMPDIFF(HOUR, a.check_in, a.check_out)) as total_hours
                    FROM users u
                    LEFT JOIN gym_attendance a ON u.id = a.user_id AND a.date LIKE ?
                    WHERE u.role != 'super_admin'
                    GROUP BY u.id
                    ORDER BY presents DESC
                ");
                $mStats->execute([$month.'%']);
                $rows = $mStats->fetchAll();
                foreach($rows as $r):
                    $pRate = ($r['presents'] > 0) ? round(($r['presents'] / date('d')) * 100) : 0;
                ?>
                <tr>
                    <td class="ps-4 text-start">
                        <div class="fw-bold small"><?= htmlspecialchars($r['name']) ?></div>
                        <small class="text-muted" style="font-size: 10px;"><?= htmlspecialchars($r['role']) ?></small>
                    </td>
                    <td><span class="badge bg-light text-dark border small fw-normal"><?= ucfirst($r['role']) ?></span></td>
                    <td class="fw-bold text-primary"><?= $r['presents'] ?> <small class="text-muted">days</small></td>
                    <td><?= $r['total_hours'] ?: '0' ?> hrs</td>
                    <td>
                        <div class="progress rounded-pill bg-light" style="height: 6px;">
                            <div class="progress-bar <?= $pRate > 75 ? 'bg-success' : 'bg-warning' ?>" style="width: <?= $pRate ?>%"></div>
                        </div>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-dark border-0 rounded-circle"><i class="bi bi-chevron-right"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Styles for Biometric Effects -->
<style>
.hover-scale:active { transform: scale(0.95); transition: 0.1s; }
.scan-pulse { 
    animation: scanner-pulse 1.5s infinite; 
}
@keyframes scanner-pulse {
    0% { transform: scale(1); opacity: 0.6; }
    100% { transform: scale(1.6); opacity: 0; }
}
.scanner-laser {
    position: absolute; width: 100%; height: 2px;
    background: rgba(var(--bs-primary-rgb), 0.5);
    box-shadow: 0 0 15px var(--bs-primary);
    top: 50%; left: 0; z-index: 2;
    animation: laser-move 2.5s infinite linear;
    pointer-events: none;
}
@keyframes laser-move {
    0% { top: 0%; }
    50% { top: 100%; }
    100% { top: 0%; }
}
</style>

<!-- Scanner Logic Scripts -->
<script src="<?= BASE_URL ?>assets/js/html5-qrcode.min.js"></script>
<script>
let html5QrScanner = null;

function startScanner() {
    document.getElementById('scan-overlay').classList.add('d-none');
    document.getElementById('start-btn').classList.add('d-none');
    
    html5QrScanner = new Html5Qrcode("qr-reader");
    const config = { fps: 10, qrbox: { width: 250, height: 250 } };

    html5QrScanner.start(
        { facingMode: "environment" }, 
        config, 
        (decodedText) => {
            // Stop after successful scan
            html5QrScanner.stop().then(() => {
                processAttendance(decodedText, 'QR');
                document.getElementById('start-btn').classList.remove('d-none');
            });
        },
        (errorMessage) => { /* Ignore errors */ }
    ).catch(err => {
        alert("Camera access failed! " + err);
        document.getElementById('start-btn').classList.remove('d-none');
    });
}

function stopScanner() {
    if (html5QrScanner && html5QrScanner.isScanning) {
        html5QrScanner.stop();
    }
}

function simulateFingerprint() {
    const btn = document.getElementById('fingerprint-btn');
    btn.classList.add('bg-primary', 'text-white');
    btn.innerHTML = '<div class="spinner-border" role="status"></div>';
    
    setTimeout(() => {
        btn.classList.remove('bg-primary', 'text-white');
        btn.innerHTML = '<i class="bi bi-fingerprint" style="font-size: 80px;"></i>';
        // For simulation, we'll try to scan the first member in the DB if manual field is empty
        let testId = document.getElementById('manual_id').value || '1';
        processAttendance(testId, 'Fingerprint');
    }, 1500);
}

function processAttendance(id, method) {
    const formData = new FormData();
    formData.append('identifier', id);
    formData.append('method', method);

    fetch('<?= BASE_URL ?>api/process_attendance.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        const resDiv = document.getElementById('scan-result');
        resDiv.classList.remove('d-none');
        
        if (data.success) {
            document.getElementById('res-avatar').src = data.user.avatar ? '<?= BASE_URL ?>' + data.user.avatar : '<?= BASE_URL ?>assets/img/avatar.png';
            document.getElementById('res-name').innerText = data.user.name;
            document.getElementById('res-role').innerText = data.user.role.toUpperCase() + ' (ID: ' + (data.user.registration_no || data.user.id) + ')';
            document.getElementById('res-time').innerText = 'Processed at ' + data.time;
            document.getElementById('res-action').innerText = (data.action === 'check_in' ? 'CHECK-IN' : 'CHECK-OUT');
            document.getElementById('res-action').className = 'badge rounded-pill px-3 ' + (data.action === 'check_in' ? 'bg-success' : 'bg-warning text-dark');
            
            document.getElementById('res-msg').innerText = data.message;
            document.getElementById('res-msg').className = 'alert mt-3 mb-0 small py-2 fw-bold text-center alert-success';
            
            // Auto hide after 5 seconds then reload
            setTimeout(() => { location.reload(); }, 5000);
        } else {
            document.getElementById('res-avatar').src = '<?= BASE_URL ?>assets/img/avatar.png';
            document.getElementById('res-name').innerText = 'Access Denied';
            document.getElementById('res-role').innerText = 'Invalid Identifier';
            document.getElementById('res-msg').innerText = data.message;
            document.getElementById('res-msg').className = 'alert mt-3 mb-0 small py-2 fw-bold text-center alert-danger';
        }
    });
}

function resetUI() {
    document.getElementById('scan-result').classList.add('d-none');
    document.getElementById('start-btn').classList.remove('d-none');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
