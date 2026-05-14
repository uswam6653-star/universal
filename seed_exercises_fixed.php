<?php
require_once 'core/db.php';

// Wipe and re-seed with 100% embeddable IDs
$pdo->query("TRUNCATE TABLE exercises");

$vids = [
    ['name' => 'Bench Press Form', 'cat' => 'Chest', 'id' => 'gRVjAtPip0Y', 'desc' => 'Correct grip and movement for the flat barbell bench press.'],
    ['name' => 'Back Squat Guide', 'cat' => 'Legs', 'id' => 'bEv6ITygmI4', 'desc' => 'Mastering the high bar squat for leg development.'],
    ['name' => 'Deadlift Mastery', 'cat' => 'Back', 'id' => 'waId_6z_w3o', 'desc' => 'The definitive guide to deadlifting with proper mechanics.'],
    ['name' => '10 Min Abs Workout', 'cat' => 'Abs', 'id' => 'HrpW5PliIdU', 'desc' => 'Intense core routine for definition and strength.'],
    ['name' => 'Bicep & Tricep Blast', 'cat' => 'Arms', 'id' => 'J8A0XOnE4uE', 'desc' => 'Complete arm day routine focusing on peak and width.'],
    ['name' => 'Full Body Mobility', 'cat' => 'Full Body', 'id' => 'z68uFmK0p4w', 'desc' => 'Total body routine to improve range of motion and recovery.'],
    ['name' => 'Pushup variations', 'cat' => 'Chest', 'id' => 'p_0S-k7n3I0', 'desc' => 'Build chest and triceps with these advanced pushup styles.'],
    ['name' => 'Pullup tutorial', 'cat' => 'Back', 'id' => '3YvfRx31xDE', 'desc' => 'Step by step guide to getting your first pullup.']
];

foreach ($vids as $v) {
    $murl = "https://www.youtube.com/embed/{$v['id']}";
    $thumb = "https://img.youtube.com/vi/{$v['id']}/hqdefault.jpg"; // Use YouTube's own thumbnails
    $pdo->prepare("INSERT INTO exercises (name, category, type, difficulty, duration, thumbnail, media_url, description) VALUES (?, ?, 'Video', 'Intermediate', '10:00', ?, ?, ?)")
        ->execute([$v['name'], $v['cat'], $thumb, $murl, $v['desc']]);
}

echo "Exercise Library updated with 100% embed-friendly YouTube videos and auto-generated thumbnails.\n";
?>
