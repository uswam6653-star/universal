<?php
$script_name = "/universal/dashboards/trainer/dashboard.php";
$offset = strlen('/universal/');
$current_url = substr($script_name, $offset);
echo "Current URL: $current_url\n";

$db_url = "dashboards/trainer/dashboard.php";
if ($current_url === $db_url) {
    echo "Match Found!\n";
} else {
    echo "No Match!\n";
}
?>
