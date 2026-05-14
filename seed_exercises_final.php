<?php
require_once 'core/db.php';

// Wipe and re-seed all to be sure
$pdo->query("TRUNCATE TABLE exercises");

$all_exercises = [
    ['name' => 'Perfect Bench Press Form', 'category' => 'Chest', 'type' => 'Video', 'difficulty' => 'Intermediate', 'duration' => '5:30', 'thumbnail' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=400&h=250&fit=crop', 'murl' => 'https://www.youtube.com/embed/vthMCtgVtFw', 'desc' => 'Detailed breakdown of the bench press including grip width and arching.'],
    ['name' => 'How to Squat Properly', 'category' => 'Legs', 'type' => 'Video', 'difficulty' => 'Beginner', 'duration' => '8:15', 'thumbnail' => 'https://images.unsplash.com/photo-1574680096145-d05b474e2155?w=400&h=250&fit=crop', 'murl' => 'https://www.youtube.com/embed/gcNh17Ckjgg', 'desc' => 'Avoid knee pain and maximize leg growth with these squatting tips.'],
    ['name' => 'Deadlift Masterclass', 'category' => 'Back', 'type' => 'Video', 'difficulty' => 'Advanced', 'duration' => '12:00', 'thumbnail' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=400&h=250&fit=crop', 'murl' => 'https://www.youtube.com/embed/VL5Ab0T07e4', 'desc' => 'Comprehensive guide to the conventional deadlift.'],
    ['name' => '6-Pack Abs Circuit', 'category' => 'Abs', 'type' => 'Video', 'difficulty' => 'Intermediate', 'duration' => '10:00', 'thumbnail' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=400&h=250&fit=crop', 'murl' => 'https://www.youtube.com/embed/2pLT-olgUJs', 'desc' => 'A high-intensity abs circuit focusing on core stability and definition.'],
    ['name' => 'Ab Roller Technique', 'category' => 'Abs', 'type' => 'Image', 'difficulty' => 'Advanced', 'duration' => 'N/A', 'thumbnail' => 'https://images.unsplash.com/photo-1541534741688-6078c6bfb5c5?w=400&h=250&fit=crop', 'murl' => '', 'desc' => 'Master the ab roller for insane core strength without back pain.'],
    ['name' => 'Bicep Peak Training', 'category' => 'Arms', 'type' => 'Video', 'difficulty' => 'Beginner', 'duration' => '6:45', 'thumbnail' => 'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?w=400&h=250&fit=crop', 'murl' => 'https://www.youtube.com/embed/i0W9sW9B0K4', 'desc' => 'Isolation movements to build the peak and thickness of your biceps.'],
    ['name' => 'Hammer Curls for Forearms', 'category' => 'Arms', 'type' => 'Video', 'difficulty' => 'Beginner', 'duration' => '5:00', 'thumbnail' => 'https://images.unsplash.com/photo-1593079831268-3381b0db4a77?w=400&h=250&fit=crop', 'murl' => 'https://www.youtube.com/embed/7jqi2qWAUzQ', 'desc' => 'Improve grip strength and forearm size with proper hammer curl form.'],
    ['name' => 'Full Body HIIT Blast', 'category' => 'Full Body', 'type' => 'Video', 'difficulty' => 'Advanced', 'duration' => '20:00', 'thumbnail' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=400&h=250&fit=crop', 'murl' => 'https://www.youtube.com/embed/5mEIdq-p88w', 'desc' => 'Burn maximum calories with this total body compound movement routine.'],
    ['name' => 'Yoga for Full Body', 'category' => 'Full Body', 'type' => 'Video', 'difficulty' => 'Beginner', 'duration' => '15:00', 'thumbnail' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=400&h=250&fit=crop', 'murl' => 'https://www.youtube.com/embed/v7AYKMP6rOE', 'desc' => 'A relaxing full body yoga flow for recovery and flexibility.']
];

foreach ($all_exercises as $e) {
    $pdo->prepare("INSERT INTO exercises (name, category, type, difficulty, duration, thumbnail, media_url, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute([$e['name'], $e['category'], $e['type'], $e['difficulty'], $e['duration'], $e['thumbnail'], $e['murl'], $e['desc']]);
}

echo "Exercise Library FULLY UPDATED with 9 professional resources for ALL categories.\n";
?>
