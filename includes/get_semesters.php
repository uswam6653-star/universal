<?php
require_once '../core/db.php';
header('Content-Type: application/json');

if (isset($_GET['program_id'])) {
    $stmt = $pdo->prepare("SELECT id, name FROM semesters WHERE program_id = ? ORDER BY number");
    $stmt->execute([$_GET['program_id']]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
else {
    echo json_encode([]);
}
?>
