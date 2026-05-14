<?php
require_once 'core/db.php';

// 1. Get first trainer
$trainer = $pdo->query("SELECT id, name FROM users WHERE role='trainer' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$trainer) {
    die("No trainer found in DB. Please create one.");
}
$tid = $trainer['id'];
$tname = $trainer['name'];

// 2. Get or create 4 members
$students = $pdo->query("SELECT id, name FROM users WHERE role='student' LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
if (count($students) < 4) {
    for ($i=count($students); $i<4; $i++) {
        $rand = rand(1000, 9999);
        $pdo->exec("INSERT INTO users (name, email, password, role, is_active, registration_no) VALUES ('Gym Member $rand', 'member$rand@gym.com', '12345', 'student', 1, 'GYM-$rand')");
        $students[] = ['id' => $pdo->lastInsertId(), 'name' => "Gym Member $rand"];
    }
}

// 3. Clear old assignments for this trainer
$pdo->exec("DELETE FROM complaints WHERE assigned_to = $tid AND subject = 'WORKOUT'");
$pdo->exec("DELETE FROM sys_activity_logs WHERE action = 'PROGRESS' AND details LIKE '%Trainer:$tid%'");

// 4. Assign members and add logs
foreach ($students as $stu) {
    $sid = $stu['id'];
    
    // Create the exact identity_no format the dashboard looks for
    $meta = "Premium Plan|5000|0|0|0|0|$tname|0|2026-12-31| |trainer:$tid|";
    $pdo->prepare("UPDATE users SET identity_no = ?, is_active = 1 WHERE id = ?")->execute([$meta, $sid]);
    
    // Add to training hub assignments
    $pdo->prepare("INSERT INTO complaints (user_id, subject, description, assigned_to, status, priority) VALUES (?, 'WORKOUT', 'Gym Training Assignment', ?, 'In Progress', 'Medium')")->execute([$sid, $tid]);

    // Add progress logs to make the dashboard timeline look active
    $weights = ['72kg', '74kg', '80kg', '65kg'];
    $w = $weights[array_rand($weights)];
    $logMsg = "Updated fitness record. Current Weight: $w. Logged by Trainer:$tid";
    $pdo->prepare("INSERT INTO sys_activity_logs (user_id, action, details) VALUES (?, 'PROGRESS', ?)")->execute([$sid, $logMsg]);
}

echo "Successfully injected live sample data for Trainer: $tname!";
?>
