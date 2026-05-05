<?php
require_once 'core/db.php';
require_once 'core/auth.php';
session_start();

$auth = new Auth($pdo);
// Test Login for Trainer
if($auth->login('ahmad.trainer@gym.com', '12345')) {
    echo "<h1>Login Successful!</h1>";
    echo "Role: " . $_SESSION['role'] . "<br>";
    echo "Target Path: dashboards/trainer/dashboard.php<br>";
    
    $full_path = __DIR__ . "/dashboards/trainer/dashboard.php";
    echo "Full Server Path: $full_path<br>";
    
    if(file_exists($full_path)) {
        echo "✅ File EXISTS on server.<br>";
    } else {
        echo "❌ File NOT found on server.<br>";
    }
    
    echo "<hr>";
    echo "<h3>Click these to test:</h3>";
    echo "<ul>";
    echo "<li><a href='dashboards/trainer/dashboard.php'>1. Link to dashboards/trainer/dashboard.php</a></li>";
    echo "<li><a href='trainer_home.php'>2. Link to trainer_home.php (in root)</a></li>";
    echo "</ul>";
} else {
    echo "Login Failed.";
}
?>
