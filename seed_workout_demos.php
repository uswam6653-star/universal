<?php
require_once 'core/db.php';

// Seed some professional workout templates
$pdo->query("TRUNCATE TABLE workout_templates");

$templates = [
    [
        'name' => 'Beginner Fat Loss',
        'level' => 'Beginner',
        'duration' => '4 Weeks',
        'exercises' => "Treadmill Walk: 15 mins\nBodyweight Squats: 3x15\nPushups (Knees): 3x10\nPlank: 3x30 seconds\nJumping Jacks: 3x30",
        'created_by' => 1
    ],
    [
        'name' => 'Hypertrophy Muscle Gain',
        'level' => 'Intermediate',
        'duration' => '8 Weeks',
        'exercises' => "Bench Press: 4x10\nBarbell Squats: 4x10\nDeadlifts: 3x8\nOverhead Press: 3x12\nBicep Curls: 3x15",
        'created_by' => 1
    ],
    [
        'name' => 'Strength & Power Pro',
        'level' => 'Advanced',
        'duration' => '12 Weeks',
        'exercises' => "Heavy Squats: 5x5\nHeavy Bench: 5x5\nConventional Deadlift: 1x5 (Top Set)\nWeighted Pullups: 3x8\nFace Pulls: 4x15",
        'created_by' => 1
    ],
    [
        'name' => 'Yoga & Flexibility',
        'level' => 'Beginner',
        'duration' => 'Indefinite',
        'exercises' => "Sun Salutation: 5 sets\nDownward Dog: 1 min\nWarrior Pose: 1 min each side\nChild Pose: 2 mins recovery",
        'created_by' => 1
    ]
];

foreach ($templates as $t) {
    $pdo->prepare("INSERT INTO workout_templates (name, level, duration, exercises, created_by) VALUES (?, ?, ?, ?, ?)")
        ->execute([$t['name'], $t['level'], $t['duration'], $t['exercises'], $t['created_by']]);
}

echo "Workout Plan Templates seeded with professional demo data.\n";
?>
