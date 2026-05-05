<?php
require_once 'core/db.php';

$queries = [
    // 1. Programs (Gym Plans)
    "CREATE TABLE IF NOT EXISTS programs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        code VARCHAR(20)
    )",
    "INSERT INTO programs (name, code) SELECT 'General Membership', 'GYM-GEN' WHERE NOT EXISTS (SELECT 1 FROM programs)",
    
    // 2. Semesters (Durations)
    "CREATE TABLE IF NOT EXISTS semesters (
        id INT AUTO_INCREMENT PRIMARY KEY,
        program_id INT,
        name VARCHAR(50),
        number INT
    )",
    "INSERT INTO semesters (program_id, name, number) SELECT 1, 'Monthly', 1 WHERE NOT EXISTS (SELECT 1 FROM semesters)",

    // 3. Invoices
    "CREATE TABLE IF NOT EXISTS invoices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        semester_id INT,
        total_base_amount DECIMAL(10,2),
        discount_amount DECIMAL(10,2) DEFAULT 0,
        fine_amount DECIMAL(10,2) DEFAULT 0,
        payable_amount DECIMAL(10,2),
        balance_due DECIMAL(10,2),
        status VARCHAR(20) DEFAULT 'Unpaid',
        due_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // 4. Payments
    "CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_id INT,
        amount DECIMAL(10,2),
        payment_method VARCHAR(50),
        transaction_id VARCHAR(100),
        status VARCHAR(20) DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // 5. Fee Structures
    "CREATE TABLE IF NOT EXISTS fee_structures (
        id INT AUTO_INCREMENT PRIMARY KEY,
        semester_id INT,
        fee_type VARCHAR(100),
        amount DECIMAL(10,2)
    )"
];

echo "Starting Global Database Repair...\n";
foreach ($queries as $q) {
    try {
        $pdo->exec($q);
        echo "✅ Success: " . substr($q, 0, 40) . "...\n";
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}
echo "Global Repair Complete. All crashing tables have been restored.\n";
?>
