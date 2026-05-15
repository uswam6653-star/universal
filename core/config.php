<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'universal_db');
define('DB_USER', 'root');
define('DB_PASS', '');
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$detected_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']);
$detected_url = preg_replace('/dashboards\/.*$/', '', $detected_url);
define('BASE_URL', rtrim($detected_url, '/') . '/'); 

define('APP_ROOT', dirname(__DIR__));
?>