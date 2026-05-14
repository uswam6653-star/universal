<?php
require_once 'core/db.php';

$templates = [
    ['name' => 'Fat Loss Challenge', 'level' => 'Beginner', 'duration' => '4 Weeks', 'exercises' => "1. Treadmill Run: 20 mins\n2. Bodyweight Squats: 3x15\n3. Pushups: 3x10\n4. Plank: 3x45sec"],
    ['name' => 'Muscle Gain Pro', 'level' => 'Intermediate', 'duration' => '8 Weeks', 'exercises' => "1. Bench Press: 4x10\n2. Deadlifts: 4x8\n3. Bicep Curls: 3x12\n4. Shoulder Press: 3x10"],
    ['name' => 'Athlete Strength', 'level' => 'Advanced', 'duration' => '12 Weeks', 'exercises' => "1. Squat (Heavy): 5x5\n2. Weighted Pullups: 4x8\n3. Clean & Jerk: 5x3\n4. Core Circuit: 4 Rounds"]
];

$uid = 1; // Admin/Trainer ID

foreach ($templates as $t) {
    $pdo->prepare("INSERT INTO workout_templates (name, level, duration, exercises, created_by) VALUES (?, ?, ?, ?, ?)")
        ->execute([$t['name'], $t['level'], $t['duration'], $t['exercises'], $uid]);
}

echo "Workout templates seeded successfully.\n";
?>
