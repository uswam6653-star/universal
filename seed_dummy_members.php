<?php
require_once 'core/db.php';

$trainers = [1, 18]; // Admin and Senior Trainer
$names = ['Ayesha Khan', 'Zeeshan Ali', 'Maria Batool', 'Hamza Sheikh', 'Fatima Zahra'];
$goals = ['Weight Loss', 'Muscle Gain', 'Strength', 'Fat Loss', 'Endurance'];

foreach ($trainers as $tid) {
    // Get trainer name
    $t = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $t->execute([$tid]);
    $tname = $t->fetchColumn() ?: 'Unknown Coach';

    foreach ($names as $i => $name) {
        $age = rand(20, 40);
        $goal = $goals[$i];
        $plan = "Premium Pro";
        $email = strtolower(str_replace(' ', '', $name)) . rand(10,99) . "@gym.com";
        $reg = "REG-" . rand(1000, 9999);
        $weight = rand(60, 95);
        
        // Check if exists
        $chk = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $chk->execute([$email]);
        if (!$chk->fetch()) {
            $meta = "$plan|5000|$age|$goal|0|0|$tname|0|2026-12-31| |trainer:$tid|";
            $pdo->prepare("INSERT INTO users (name, email, password, role, registration_no, identity_no, is_active) VALUES (?, ?, ?, 'student', ?, ?, 1)")
                ->execute([$name, $email, md5('123'), $reg, $meta]);
            $sid = $pdo->lastInsertId();
            
            $pdo->prepare("INSERT INTO complaints (user_id, subject, description, assigned_to, status) VALUES (?, 'WORKOUT', ?, ?, 'Active')")
                ->execute([$sid, "Standard $plan Routine", $tid]);
                
            $pdo->prepare("INSERT INTO sys_activity_logs (user_id, action, details) VALUES (?, 'PROGRESS', ?)")
                ->execute([$sid, "Weight: {$weight}kg | Initial Check (Logged by Coach: $tname)"]);
        }
    }
}
echo "Dummy members seeded successfully for testing.\n";
?>
