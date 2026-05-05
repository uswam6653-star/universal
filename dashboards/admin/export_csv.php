<?php

require_once '../../core/session.php';


// Basic Security Check
if (!in_array($_SESSION['role'], ['super_admin', 'hod'])) {
    die('Unauthorized');
}

$status = $_GET['status'] ?? 'all';
$query = "SELECT c.id, u.name as student, u.roll_no, cat.name as category, d.name as department, c.subject, c.status, c.priority, c.created_at 
          FROM complaints c 
          JOIN users u ON c.user_id = u.id 
          JOIN complaint_categories cat ON c.category_id = cat.id 
          LEFT JOIN departments d ON c.department_id = d.id";

if ($status !== 'all') {
    $query .= " WHERE c.status = " . $pdo->quote($status);
}

$stmt = $pdo->query($query);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Send headers to download file rather than display
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=complaints_report_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// Set CSV header row
if (!empty($data)) {
    fputcsv($output, array_keys($data[0]));
}

// Loop through data and write to CSV
foreach ($data as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>
