<?php
echo "Current Root: " . __DIR__ . "<br>";
echo "Checking Trainer Dashboard Path...<br>";
$path = __DIR__ . "/dashboards/trainer/dashboard.php";
if (file_exists($path)) {
    echo "✅ File Exists at: $path<br>";
    echo "<a href='dashboards/trainer/dashboard.php'>Click here to go to Trainer Dashboard</a>";
} else {
    echo "❌ File NOT found at: $path<br>";
    echo "Checking parent directory...<br>";
    print_r(scandir(__DIR__ . "/dashboards"));
}
?>
