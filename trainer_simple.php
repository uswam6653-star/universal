<?php
session_start();
// Pure self-contained file
$host = 'localhost';
$db   = 'universal_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Connection failed: " . $e->getMessage());
}

if(!isset($_SESSION['user_id'])) {
    die("Please login first.");
}

echo "<h1>Self-Contained Trainer Dashboard</h1>";
echo "Welcome, " . $_SESSION['name'] . "<br>";
echo "Role: " . $_SESSION['role'] . "<br>";

$stmt = $pdo->prepare("SELECT count(*) FROM users WHERE role = 'student'");
$stmt->execute();
echo "Total Members in System: " . $stmt->fetchColumn() . "<br>";

echo "<hr><p>If you see this, then your server is working perfectly. The issue is with the include files (header/footer/sidebar).</p>";
?>
