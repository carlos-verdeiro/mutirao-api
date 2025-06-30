<?php
$env = parse_ini_file(__DIR__ . '/.env');

define('DB_HOST', $env['DB_HOST']);
define('DB_USER', $env['DB_USER']);
define('DB_PASS', $env['DB_PASS']);
?>