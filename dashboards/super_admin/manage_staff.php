<?php 
require_once __DIR__ . '/../../includes/header.php'; 

// Handle Add Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    
    // 1. Handle Avatar (File or Webcam)
    $avatarPath = 'assets/img/avatar.png';
    if (!empty($_POST['webcam_image'])) {
        $img = $_POST['webcam_image'];
        $img = str_replace('data:image/png;base64,', '', $img);
        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);
        $fileName = time() . "_staff_webcam.png";
        $targetDir = "../../uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        file_put_contents($targetDir . $fileName, $data);
        $avatarPath = "uploads/" . $fileName;
    } elseif (!empty($_FILES['avatar']['name'])) {
        $targetDir = "../../uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES['avatar']['name']);
        if(move_uploaded_file($_FILES['avatar']['tmp_name'], $targetDir . $fileName)){
            $avatarPath = "uploads/" . $fileName;
        }
    }

    // Metadata
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $address = trim($_POST['address']);
    $join_date = date('Y-m-d');
    
    $meta = array_fill(0, 11, '');
    $meta[0] = $phone; $meta[1] = $gender; $meta[2] = $dob; $meta[3] = $join_date; $meta[4] = $address;
    $identity_no = implode('|', $meta);

    $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkEmail->execute([$email]);
    if ($checkEmail->fetch()) {
        $error = "Error: Email already registered.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, avatar, identity_no, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
        try {
            $stmt->execute([$name, $email, $password, $role, $avatarPath, $identity_no]);
            echo "<script>alert('Staff member added successfully!'); window.location.href='manage_staff.php';</script>";
        } catch(Exception $e) { $error = "Error: " . $e->getMessage(); }
    }
}

// Handle Update Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_staff'])) {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];

    $stmt = $pdo->prepare("SELECT identity_no, avatar FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $current = $stmt->fetch();
    $meta = array_pad(explode('|', $current['identity_no']), 11, '');
    $avatarPath = $current['avatar'];

    if (!empty($_POST['webcam_image'])) {
        $img = $_POST['webcam_image'];
        $img = str_replace('data:image/png;base64,', '', $img);
        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);
        $fileName = time() . "_staff_webcam.png";
        $targetDir = "../../uploads/";
        file_put_contents($targetDir . $fileName, $data);
        $avatarPath = "uploads/" . $fileName;
    } elseif (!empty($_FILES['avatar']['name'])) {
        $targetDir = "../../uploads/";
        $fileName = time() . "_" . basename($_FILES['avatar']['name']);
        if(move_uploaded_file($_FILES['avatar']['tmp_name'], $targetDir . $fileName)){
            $avatarPath = "uploads/" . $fileName;
        }
    }

    $meta[0] = trim($_POST['phone']);
    $meta[1] = $_POST['gender'];
    $meta[2] = $_POST['dob'];
    $meta[4] = trim($_POST['address']);
    $new_identity = implode('|', $meta);

    $sql = "UPDATE users SET name = ?, email = ?, role = ?, avatar = ?, identity_no = ? WHERE id = ?";
    $pdo->prepare($sql)->execute([$name, $email, $role, $avatarPath, $new_identity, $id]);
    echo "<script>alert('Staff updated!'); window.location.href='manage_staff.php';</script>";
}

// Handle Status Toggle (Unique Feature)
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $pdo->prepare("UPDATE users SET is_active = 1 - is_active WHERE id = ? AND role IN ('hod', 'clerk', 'trainer', 'gym_manager')")->execute([$id]);
    header("Location: manage_staff.php");
    exit;
}

// Handle Delete Staff
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role IN ('hod', 'clerk', 'trainer', 'gym_manager')");
    $stmt->execute([$id]);
    echo "<script>window.location.href='manage_staff.php';</script>";
}

// Fetch all staff members
$staff = $pdo->query("SELECT * FROM users WHERE role IN ('hod', 'clerk', 'trainer', 'gym_manager') ORDER BY id DESC")->fetchAll();
?>

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-transparent border-0 p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="fw-bold mb-0">Manage Gym Staff</h4>
                    <p class="text-muted small mb-0">List of all registered Trainers and Management Staff</p>
                </div>
                <div class="d-flex gap-2">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <span class="input-group-text bg-white border-end-0 rounded-start-pill ps-3"><i class="bi bi-search"></i></span>
                        <input type="text" id="staffSearch" class="form-control border-start-0 rounded-end-pill px-3" placeholder="Search staff...">
                    </div>
                    <button class="btn btn-dark rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                        <i class="bi bi-person-plus me-2"></i> Register New Staff
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if(isset($error)): ?> <div class="alert alert-danger m-3 rounded-3"><?= $error ?></div> <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="staffTable">
                        <thead class="bg-light small fw-bold text-uppercase text-secondary">
                            <tr>
                                <th class="ps-4">Staff Name</th>
                                <th>Contact & Address</th>
                                <th>Designation / Role</th>
                                <th class="text-center">Identity</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($staff as $s): 
                                $meta_parts = array_pad(explode('|', $s['identity_no']), 11, '');
                                $s_phone = $meta_parts[0] ?: 'N/A';
                                $s_address = $meta_parts[4] ?: 'N/A';
                                $s_avatar = !empty($s['avatar']) ? BASE_URL.$s['avatar'] : BASE_URL.'assets/img/avatar.png?v='.time();
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <img src="<?= $s_avatar ?>" class="rounded-circle border p-1 me-3" width="45" height="45" style="object-fit: cover;">
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($s['name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($s['email']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small"><strong>Ph:</strong> <?= htmlspecialchars($s_phone) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($s_address) ?></div>
                                </td>
                                <td>
                                    <?php if($s['role'] === 'hod' || $s['role'] === 'trainer'): ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">Gym Trainer / HOD</span>
                                    <?php elseif($s['role'] === 'gym_manager'): ?>
                                        <span class="badge bg-dark bg-opacity-10 text-dark rounded-pill px-3">Gym Manager</span>
                                    <?php else: ?>
                                        <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3">Inventory/Clerk</span>
                                    <?php endif; ?>
                                    <div class="small mt-1 <?= $s['is_active'] ? 'text-success' : 'text-danger fw-bold' ?>"><i class="bi bi-circle-fill me-1" style="font-size: 8px;"></i> <?= $s['is_active'] ? 'Active' : 'Offline' ?></div>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-dark rounded-pill px-3" onclick='showStaffCard(<?= json_encode($s) ?>, <?= json_encode($meta_parts) ?>)'>
                                        <i class="bi bi-card-heading me-1"></i> ID Card
                                    </button>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary border-0 rounded-circle p-2 me-1" onclick='editStaff(<?= json_encode($s) ?>, <?= json_encode($meta_parts) ?>)' title="Edit Staff">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <a href="?delete=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger border-0 rounded-circle p-2" onclick="return confirm('Are you sure you want to delete this staff member?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($staff)): ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">No staff members registered. Add your trainers and clerks now.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-bold">Register New Staff Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Full Name</label>
                        <input type="text" name="name" class="form-control rounded-3" placeholder="Enter staff name" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Email Address</label>
                        <input type="email" name="email" class="form-control rounded-3" placeholder="staff@gym.com" required>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Password</label>
                        <input type="password" name="password" class="form-control rounded-3" placeholder="Create a password" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Designation (System Role)</label>
                        <select name="role" class="form-select rounded-3" required>
                            <option value="trainer">Gym Trainer (HOD)</option>
                            <option value="clerk">Front Desk / Inventory (Clerk)</option>
                            <option value="gym_manager">Gym Manager</option>
                        </select>
                    </div>
                </div>
                <hr class="my-3 opacity-10">
                <div class="row g-3">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Contact Number</label>
                        <input type="text" name="phone" id="add_phone" class="form-control rounded-3" placeholder="03XXXXXXXXX">
                    </div>
                    <div class="col-md-6 mb-3 text-center">
                        <label class="form-label small fw-bold d-block">Staff Photo</label>
                        <div class="d-flex flex-column align-items-center gap-2">
                            <div id="webcam_container" class="d-none border rounded-3 overflow-hidden shadow-sm" style="width: 200px; height: 150px;">
                                <video id="video" width="200" height="150" autoplay></video>
                            </div>
                            <img id="capture_preview" src="<?= BASE_URL ?>assets/img/avatar.png" class="rounded-3 border shadow-sm d-none" width="100" height="100" style="object-fit: cover;">
                            <div class="btn-group w-100">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="startWebcam('add')"><i class="bi bi-camera"></i></button>
                                <label class="btn btn-sm btn-outline-secondary mb-0">
                                    <i class="bi bi-upload"></i>
                                    <input type="file" name="avatar" class="d-none" accept="image/*" onchange="previewFile(this, 'add')">
                                </label>
                            </div>
                            <button type="button" id="snap" class="btn btn-sm btn-primary d-none w-100" onclick="takeSnapshot('add')">Capture Now</button>
                            <input type="hidden" name="webcam_image" id="webcam_image_add">
                        </div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Gender</label>
                        <select name="gender" class="form-select rounded-3">
                            <option>Male</option>
                            <option>Female</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Date of Birth</label>
                        <input type="date" name="dob" class="form-control rounded-3">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Residential Address</label>
                    <textarea name="address" class="form-control rounded-3" rows="2" placeholder="Street, Area, City"></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_staff" class="btn btn-dark rounded-pill px-4 shadow-sm">Complete Staff Access</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Staff Modal -->
<div class="modal fade" id="editStaffModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow rounded-4">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-bold">Update Staff Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Full Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control rounded-3" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Email Address</label>
                        <input type="email" name="email" id="edit_email" class="form-control rounded-3" required>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6 mb-3 text-center">
                        <label class="form-label small fw-bold d-block">Update Photo</label>
                        <div class="d-flex flex-column align-items-center gap-2">
                            <div id="webcam_container_edit" class="d-none border rounded-3 overflow-hidden" style="width: 200px; height: 150px;">
                                <video id="video_edit" width="200" height="150" autoplay></video>
                            </div>
                            <img id="edit_avatar_preview" src="" class="rounded-3 border shadow-sm" width="80" height="80" style="object-fit: cover;">
                            <div class="btn-group w-100">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="startWebcam('edit')"><i class="bi bi-camera"></i></button>
                                <label class="btn btn-sm btn-outline-secondary mb-0">
                                    <i class="bi bi-upload"></i>
                                    <input type="file" name="avatar" class="d-none" accept="image/*" onchange="previewFile(this, 'edit')">
                                </label>
                            </div>
                            <button type="button" id="snap_edit" class="btn btn-sm btn-primary d-none w-100" onclick="takeSnapshot('edit')">Capture</button>
                            <input type="hidden" name="webcam_image" id="webcam_image_edit">
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">System Role</label>
                        <select name="role" id="edit_role" class="form-select rounded-3">
                            <option value="trainer">Gym Trainer (HOD)</option>
                            <option value="clerk">Inventory Clerk</option>
                            <option value="gym_manager">Gym Manager</option>
                        </select>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Contact Number</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control rounded-3">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Gender</label>
                        <select name="gender" id="edit_gender" class="form-select rounded-3">
                            <option>Male</option>
                            <option>Female</option>
                        </select>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Date of Birth</label>
                        <input type="date" name="dob" id="edit_dob" class="form-control rounded-3">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Address</label>
                        <textarea name="address" id="edit_address" class="form-control rounded-3" rows="1"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="update_staff" class="btn btn-dark rounded-pill px-4 shadow-sm">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Staff ID Card Modal -->
<div class="modal fade" id="staffCardModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 bg-transparent">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden" style="background: linear-gradient(135deg, #0f2027, #2c5364);">
                <div class="card-body p-0 text-center text-white">
                    <div class="p-4" style="background: rgba(255,255,255,0.05);">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-light text-dark px-3 rounded-pill fw-bold">STAFF ACCESS</span>
                            <span class="small opacity-50 fw-bold">GYM MANAGEMENT</span>
                        </div>
                        <img id="staffAvatar" src="" class="rounded-circle border border-4 border-light shadow-lg mb-3" width="110" height="110" style="object-fit:cover;">
                        <h4 id="staffName" class="fw-bold mb-0"></h4>
                        <p id="staffRole" class="text-info small fw-bold mb-4 text-uppercase"></p>
                    </div>
                    <div class="p-4 bg-white text-dark rounded-top-5 text-start">
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="small text-muted d-block">Contact</label>
                                <span id="staffPhone" class="fw-bold"></span>
                            </div>
                            <div class="col-6">
                                <label class="small text-muted d-block">Gender</label>
                                <span id="staffGender" class="fw-bold"></span>
                            </div>
                            <div class="col-12">
                                <label class="small text-muted d-block">Joined Date</label>
                                <span id="staffJoin" class="fw-bold"></span>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                            <div class="text-muted small" style="font-size: 10px;">AUTHORIZED PERSONNEL ONLY</div>
                            <div class="bg-white p-2 border rounded shadow-sm">
                                <img id="staffQR" src="" width="60" height="60" alt="Scanner QR">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<canvas id="canvas" width="600" height="450" class="d-none"></canvas>

<script>
let stream = null;

function startWebcam(mode) {
    const video = mode === 'edit' ? document.getElementById('video_edit') : document.getElementById('video');
    const container = mode === 'edit' ? document.getElementById('webcam_container_edit') : document.getElementById('webcam_container');
    const snapBtn = mode === 'edit' ? document.getElementById('snap_edit') : document.getElementById('snap');
    const preview = mode === 'edit' ? document.getElementById('edit_avatar_preview') : document.getElementById('capture_preview');
    
    container.classList.remove('d-none');
    snapBtn.classList.remove('d-none');
    if(preview) preview.classList.add('d-none');

    navigator.mediaDevices.getUserMedia({ video: true, audio: false })
    .then(s => {
        stream = s;
        video.srcObject = s;
    })
    .catch(err => alert("Webcam access denied or not available."));
}

function takeSnapshot(mode) {
    const video = mode === 'edit' ? document.getElementById('video_edit') : document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const context = canvas.getContext('2d');
    const hiddenInput = mode === 'edit' ? document.getElementById('webcam_image_edit') : document.getElementById('webcam_image_add');
    const preview = mode === 'edit' ? document.getElementById('edit_avatar_preview') : document.getElementById('capture_preview');
    const container = mode === 'edit' ? document.getElementById('webcam_container_edit') : document.getElementById('webcam_container');
    const snapBtn = mode === 'edit' ? document.getElementById('snap_edit') : document.getElementById('snap');

    context.drawImage(video, 0, 0, 600, 450);
    const data = canvas.toDataURL('image/png');
    hiddenInput.value = data;
    preview.src = data;
    preview.classList.remove('d-none');
    container.classList.add('d-none');
    snapBtn.classList.add('d-none');

    if(stream) {
        stream.getTracks().forEach(track => track.stop());
    }
}

function previewFile(input, mode) {
    const preview = mode === 'edit' ? document.getElementById('edit_avatar_preview') : document.getElementById('capture_preview');
    const container = mode === 'edit' ? document.getElementById('webcam_container_edit') : document.getElementById('webcam_container');
    const snapBtn = mode === 'edit' ? document.getElementById('snap_edit') : document.getElementById('snap');

    if (input.files && input.files[0]) {
        let reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
            container.classList.add('d-none');
            snapBtn.classList.add('d-none');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function editStaff(user, meta) {
    document.getElementById('edit_id').value = user.id;
    document.getElementById('edit_name').value = user.name;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_phone').value = meta[0] || '';
    document.getElementById('edit_gender').value = meta[1] || 'Male';
    document.getElementById('edit_dob').value = meta[2] || '';
    document.getElementById('edit_address').value = meta[4] || '';
    document.getElementById('edit_avatar_preview').src = user.avatar ? '<?= BASE_URL ?>' + user.avatar : '<?= BASE_URL ?>assets/img/avatar.png';
    new bootstrap.Modal(document.getElementById('editStaffModal')).show();
}

function showStaffCard(user, meta) {
    let regNo = user.registration_no || 'STF-' + user.id;
    document.getElementById('staffAvatar').src = user.avatar ? '<?= BASE_URL ?>' + user.avatar : '<?= BASE_URL ?>assets/img/avatar.png?v=<?= time() ?>';
    document.getElementById('staffName').innerText = user.name;
    let r = user.role;
    let roleText = 'Staff';
    if(r === 'hod' || r === 'trainer') roleText = 'Trainer / Head';
    else if(r === 'gym_manager') roleText = 'Gym Manager';
    else if(r === 'clerk') roleText = 'Inventory Clerk';
    document.getElementById('staffRole').innerText = roleText;
    document.getElementById('staffPhone').innerText = meta[0] || 'N/A';
    document.getElementById('staffGender').innerText = meta[1] || 'N/A';
    document.getElementById('staffJoin').innerText = meta[3] || 'N/A';
    
    // Generate QR Code URL
    document.getElementById('staffQR').src = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' + encodeURIComponent(regNo);
    
    new bootstrap.Modal(document.getElementById('staffCardModal')).show();
}

document.getElementById('staffSearch').addEventListener('keyup', function() {
    let q = this.value.toLowerCase();
    document.querySelectorAll('#staffTable tbody tr').forEach(tr => {
        tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
