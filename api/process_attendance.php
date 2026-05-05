<?php
require_once '../core/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$identifier = trim($_POST['identifier'] ?? '');
$method = $_POST['method'] ?? 'QR'; // QR or Fingerprint

if (empty($identifier)) {
    echo json_encode(['success' => false, 'message' => 'User identifier required.']);
    exit;
}

// 1. Find User (ID, Email, or Registration No)
$stmt = $pdo->prepare("SELECT id, name, role, avatar FROM users WHERE id = ? OR email = ? OR registration_no = ? LIMIT 1");
$stmt->execute([$identifier, $identifier, $identifier]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found in system.']);
    exit;
}

$uid = $user['id'];
$today = date('Y-m-d');
$now = date('H:i:s');

// 2. Check for Today's Attendance
$check = $pdo->prepare("SELECT id, check_in, check_out FROM gym_attendance WHERE user_id = ? AND date = ?");
$check->execute([$uid, $today]);
$record = $check->fetch();

if ($record) {
    if ($record['check_out']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Attendance already completed for today.',
            'user' => $user,
            'details' => ['in' => $record['check_in'], 'out' => $record['check_out']]
        ]);
    } else {
        // Mark Check-Out
        $pdo->prepare("UPDATE gym_attendance SET check_out = ? WHERE id = ?")->execute([$now, $record['id']]);
        echo json_encode([
            'success' => true, 
            'action' => 'check_out',
            'message' => 'Goodbye, ' . explode(' ', $user['name'])[0] . '! Check-out successful.',
            'user' => $user,
            'time' => $now
        ]);
    }
} else {
    // Mark Check-In
    $pdo->prepare("INSERT INTO gym_attendance (user_id, date, check_in, method) VALUES (?, ?, ?, ?)")->execute([$uid, $today, $now, $method]);
    echo json_encode([
        'success' => true, 
        'action' => 'check_in',
        'message' => 'Welcome, ' . explode(' ', $user['name'])[0] . '! Check-in recorded.',
        'user' => $user,
        'time' => $now
    ]);
}
?>
