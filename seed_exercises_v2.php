<?php
require_once 'core/db.php';

// Truncate and re-seed with media URLs
$pdo->query("TRUNCATE TABLE exercises");

$exercises = [
    [
        'name' => 'Perfect Bench Press Form', 
        'category' => 'Chest', 
        'type' => 'Video', 
        'difficulty' => 'Intermediate', 
        'duration' => '5:30', 
        'thumbnail' => 'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=400&h=250&fit=crop', 
        'murl' => 'https://www.youtube.com/embed/vthMCtgVtFw',
        'desc' => 'Detailed breakdown of the bench press including grip width and arching.'
    ],
    [
        'name' => 'How to Squat Properly', 
        'category' => 'Legs', 
        'type' => 'Video', 
        'difficulty' => 'Beginner', 
        'duration' => '8:15', 
        'thumbnail' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=400&h=250&fit=crop', 
        'murl' => 'https://www.youtube.com/embed/gcNh17Ckjgg',
        'desc' => 'Avoid knee pain and maximize leg growth with these squatting tips.'
    ],
    [
        'name' => 'Deadlift Masterclass', 
        'category' => 'Back', 
        'type' => 'Video', 
        'difficulty' => 'Advanced', 
        'duration' => '12:00', 
        'thumbnail' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=400&h=250&fit=crop', 
        'murl' => 'https://www.youtube.com/embed/Xs78Z-4Z_bI',
        'desc' => 'Comprehensive guide to the conventional deadlift.'
    ]
];

foreach ($exercises as $e) {
    $pdo->prepare("INSERT INTO exercises (name, category, type, difficulty, duration, thumbnail, media_url, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute([$e['name'], $e['category'], $e['type'], $e['difficulty'], $e['duration'], $e['thumbnail'], $e['murl'], $e['desc']]);
}

echo "Exercise Library updated with REAL PLAYABLE VIDEOS.\n";
?>
