<?php
/**
 * Project Seed Script - Populates Demo Data
 * ACCESS VIA BROWSER: http://localhost/universal/core/seed_project.php
 */
require_once __DIR__ . '/db.php';

echo "<body style='font-family: sans-serif; padding: 20px; line-height: 1.6;'>";
echo "<h2 style='color: #007bff;'>Project Demo Data Seeder</h2>";
echo "Initializing database...<br>";

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $tables = ['installments', 'payments', 'invoices', 'user_scholarships', 'scholarships', 'fee_structures', 'semesters', 'programs'];
    foreach ($tables as $t) {
        $pdo->exec("TRUNCATE TABLE $t;");
    }
    $pdo->exec("DELETE FROM users WHERE role != 'super_admin';");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    $pdo->beginTransaction();

    // 1. Programs
    $stmt = $pdo->prepare("INSERT INTO programs (name, code) VALUES (:name, :code)");
    $stmt->execute([':name' => 'Computer Science', ':code' => 'BSCS']);
    $bscs_id = $pdo->lastInsertId();

    // 2. Semesters
    $sem_stmt = $pdo->prepare("INSERT INTO semesters (program_id, name, number, status) VALUES (:pid, :name, :num, 'active')");
    $sem_stmt->execute([':pid' => $bscs_id, ':name' => 'Fall 2024', ':num' => 1]);
    $sem1_id = $pdo->lastInsertId();

    // 3. Fee Structures
    $fee_stmt = $pdo->prepare("INSERT INTO fee_structures (semester_id, base_fee, lab_charges, library_fee, hostel_fee, late_fine_per_day) VALUES (:sid, :base, :lab, :lib, :hostel, :fine)");
    $fee_stmt->execute([':sid' => $sem1_id, ':base' => 45000, ':lab' => 5000, ':lib' => 2000, ':hostel' => 15000, ':fine' => 500]);

    // 4. Student
    $pass = password_hash('student123', PASSWORD_DEFAULT);
    $stu_stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, registration_no, roll_no, program_id, semester_id, scholarship_percent) VALUES (:name, :email, :pass, 'student', :reg, :roll, :pid, :sid, :scholar)");
    $stu_stmt->execute([
        ':name' => 'Ali Raza',
        ':email' => 'ali@student.com',
        ':pass' => $pass,
        ':reg' => 'CS-24-101',
        ':roll' => 'CS-24-101',
        ':pid' => $bscs_id,
        ':sid' => $sem1_id,
        ':scholar' => 0.00
    ]);
    $stu_id = $pdo->lastInsertId();

    // 5. Invoice
    $total = 45000 + 5000 + 2000 + 15000;
    $inv_stmt = $pdo->prepare("INSERT INTO invoices (user_id, semester_id, total_base_amount, discount_amount, payable_amount, balance_due, status) VALUES (:uid, :sid, :base, 0, :total, :total, 'unpaid')");
    $inv_stmt->execute([':uid' => $stu_id, ':sid' => $sem1_id, ':base' => $total, ':total' => $total]);

    // 6. Faculty
    $fac_pass = password_hash('faculty123', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES ('Finance Officer', 'finance@sys.com', :pass, 'finance')")->execute([':pass' => $fac_pass]);
    $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES ('HOD Admin', 'hod@sys.com', :pass, 'hod')")->execute([':pass' => $fac_pass]);
    $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES ('Verification Clerk', 'clerk@sys.com', :pass, 'clerk')")->execute([':pass' => $fac_pass]);

    $pdo->commit();
    echo "<br><b style='color:green;'>SUCCESS! Demo data is ready.</b>";
}
catch (Exception $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    echo "<b style='color:red;'>ERROR:</b> " . $e->getMessage();
}
?>
