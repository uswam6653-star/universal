<?php
require_once 'core/db.php';
$stmt = $pdo->query("SELECT id, page_name, page_url FROM sys_pages");
foreach ($stmt->fetchAll() as $r) {
    echo "{$r['id']} | {$r['page_name']} | {$r['page_url']}\n";
}
?>
