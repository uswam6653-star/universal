<?php
echo "<h1>Full Project Scan</h1>";
echo "Current Root: " . __DIR__ . "<br>";

function scan($dir) {
    $files = scandir($dir);
    echo "<ul>";
    foreach($files as $file) {
        if($file == '.' || $file == '..') continue;
        $path = $dir . '/' . $file;
        echo "<li>" . (is_dir($path) ? "<b>[DIR]</b> " : "") . $file;
        if(is_dir($path) && $file != 'assets' && $file != 'uploads') {
            scan($path);
        }
        echo "</li>";
    }
    echo "</ul>";
}

scan(__DIR__);
?>
