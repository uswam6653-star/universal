<?php
require_once 'core/db.php';
$stmt = $pdo->query("SELECT id, parent_id, page_name FROM sys_pages");
$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$loop_found = false;
foreach ($pages as $p) {
    if ($p['id'] == $p['parent_id'] && $p['id'] != 0) {
        echo "Direct Loop Found: Page ID " . $p['id'] . " (" . $p['page_name'] . ") has itself as parent.\n";
        $loop_found = true;
    }
}

// Check for multi-level loops
foreach ($pages as $p) {
    $current = $p;
    $visited = [$current['id']];
    while ($current['parent_id'] != 0) {
        $parent_id = $current['parent_id'];
        if (in_array($parent_id, $visited)) {
            echo "Circular Reference Found: " . implode(' -> ', $visited) . " -> $parent_id\n";
            $loop_found = true;
            break;
        }
        $visited[] = $parent_id;
        $found_parent = false;
        foreach ($pages as $search) {
            if ($search['id'] == $parent_id) {
                $current = $search;
                $found_parent = true;
                break;
            }
        }
        if (!$found_parent) break;
    }
}

if (!$loop_found) echo "No circular references found in sys_pages.\n";
?>
