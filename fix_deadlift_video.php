<?php
require_once 'core/db.php';

// Update Deadlift video with a more reliable link
$pdo->prepare("UPDATE exercises SET media_url = ? WHERE name LIKE '%Deadlift%'")
    ->execute(['https://www.youtube.com/embed/VL5Ab0T07e4']); // Another reliable deadlift guide

echo "Deadlift video link updated successfully.\n";
?>
