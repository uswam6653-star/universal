<?php
require_once 'core/db.php';
$members = $pdo->query("SELECT id, identity_no FROM users WHERE role = 'student'")->fetchAll();
foreach ($members as $m) {
    $meta = array_pad(explode('|', $m['identity_no'] ?? ''), 11, '');
    $meta[2] = rand(18, 45); // Age
    $meta[3] = (['Weight Loss', 'Muscle Gain', 'Endurance', 'Flexibility'])[rand(0,3)]; // Goal
    $new_meta = implode('|', $meta);
    $pdo->prepare("UPDATE users SET identity_no = ? WHERE id = ?")->execute([$new_meta, $m['id']]);
}
echo "Age and Goal seeded for all members.\n";
?>
