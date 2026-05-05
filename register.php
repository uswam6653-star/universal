<?php
require_once 'core/session.php';
require_once 'core/auth.php';
$auth = new Auth($pdo);
$roles = $auth->getPublicRoles();

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim($_POST['name']),
        'email' => trim($_POST['email']),
        'password' => $_POST['password'],
        'role' => $_POST['role'],
        'identity_no' => trim($_POST['identity_no']),
        'registration_no' => trim($_POST['registration_no']),
        'roll_no' => trim($_POST['roll_no'] ?? ''),
        'program_id' => $_POST['program_id'] ?? null,
        'semester_id' => $_POST['semester_id'] ?? null
    ];

    if ($data['password'] !== $_POST['retype_password']) {
        $msg = "Passwords do not match!";
        $msgType = "danger";
    } else {
        $result = $auth->register($data);
        if ($result === true) {
            $msg = "Registration successful! <a href='login.php' class='fw-bold text-decoration-none'>Login now</a>";
            $msgType = "success";
        } else {
            $msg = $result;
            $msgType = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | Universal Gym ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" />
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            --glass-bg: rgba(255, 255, 255, 0.92);
            --accent: #6366f1;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            position: relative;
            overflow-x: hidden;
        }
        .circle {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            z-index: -1;
            opacity: 0.3;
        }
        .circle-1 { width: 600px; height: 600px; background: #6366f1; top: -200px; left: -200px; }
        .circle-2 { width: 500px; height: 500px; background: #a855f7; bottom: -150px; right: -150px; }

        .register-card {
            width: 100%;
            max-width: 600px;
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            border-radius: 36px;
            padding: 50px;
            box-shadow: 0 40px 80px -20px rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        .logo-area { text-align: center; margin-bottom: 40px; }
        .logo-area h1 {
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2.8rem;
            letter-spacing: -1.5px;
        }
        .role-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 35px;
        }
        .role-card {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 24px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        .role-card:hover {
            transform: translateY(-5px);
            border-color: #6366f1;
            background: #fff;
        }
        .role-card.active {
            border-color: #6366f1;
            background: #fff;
            box-shadow: 0 15px 30px -10px rgba(99, 102, 241, 0.2);
        }
        .role-card i {
            font-size: 32px;
            margin-bottom: 12px;
            display: block;
            color: #94a3b8;
            transition: color 0.3s;
        }
        .role-card.active i { color: #6366f1; }
        .role-card span {
            font-weight: 700;
            font-size: 1rem;
            color: #475569;
        }
        .role-card.active span { color: #1e293b; }
        
        .form-control, .form-select {
            border-radius: 16px;
            padding: 14px 18px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        .form-control:focus {
            background: #fff;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            border-radius: 18px;
            padding: 16px;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-top: 20px;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            transform: scale(1.02);
            box-shadow: 0 20px 40px -10px rgba(168, 85, 247, 0.5);
        }
    </style>
</head>
<body>
    <div class="circle circle-1"></div>
    <div class="circle circle-2"></div>

    <div class="register-card">
        <div class="logo-area">
            <h1>Join Us</h1>
            <p class="text-muted fw-500">Start your fitness journey today</p>
        </div>

        <?php if($msg): ?>
            <div class="alert alert-<?= $msgType ?> border-0 rounded-4 py-3 small text-center"><?= $msg ?></div>
        <?php endif; ?>

        <form action="" method="post" id="regForm">
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted ps-2">Full Name</label>
                    <input type="text" name="name" class="form-control" placeholder="John Doe" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted ps-2">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-muted ps-2">I am joining as...</label>
                <div class="role-grid">
                    <div class="role-card" onclick="selectRole('student', this)">
                        <i class="bi bi-person-walking"></i>
                        <span>Gym Member</span>
                    </div>
                    <div class="role-card" onclick="selectRole('trainer', this)">
                        <i class="bi bi-award"></i>
                        <span>Gym Trainer</span>
                    </div>
                    <input type="hidden" name="role" id="selectedRole" required>
                </div>
            </div>

            <div id="dynamicFields" class="row g-3 mb-4 d-none animate__animated animate__fadeIn">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted ps-2" id="idLabel">CNIC / Identity</label>
                    <input type="text" name="identity_no" class="form-control" placeholder="Enter ID">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted ps-2" id="regLabel">Member ID</label>
                    <input type="text" name="registration_no" class="form-control" placeholder="Enter Registration">
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted ps-2">Create Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted ps-2">Confirm Password</label>
                    <input type="password" name="retype_password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 shadow-lg">Complete Registration</button>

            <div class="text-center mt-4">
                <p class="small text-muted mb-0">Already have an account? <a href="login.php" class="text-primary fw-bold text-decoration-none">Sign In</a></p>
            </div>
        </form>
    </div>

    <script>
        function selectRole(role, el) {
            document.querySelectorAll('.role-card').forEach(opt => opt.classList.remove('active'));
            el.classList.add('active');
            document.getElementById('selectedRole').value = role;
            
            const dyn = document.getElementById('dynamicFields');
            dyn.classList.remove('d-none');
            
            if(role === 'student') {
                document.getElementById('idLabel').innerText = "CNIC / Identity";
                document.getElementById('regLabel').innerText = "Member ID (Reg No)";
            } else {
                document.getElementById('idLabel').innerText = "CNIC / Identity";
                document.getElementById('regLabel').innerText = "Trainer Code";
            }
        }
    </script>
</body>
</html>