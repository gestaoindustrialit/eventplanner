<?php

define('APP_NAME', 'StandUp Event Planner');
define('DEFAULT_TIMEZONE', 'UTC');

date_default_timezone_set(DEFAULT_TIMEZONE);

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$scriptDir = str_replace('\\', '/', dirname($scriptName));
$normalizedDir = $scriptDir === '.' ? '' : rtrim($scriptDir, '/');
$endsWithPublic = substr($normalizedDir, -7) === '/public';
if ($endsWithPublic) {
    $normalizedDir = substr($normalizedDir, 0, -7);
}

$computedBasePath = $normalizedDir . '/index.php';
define('BASE_URL', getenv('BASE_URL') ?: $computedBasePath);

define('PUBLIC_SITE_DEFAULT_PATH', getenv('PUBLIC_SITE_PATH') ?: dirname(__DIR__, 3) . '/chorarderir.com');
