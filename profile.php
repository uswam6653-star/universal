<?php 
require_once 'core/session.php'; // Ensures login
require_once 'includes/header.php'; 

$uID = $_SESSION['user_id'];
$msg = "";
$msgType = "";

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $password = $_POST['password'];
    
    // 1. Handle File Upload
    if (!empty($_FILES['avatar']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        
        $fileName = time() . "_" . basename($_FILES['avatar']['name']);
        $targetFilePath = $targetDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
        
        // Allow certain file formats
        $allowTypes = array('jpg','png','jpeg','gif');
        if(in_array(strtolower($fileType), $allowTypes)){
            if(move_uploaded_file($_FILES['avatar']['tmp_name'], $targetFilePath)){
                // Update DB
                $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?")->execute([$targetFilePath, $uID]);
                $_SESSION['avatar'] = $targetFilePath;
            }
        }
    }

    // 2. Update Password (if provided)
    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $uID]);
    }

    // 3. Update Basic Info
    $pdo->prepare("UPDATE users SET name = ? WHERE id = ?")->execute([$name, $uID]);

    // Refresh Session Name
    $_SESSION['name'] = $name;
    $msg = "Profile updated successfully!";
    $msgType = "success";
}

// Fetch Latest User Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uID]);
$user = $stmt->fetch();

// Decode metadata for gym info
$meta = explode('|', $user['identity_no'] ?? '');

?>

<style>
    :root {
        --glass-bg: rgba(255, 255, 255, 0.7);
        --glass-border: rgba(255, 255, 255, 0.4);
        --glass-blur: blur(15px);
    }
    .profile-container {
        perspective: 1000px;
    }
    .profile-hero {
        position: relative;
        height: 350px;
        background: linear-gradient(rgba(0,0,0,0.2), rgba(0,0,0,0.8)), url('<?= BASE_URL ?>assets/img/gym_profile_banner.png') center/cover no-repeat;
        border-radius: 40px;
        margin-bottom: -150px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        display: flex;
        align-items: center;
        padding-left: 60px;
        overflow: hidden;
    }
    .profile-hero::after {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: radial-gradient(circle at top right, rgba(0,210,255,0.2), transparent);
    }
    .profile-hero-title {
        color: white;
        z-index: 10;
    }
    .glass-card {
        background: var(--glass-bg);
        backdrop-filter: var(--glass-blur);
        -webkit-backdrop-filter: var(--glass-blur);
        border: 1px solid var(--glass-border);
        border-radius: 35px;
        box-shadow: 0 25px 50px rgba(0,0,0,0.1);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .profile-main-card {
        padding-top: 150px;
        margin-top: -20px;
    }
    .profile-img-overlap {
        position: relative;
        width: 220px;
        height: 220px;
        margin: 0 auto -110px;
        z-index: 20;
    }
    .profile-img-overlap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border: 12px solid white;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    }
    .stat-pill {
        background: white;
        padding: 15px 25px;
        border-radius: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        transition: all 0.3s ease;
        border: 1px solid #f0f0f0;
    }
    .stat-pill:hover {
        transform: translateY(-3px);
        border-color: var(--bs-primary);
    }
    .strength-meter {
        height: 8px;
        background: #eee;
        border-radius: 10px;
        overflow: hidden;
        margin: 15px 0;
    }
    .strength-fill {
        height: 100%;
        background: linear-gradient(90deg, #ff416c, #ff4b2b);
        transition: width 1s ease-in-out;
    }
    .nav-pills-premium {
        background: #f0f2f5;
        padding: 8px;
        border-radius: 20px;
        display: inline-flex;
    }
    .nav-pills-premium .nav-link {
        border-radius: 15px;
        padding: 10px 25px;
        font-weight: 700;
        color: #6c757d;
        transition: all 0.3s ease;
    }
    .nav-pills-premium .nav-link.active {
        background: white;
        color: var(--bs-primary);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .premium-input-group {
        background: white;
        border-radius: 18px;
        padding: 5px 15px;
        border: 2px solid #f0f2f5;
        transition: all 0.3s ease;
    }
    .premium-input-group:focus-within {
        border-color: var(--bs-primary);
        box-shadow: 0 10px 25px rgba(var(--bs-primary-rgb), 0.1);
    }
    .premium-input-group input {
        border: none !important;
        box-shadow: none !important;
        background: transparent;
        padding: 12px 10px;
    }
</style>

<div class="container-fluid py-4 px-lg-5 profile-container">
    <!-- Hero Banner with ID -->
    <div class="profile-hero">
        <div class="profile-hero-title">
            <h1 class="display-3 fw-bold mb-0">Identity Hub</h1>
            <p class="fs-5 opacity-75">Visualizing excellence for the <?= htmlspecialchars($settings['system_name'] ?? 'Universal System') ?></p>
        </div>
    </div>

    <!-- Avatar Overlap -->
    <div class="profile-img-overlap">
        <img src="<?= !empty($user['avatar']) ? BASE_URL.$user['avatar'] : BASE_URL.'assets/img/avatar.png?v='.time() ?>" 
             class="rounded-circle shadow-lg" alt="Athlete Avatar">
    </div>

    <div class="row g-4 justify-content-center">
        <!-- Main Profile Info -->
        <div class="col-lg-5">
            <div class="glass-card profile-main-card mb-4 text-center p-4">
                <div class="p-3">
                    <h1 class="fw-bold text-dark mb-1"><?= htmlspecialchars($user['name']) ?></h1>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-4 py-2 mb-4 fw-bold">
                        <i class="bi bi-shield-check me-2"></i><?= strtoupper($user['role']) ?>
                    </span>
                    
                    <!-- Profile Strength -->
                    <div class="text-start mb-5">
                        <div class="d-flex justify-content-between">
                            <small class="fw-bold text-muted uppercase">Profile Completeness</small>
                            <small class="fw-bold text-primary">85%</small>
                        </div>
                        <div class="strength-meter">
                            <div class="strength-fill" style="width: 85%; background: linear-gradient(90deg, var(--bs-primary), #00d2ff);"></div>
                        </div>
                    </div>

                    <!-- Stats Bar -->
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="stat-pill">
                                <i class="bi bi-calendar3 fs-4 text-primary me-3"></i>
                                <div class="text-start">
                                    <small class="text-muted d-block">Apprentice</small>
                                    <span class="fw-bold">Member Since '24</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-pill">
                                <i class="bi bi-activity fs-4 text-success me-3"></i>
                                <div class="text-start">
                                    <small class="text-muted d-block">Active Status</small>
                                    <span class="fw-bold">Level 05 Admin</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Management Section -->
        <div class="col-lg-7">
            <div class="glass-card h-100 p-5 mt-lg-5">
                <div class="mb-5 text-center text-lg-start">
                    <ul class="nav nav-pills nav-pills-premium mb-0" id="profileTabs">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="pill" href="#basicInfo">
                                <i class="bi bi-person-circle me-2"></i>Personal Info
                            </a>
                        </li>
                        <li class="nav-item ms-2">
                            <a class="nav-link" data-bs-toggle="pill" href="#security">
                                <i class="bi bi-lock-fill me-2"></i>Security Settings
                            </a>
                        </li>
                    </ul>
                </div>

                <?php if($msg): ?>
                    <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4">
                        <i class="bi bi-stars me-2"></i><?= $msg ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="tab-content">
                        <!-- Basic Info Tab -->
                        <div class="tab-pane fade show active" id="basicInfo">
                            <div class="row g-4">
                                <div class="col-12">
                                    <label class="small fw-bold text-muted mb-2 ms-2">Your Full Name</label>
                                    <div class="premium-input-group d-flex align-items-center">
                                        <i class="bi bi-pen text-primary fs-5"></i>
                                        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="small fw-bold text-muted mb-2 ms-2">Update Avatar</label>
                                    <div class="premium-input-group d-flex align-items-center">
                                        <i class="bi bi-camera text-primary fs-5"></i>
                                        <input type="file" class="form-control" name="avatar">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Security Tab -->
                        <div class="tab-pane fade" id="security">
                            <div class="row g-4">
                                <div class="col-12">
                                    <label class="small fw-bold text-muted mb-2 ms-2">Account Email</label>
                                    <div class="premium-input-group d-flex align-items-center bg-light-subtle">
                                        <i class="bi bi-envelope text-muted fs-5"></i>
                                        <input type="text" class="form-control text-muted" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="small fw-bold text-muted mb-2 ms-2">Security Key (Password)</label>
                                    <div class="premium-input-group d-flex align-items-center">
                                        <i class="bi bi-shield-lock text-primary fs-5"></i>
                                        <input type="password" class="form-control" name="password" placeholder="••••••••••••">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 w-100 py-3 fw-bold shadow-lg border-0" 
                                style="background: linear-gradient(90deg, var(--bs-primary), #00d2ff);">
                            Commit Changes & Synchronize
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>