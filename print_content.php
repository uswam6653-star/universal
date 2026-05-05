<?php
$file = 'dashboards/super_admin/manage_users.php';
if (file_exists($file)) {
    echo "--- CONTENT OF $file ---\n";
    $content = file_get_contents($file);
    echo substr($content, 0, 1000) . "...\n"; // Print first 1000 chars
    if (strpos($content, 'encodeMetadata') !== false) {
        echo "\n[CHECK] encodeMetadata function FOUND in file.\n";
    } else {
        echo "\n[CHECK] encodeMetadata function NOT FOUND in file.\n";
    }
} else {
    echo "File $file not found in " . getcwd();
}
?>
