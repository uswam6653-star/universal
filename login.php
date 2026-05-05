<?php
require_once 'core/session.php';
require_once 'core/auth.php';
$auth = new Auth($pdo);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier']);
    $password = $_POST['password'];

    if ($auth->login($identifier, $password)) {
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid Credentials or Account Suspended.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Universal Gym ERP</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" />
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            --glass-bg: rgba(255, 255, 255, 0.85);
            --accent: #6366f1;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background: #0f172a;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        /* Background Animated Circles */
        .circle {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.4;
            animation: move 20s infinite alternate;
        }
        .circle-1 { width: 500px; height: 500px; background: #6366f1; top: -150px; left: -150px; }
        .circle-2 { width: 400px; height: 400px; background: #a855f7; bottom: -100px; right: -100px; animation-delay: -5s; }

        @keyframes move {
            from { transform: translate(0, 0); }
            to { transform: translate(50px, 50px); }
        }

        .login-card {
            width: 100%;
            max-width: 440px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: 32px;
            padding: 50px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transform: translateY(0);
            transition: all 0.5s ease;
        }
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.7);
        }
        .logo-area {
            text-align: center;
            margin-bottom: 35px;
        }
        .logo-area h1 {
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
            font-size: 2.5rem;
            letter-spacing: -1px;
        }
        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
            padding-left: 4px;
        }
        .input-group {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s;
        }
        .input-group:focus-within {
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
            background: #fff;
        }
        .input-group-text {
            background: transparent;
            border: none;
            color: #94a3b8;
            padding-left: 18px;
        }
        .form-control {
            border: none;
            padding: 14px 16px;
            background: transparent;
            font-size: 0.95rem;
        }
        .form-control:focus {
            box-shadow: none;
            background: transparent;
        }
        .password-toggle {
            cursor: pointer;
            padding-right: 18px;
            color: #94a3b8;
            transition: color 0.3s;
        }
        .password-toggle:hover {
            color: #6366f1;
        }
        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            border-radius: 16px;
            padding: 15px;
            font-weight: 700;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            margin-top: 10px;
        }
        .btn-primary:hover {
            transform: scale(1.02);
            box-shadow: 0 15px 25px -5px rgba(168, 85, 247, 0.4);
        }
        .footer-links {
            margin-top: 30px;
            text-align: center;
        }
        .footer-links a {
            color: #6366f1;
            text-decoration: none;
            font-weight: 600;
            transition: opacity 0.3s;
        }
        .footer-links a:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="circle circle-1"></div>
    <div class="circle circle-2"></div>

    <div class="login-card">
        <div class="logo-area">
            <h1>Universal ERP</h1>
            <p class="text-muted fw-500">Premium Gym Management Experience</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger border-0 rounded-4 py-2 small text-center animate__animated animate__shakeX"><?= $error ?></div>
        <?php endif; ?>

        <form action="" method="post">
            <div class="mb-4">
                <label class="form-label">Access Identifier</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person-circle"></i></span>
                    <input type="text" name="identifier" class="form-control" placeholder="Email, CNIC or Reg ID" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label d-flex justify-content-between">
                    Secure Password
                    <a href="#" class="text-primary text-decoration-none" style="font-size: 11px;">Recover?</a>
                </label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                    <input type="password" name="password" id="passwordInput" class="form-control" placeholder="••••••••" required>
                    <span class="input-group-text password-toggle" onclick="togglePassword()">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </span>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 shadow-lg">Sign In to Dashboard</button>

            <div class="footer-links">
                <p class="small text-muted mb-0">New to the community? <a href="register.php">Create Account</a></p>
            </div>
        </form>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('passwordInput');
            const icon = document.getElementById('toggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>