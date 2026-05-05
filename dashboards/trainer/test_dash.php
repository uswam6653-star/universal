<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "Trainer Dashboard Loaded Successfully - Testing Connection...<br>";
require_once '../../core/db.php';
echo "DB Connected!<br>";
require_once '../../core/session.php';
echo "Session Started! User: " . ($_SESSION['name'] ?? 'Guest') . "<br>";
require_once __DIR__ . '/../../includes/header.php';
?>
<h1>Trainer Dashboard Test</h1>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
