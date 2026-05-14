<?php
require_once 'core/db.php';

$setting_key = 'gym_membership_plans';

$plans = [
    [
        'id' => 'PLAN-SILV',
        'name' => 'Silver Plan',
        'price' => '3000',
        'duration' => '30 Days',
        'badge' => 'Basic Access',
        'features' => ['Gym Access', 'Cardio Access'],
        'is_active' => 1,
        'is_popular' => 0
    ],
    [
        'id' => 'PLAN-GOLD',
        'name' => 'Gold Membership',
        'price' => '5000',
        'duration' => '30 Days',
        'badge' => 'Best Value',
        'features' => ['Gym Access', 'Personal Trainer', 'Cardio Access'],
        'is_active' => 1,
        'is_popular' => 1
    ],
    [
        'id' => 'PLAN-PLAT',
        'name' => 'Platinum VIP',
        'price' => '8000',
        'duration' => '30 Days',
        'badge' => 'All Inclusive',
        'features' => ['Gym Access', 'Personal Trainer', 'Diet Plan', 'Cardio Access'],
        'is_active' => 1,
        'is_popular' => 0
    ]
];

$pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?")
    ->execute([$setting_key, json_encode($plans), json_encode($plans)]);

echo "Professional Membership Plans seeded with Silver, Gold, and Platinum demos.\n";
?>
