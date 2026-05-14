<?php
require_once 'core/db.php';

// Assign 3 members to Admin (ID 1)
$admin_id = 1;
$members = $pdo->query("SELECT id FROM users WHERE role = 'student' LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);

foreach ($members as $sid) {
    // Check if already assigned
    $chk = $pdo->prepare("SELECT id FROM complaints WHERE user_id = ? AND subject = 'WORKOUT'");
    $chk->execute([$sid]);
    $existing = $chk->fetch();

    if ($existing) {
        $pdo->prepare("UPDATE complaints SET assigned_to = ? WHERE id = ?")->execute([$admin_id, $existing['id']]);
    } else {
        $pdo->prepare("INSERT INTO complaints (user_id, subject, description, assigned_to, status, priority) VALUES (?, 'WORKOUT', 'Comprehensive Bodybuilding Plan', ?, 'In Progress', 'High')")->execute([$sid, $admin_id]);
    }
    
    // Also update identity_no for trainer name display
    $u = $pdo->prepare("SELECT identity_no FROM users WHERE id = ?");
    $u->execute([$sid]);
    $meta = explode('|', $u->fetchColumn() ?? '');
    $meta = array_pad($meta, 11, '');
    $meta[6] = 'Gym Admin';
    $meta[10] = "trainer:$admin_id";
    $new_meta = implode('|', $meta);
    $pdo->prepare("UPDATE users SET identity_no = ? WHERE id = ?")->execute([$new_meta, $sid]);
}

echo "Assigned 3 members to Admin (ID 1) successfully.\n";
?>
