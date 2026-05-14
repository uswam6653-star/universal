<?php
require_once 'core/db.php';

// 1. Get all trainers
$trainers = $pdo->query("SELECT id, name FROM users WHERE role='trainer'")->fetchAll(PDO::FETCH_ASSOC);
if (!$trainers) {
    die("No trainers available.");
}

// 2. Get all members
$members = $pdo->query("SELECT id, name, identity_no FROM users WHERE role='student'")->fetchAll(PDO::FETCH_ASSOC);

$t_count = count($trainers);
$t_idx = 0;

$updated_count = 0;

foreach ($members as $m) {
    $sid = $m['id'];
    $sname = $m['name'];
    $meta = explode('|', $m['identity_no'] ?? '');
    
    // Assign trainer if missing or empty
    $trainerId = 0;
    $trainerName = '';
    
    $trainerMeta = $meta[10] ?? '';
    if (strpos($trainerMeta, 'trainer:') !== false) {
        $trainerId = (int)str_replace('trainer:', '', $trainerMeta);
    } else {
        $trainerId = (int)$trainerMeta;
    }

    if (!$trainerId) {
        $t = $trainers[$t_idx % $t_count];
        $trainerId = $t['id'];
        $trainerName = $t['name'];
        $t_idx++;
        
        // Rebuild meta
        $meta = array_pad($meta, 11, '');
        $meta[0] = 'Standard Plan';
        $meta[1] = '3000';
        $meta[6] = $trainerName;
        $meta[8] = '2026-12-31';
        $meta[10] = "trainer:$trainerId";
        
        $new_meta = implode('|', $meta);
        $pdo->prepare("UPDATE users SET identity_no = ? WHERE id = ?")->execute([$new_meta, $sid]);
    } else {
        // Just find the trainer name for logging
        foreach ($trainers as $tr) {
            if ($tr['id'] == $trainerId) {
                $trainerName = $tr['name'];
                break;
            }
        }
    }

    // Link: Workout Plan (Complaints Table)
    $chkWork = $pdo->prepare("SELECT id FROM complaints WHERE user_id = ? AND subject = 'WORKOUT'");
    $chkWork->execute([$sid]);
    if (!$chkWork->fetch()) {
        $planText = "Day 1: Chest & Triceps\nDay 2: Back & Biceps\nDay 3: Legs & Core\nDiet: High Protein, Low Carbs.";
        $pdo->prepare("INSERT INTO complaints (user_id, subject, description, assigned_to, status, priority) VALUES (?, 'WORKOUT', ?, ?, 'In Progress', 'High')")->execute([$sid, $planText, $trainerId]);
    }

    // Link: Progress Logs (sys_activity_logs)
    $chkProg = $pdo->prepare("SELECT id FROM sys_activity_logs WHERE user_id = ? AND action = 'PROGRESS'");
    $chkProg->execute([$sid]);
    if (!$chkProg->fetch()) {
        $logMsg = "Initial assessment completed. Weight: 70kg. Logged by Trainer:$trainerId";
        $pdo->prepare("INSERT INTO sys_activity_logs (user_id, action, details) VALUES (?, 'PROGRESS', ?)")->execute([$sid, $logMsg]);
    }

    // Link: Attendance Logs
    $chkAtt = $pdo->prepare("SELECT id FROM sys_activity_logs WHERE user_id = ? AND action = 'ATTENDANCE'");
    $chkAtt->execute([$sid]);
    if (!$chkAtt->fetch()) {
        $pdo->prepare("INSERT INTO sys_activity_logs (user_id, action, details) VALUES (?, 'ATTENDANCE', 'Present')")->execute([$sid]);
    }

    $updated_count++;
}

echo "Successfully related and linked all $updated_count gym members with Trainers, Workout Plans, Progress, and Attendance!\n";
?>
