<?php
require_once __DIR__ . '/core/db.php';
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "TABLES:\n" . implode("\n", $tables) . "\n\n";

foreach ($tables as $t) {
    if (!in_array($t, ['users', 'sys_roles', 'sys_pages', 'role_access'])) {
        echo "--- $t ---\n";
        print_r($pdo->query("DESCRIBE `$t`")->fetchAll(PDO::FETCH_ASSOC));
    }
}
?>
