<?php
require_once 'core/db.php';

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Generate a random temporary password
        $tempPassword = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$'), 0, 8);
        $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
        
        // Update password in database
        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($updateStmt->execute([$hashedPassword, $user['id']])) {
            // Since we don't have an SMTP server configured in this local ERP, we will display it securely.
            $msg = "Password reset successful! Your temporary password is: <strong>{$tempPassword}</strong> <br>Please <a href='login.php' class='fw-bold text-decoration-none'>Login</a> and change it.";
            $msgType = "success";
        } else {
            $msg = "An error occurred while resetting your password. Please try again.";
            $msgType = "danger";
        }
    } else {
        $msg = "No account found with that email address.";
        $msgType = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password | Universal Gym ERP</title>
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
            font-size: 2.2rem;
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
            <h1>Account Recovery</h1>
            <p class="text-muted fw-500">Enter your email to reset your password</p>
        </div>

        <?php if($msg): ?>
            <div class="alert alert-<?= $msgType ?> border-0 rounded-4 py-3 small text-center animate__animated animate__shakeX"><?= $msg ?></div>
        <?php endif; ?>

        <?php if($msgType !== 'success'): ?>
        <form action="" method="post">
            <div class="mb-4">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 shadow-lg">Reset Password</button>

            <div class="footer-links">
                <p class="small text-muted mb-0">Remembered your password? <a href="login.php">Sign In</a></p>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
