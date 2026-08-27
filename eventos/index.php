<?php
$applicationPath = dirname(dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/index.php')));
$_SERVER['SCRIPT_NAME'] = rtrim(str_replace('\\', '/', $applicationPath), '/') . '/index.php';
$_GET['controller'] = 'reservation';
$_GET['action'] = 'eventos';
require dirname(__DIR__) . '/public/index.php';
