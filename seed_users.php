<?php
require_once 'core/db.php';

function seedUser($pdo, $name, $email, $pass, $role, $regNo) {
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->rowCount() == 0) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, registration_no, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([$name, $email, $hash, $role, $regNo]);
        echo "Created $role: $email\n";
    } else {
        echo "$role already exists: $email\n";
    }
}

try {
    seedUser($pdo, 'Gym Admin', 'admin@gym.com', 'admin123', 'super_admin', 'ADM-001');
    seedUser($pdo, 'Senior Trainer', 'trainer@gym.com', 'trainer123', 'hod', 'TRN-001');
    seedUser($pdo, 'Gym Member', 'member@gym.com', 'member123', 'student', 'MEM-001');
} catch (Exception $e) {
    echo "Error seeding: " . $e->getMessage();
}
?>
