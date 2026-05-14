<?php
require_once 'core/db.php';

$exercises = [
    ['name' => 'Barbell Bench Press', 'category' => 'Chest', 'type' => 'Video', 'difficulty' => 'Intermediate', 'duration' => '5:30', 'thumbnail' => 'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=400&h=250&fit=crop', 'desc' => 'Master the king of chest exercises with proper bar path and leg drive.'],
    ['name' => 'Back Squat Pro', 'category' => 'Legs', 'type' => 'Image', 'difficulty' => 'Advanced', 'duration' => 'N/A', 'thumbnail' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=400&h=250&fit=crop', 'desc' => 'High bar vs Low bar: A complete postural breakdown for heavy squatting.'],
    ['name' => 'Deadlift Fundamentals', 'category' => 'Back', 'type' => 'Video', 'difficulty' => 'Beginner', 'duration' => '8:45', 'thumbnail' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=400&h=250&fit=crop', 'desc' => 'Protect your spine. Learn the hip hinge and setup for a perfect deadlift.'],
    ['name' => 'Hanging Leg Raises', 'category' => 'Abs', 'type' => 'Video', 'difficulty' => 'Intermediate', 'duration' => '3:20', 'thumbnail' => 'https://images.unsplash.com/photo-1541534741688-6078c6bfb5c5?w=400&h=250&fit=crop', 'desc' => 'Build rock solid core stability and lower ab definition.'],
    ['name' => 'Dumbbell Lunges', 'category' => 'Legs', 'type' => 'Article', 'difficulty' => 'Beginner', 'duration' => '4 Min Read', 'thumbnail' => 'https://images.unsplash.com/photo-1434608519344-49d77a699e1d?w=400&h=250&fit=crop', 'desc' => 'A guide to single-leg strength and balance training.'],
    ['name' => 'Pull-up Progression', 'category' => 'Back', 'type' => 'Video', 'difficulty' => 'Advanced', 'duration' => '10:00', 'thumbnail' => 'https://images.unsplash.com/photo-1526506118085-60ce8714f8c5?w=400&h=250&fit=crop', 'desc' => 'From zero to ten: The ultimate roadmap to mastering the pull-up.']
];

foreach ($exercises as $e) {
    $pdo->prepare("INSERT INTO exercises (name, category, type, difficulty, duration, thumbnail, description) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute([$e['name'], $e['category'], $e['type'], $e['difficulty'], $e['duration'], $e['thumbnail'], $e['desc']]);
}

echo "Exercise Library seeded with professional tutorials.\n";
?>
