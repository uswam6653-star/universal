<?php
require_once 'core/db.php';

// Get all members
$members = $pdo->query("SELECT id, name, identity_no FROM users WHERE role = 'student'")->fetchAll();

if (empty($members)) {
    die("No members found to seed progress for.\n");
}

echo "Seeding progress data for " . count($members) . " members...\n";

foreach ($members as $m) {
    $mid = $m['id'];
    $mname = $m['name'];
    
    // Parse base weight from identity_no or random
    $meta = explode('|', $m['identity_no'] ?? '');
    $base_weight = rand(70, 95);
    
    // Create 5 historical entries for each member
    for ($i = 5; $i >= 0; $i--) {
        $days_ago = $i * 3; // Every 3 days
        $date = date('Y-m-d H:i:s', strtotime("-$days_ago days"));
        
        // Slightly decreasing weight to show progress
        $current_weight = $base_weight - (5 - $i) * 0.5; 
        $chest = 40 - (5 - $i) * 0.2;
        $waist = 36 - (5 - $i) * 0.3;
        
        $details = "Weight: {$current_weight}kg | Chest: {$chest}in | Waist: {$waist}in | Notes: Routine check - Showing good consistency. (Coach: System)";
        
        // Insert Log
        $pdo->prepare("INSERT INTO sys_activity_logs (user_id, action, details, created_at) VALUES (?, 'PROGRESS', ?, ?)")
            ->execute([$mid, $details, $date]);
    }
    echo "Added 6 progress logs for member: $mname\n";
}

echo "All members now have historical progress data.\n";
?>
