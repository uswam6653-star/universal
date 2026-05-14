<?php
require_once 'core/db.php';
$stmt = $pdo->query("SELECT id, parent_id, page_name, sort_order FROM sys_pages ORDER BY parent_id, sort_order, id");
$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

function buildTree($pages, $parentId = 0, $indent = "") {
    foreach ($pages as $p) {
        if ($p['parent_id'] == $parentId) {
            echo $indent . "- [" . $p['id'] . "] " . $p['page_name'] . " (sort: " . $p['sort_order'] . ")\n";
            buildTree($pages, $p['id'], $indent . "  ");
        }
    }
}
buildTree($pages);
?>
