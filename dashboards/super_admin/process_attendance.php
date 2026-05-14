<?php
require_once __DIR__ . '/../../core/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    
    $user_id = $data['user_id'] ?? null;
    if (!$user_id) { echo json_encode(['success' => false, 'msg' => 'Invalid ID']); exit; }

    // 1. Check if user exists
    $uStmt = $pdo->prepare("SELECT id, name, identity_no FROM users WHERE id = ? OR registration_no = ?");
    $uStmt->execute([$user_id, $user_id]);
    $user = $uStmt->fetch();

    if (!$user) { echo json_encode(['success' => false, 'msg' => 'Member not found!']); exit; }

    // 2. Check Expiry (Simplification: using index 10 for expiry date if it exists)
    $meta = explode('|', $user['identity_no'] ?? '');
    $expiry = $meta[10] ?? '';
    if ($expiry && strtotime($expiry) < time()) {
        echo json_encode(['success' => false, 'msg' => "Membership EXPIRED on $expiry!"]); exit;
    }

    $today = date('Y-m-d');
    $now = date('Y-m-d H:i:s');

    // 3. Check for open session (IN but not OUT)
    $stmt = $pdo->prepare("SELECT * FROM gym_attendance WHERE user_id = ? AND date = ? AND check_out IS NULL ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user['id'], $today]);
    $open_session = $stmt->fetch();

    if ($open_session) {
        // Prevent duplicate check-out within 2 minutes
        if (strtotime($now) - strtotime($open_session['check_in']) < 120) {
            echo json_encode(['success' => false, 'msg' => 'Already checked in recently!']); exit;
        }
        // Mark OUT
        $upd = $pdo->prepare("UPDATE gym_attendance SET check_out = ?, status = 'OUT' WHERE id = ?");
        $upd->execute([$now, $open_session['id']]);
        $res_status = 'OUT';
    } else {
        // Mark IN
        $ins = $pdo->prepare("INSERT INTO gym_attendance (user_id, check_in, status, date) VALUES (?, ?, 'IN', ?)");
        $ins->execute([$user['id'], $now, $today]);
        $res_status = 'IN';
    }

    echo json_encode([
        'success' => true,
        'msg' => "Attendance marked: $res_status",
        'name' => $user['name'],
        'status' => $res_status,
        'time' => date('h:i A')
    ]);
    exit;
}
?>
