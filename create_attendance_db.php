<?php
require_once 'core/db.php';
$sql = "CREATE TABLE IF NOT EXISTS gym_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    check_in TIME NOT NULL,
    check_out TIME DEFAULT NULL,
    status ENUM('Present', 'Late', 'Absent') DEFAULT 'Present',
    method ENUM('Manual', 'QR', 'Fingerprint') DEFAULT 'Manual',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
try {
    $pdo->exec($sql);
    echo "SUCCESS: gym_attendance table created.\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
