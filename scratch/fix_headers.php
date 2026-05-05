<?php
$files = [
    'dashboards/trainer/manage_members.php',
    'dashboards/trainer/dashboard.php',
    'dashboards/super_admin/inventory.php',
    'dashboards/super_admin/attendance.php',
    'dashboards/super_admin/assign_students.php',
    'dashboards/student/member_portal.php'
];

foreach ($files as $file) {
    $path = "e:/xampp/htdocs/universal/" . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $newContent = str_replace("require_once '../../includes/header.php';", "require_once '../../includes/footer.php';", $content, $count);
        
        // Wait, str_replace will replace ALL occurrences. 
        // I only want to replace the one at the end if it exists.
        // Actually, since header.php is usually at the top (line 2), 
        // if I find it twice, I should replace the last one.
        
        $lines = explode("\n", $content);
        $foundHeader = false;
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            if (strpos($lines[$i], "require_once '../../includes/header.php';") !== false) {
                // If we find it and it's not the first one (near top)
                if ($i > 5) { 
                    $lines[$i] = str_replace("header.php", "footer.php", $lines[$i]);
                    echo "Fixed $file at line " . ($i + 1) . "\n";
                    break; 
                }
            }
        }
        file_put_contents($path, implode("\n", $lines));
    }
}
?>
