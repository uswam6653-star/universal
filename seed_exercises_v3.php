<?php
require_once 'core/db.php';

$extras = [
    [
        'name' => '6-Pack Abs Circuit', 
        'category' => 'Abs', 
        'type' => 'Video', 
        'difficulty' => 'Intermediate', 
        'duration' => '10:00', 
        'thumbnail' => 'https://images.unsplash.com/photo-1541534741688-6078c6bfb5c5?w=400&h=250&fit=crop', 
        'murl' => 'https://www.youtube.com/embed/2pLT-olgUJs',
        'desc' => 'A high-intensity abs circuit focusing on core stability and definition.'
    ],
    [
        'name' => 'Bicep Peak Training', 
        'category' => 'Arms', 
        'type' => 'Video', 
        'difficulty' => 'Beginner', 
        'duration' => '6:45', 
        'thumbnail' => 'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?w=400&h=250&fit=crop', 
        'murl' => 'https://www.youtube.com/embed/i0W9sW9B0K4',
        'desc' => 'Isolation movements to build the peak and thickness of your biceps.'
    ],
    [
        'name' => 'Full Body HIIT Blast', 
        'category' => 'Full Body', 
        'type' => 'Video', 
        'difficulty' => 'Advanced', 
        'duration' => '20:00', 
        'thumbnail' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=400&h=250&fit=crop', 
        'murl' => 'https://www.youtube.com/embed/5mEIdq-p88w',
        'desc' => 'Burn maximum calories with this total body compound movement routine.'
    ],
    [
        'name' => 'Tricep Extension Masterclass', 
        'category' => 'Arms', 
        'type' => 'Video', 
        'difficulty' => 'Intermediate', 
        'duration' => '7:20', 
        'thumbnail' => 'https://images.unsplash.com/photo-1590239098569-e611bb26ebf1?w=400&h=250&fit=crop', 
        'murl' => 'https://www.youtube.com/embed/6kALZiktrLc',
        'desc' => 'Deep dive into tricep anatomy and the best exercises for overhead extensions.'
    ]
];

foreach ($extras as $e) {
    $pdo->prepare("INSERT INTO exercises (name, category, type, difficulty, duration, thumbnail, media_url, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute([$e['name'], $e['category'], $e['type'], $e['difficulty'], $e['duration'], $e['thumbnail'], $e['murl'], $e['desc']]);
}

echo "Added 4 new resources for Abs, Arms, and Full Body categories.\n";
?>
