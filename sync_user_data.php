<?php
require_once 'core/db.php';

// 1. Get all trainers
$trainers = $pdo->query("SELECT id, name FROM users WHERE role IN ('trainer', 'hod')")->fetchAll();
if (empty($trainers)) {
    die("No trainers found in the system. Please add trainers first.\n");
}

// 2. Get all members
$members = $pdo->query("SELECT id, name, identity_no FROM users WHERE role = 'student'")->fetchAll();

echo "Starting Synchronization of User Data...\n";
echo "Found " . count($members) . " members and " . count($trainers) . " trainers.\n";

$t_index = 0;
foreach ($members as $m) {
    $mid = $m['id'];
    $mname = $m['name'];
    
    // Assign to trainers in a round-robin fashion
    $trainer = $trainers[$t_index];
    $tid = $trainer['id'];
    $tname = $trainer['name'];
    
    // a. Update/Insert Workout Assignment (Complaints Table)
    $chk = $pdo->prepare("SELECT id FROM complaints WHERE user_id = ? AND subject = 'WORKOUT'");
    $chk->execute([$mid]);
    $existing = $chk->fetch();
    
    if ($existing) {
        $pdo->prepare("UPDATE complaints SET assigned_to = ?, status = 'Active' WHERE id = ?")->execute([$tid, $existing['id']]);
    } else {
        $pdo->prepare("INSERT INTO complaints (user_id, subject, description, assigned_to, status, priority) VALUES (?, 'WORKOUT', 'Standard Fitness Routine', ?, 'Active', 'Medium')")->execute([$mid, $tid]);
    }
    
    // b. Update Member Metadata (identity_no)
    $meta = explode('|', $m['identity_no'] ?? '');
    $meta = array_pad($meta, 11, '');
    
    // Logic: Keep existing data if present, but force Trainer linkage
    if (empty($meta[0])) $meta[0] = 'Premium Plan'; // Plan
    if (empty($meta[2])) $meta[2] = rand(20, 35);   // Age
    if (empty($meta[3])) $meta[3] = 'General Fitness'; // Goal
    
    $meta[6] = $tname; // Trainer Name (Index 6)
    $meta[10] = "trainer:$tid"; // Trainer ID Link (Index 10)
    
    $new_meta = implode('|', $meta);
    $pdo->prepare("UPDATE users SET identity_no = ? WHERE id = ?")->execute([$new_meta, $mid]);
    
    echo "Linked Member [$mname] to Trainer [$tname]\n";
    
    // Rotate trainer
    $t_index = ($t_index + 1) % count($trainers);
}

echo "Data synchronization complete!\n";
?>
