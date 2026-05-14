<?php
require_once 'core/db.php';

// Get all trainers
$trainers = $pdo->query("SELECT id, name FROM users WHERE role='trainer'")->fetchAll(PDO::FETCH_ASSOC);

if (!$trainers) {
    die("No trainers found in DB.");
}

// Ensure we have at least 10 dummy members to distribute
$students = $pdo->query("SELECT id, name FROM users WHERE role='student'")->fetchAll(PDO::FETCH_ASSOC);
if (count($students) < 10) {
    for ($i=count($students); $i<10; $i++) {
        $rand = rand(1000, 9999);
        $pdo->exec("INSERT INTO users (name, email, password, role, is_active, registration_no) VALUES ('Gym Member $rand', 'member$rand@gym.com', '12345', 'student', 1, 'GYM-$rand')");
        $students[] = ['id' => $pdo->lastInsertId(), 'name' => "Gym Member $rand"];
    }
}

// Distribute students among all trainers
$studentIndex = 0;
foreach ($trainers as $trainer) {
    $tid = $trainer['id'];
    $tname = $trainer['name'];
    
    // Clear old data for this trainer to avoid duplicates
    $pdo->exec("DELETE FROM complaints WHERE assigned_to = $tid AND subject = 'WORKOUT'");
    $pdo->exec("DELETE FROM sys_activity_logs WHERE action = 'PROGRESS' AND details LIKE '%Trainer:$tid%'");
    
    // Assign 2 or 3 students to this trainer
    $assignCount = rand(2, 4);
    for ($i = 0; $i < $assignCount; $i++) {
        // Cycle through students
        $stu = $students[$studentIndex % count($students)];
        $sid = $stu['id'];
        $studentIndex++;
        
        // Identity meta string
        $meta = "Premium Plan|5000|0|0|0|0|$tname|0|2026-12-31| |trainer:$tid|";
        $pdo->prepare("UPDATE users SET identity_no = ?, is_active = 1 WHERE id = ?")->execute([$meta, $sid]);
        
        // Complaints (Workout Assignment)
        $pdo->prepare("INSERT INTO complaints (user_id, subject, description, assigned_to, status, priority) VALUES (?, 'WORKOUT', 'Daily Gym Routine', ?, 'In Progress', 'Medium')")->execute([$sid, $tid]);
        
        // Activity logs
        $weights = ['72kg', '74kg', '80kg', '65kg', '90kg', '55kg'];
        $w = $weights[array_rand($weights)];
        $logMsg = "Updated fitness record. Current Weight: $w. Logged by Trainer:$tid";
        $pdo->prepare("INSERT INTO sys_activity_logs (user_id, action, details) VALUES (?, 'PROGRESS', ?)")->execute([$sid, $logMsg]);
    }
    
    echo "Seeded data for Trainer: $tname (ID: $tid)\n";
}

echo "\nAll trainers successfully seeded with data!";
?>
