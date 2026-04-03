<?php

define('APP_NAME', 'StandUp Event Planner');
define('BASE_URL', '/public/index.php');
define('DEFAULT_TIMEZONE', 'UTC');

date_default_timezone_set(DEFAULT_TIMEZONE);

define('PUBLIC_SITE_DEFAULT_PATH', getenv('PUBLIC_SITE_PATH') ?: dirname(__DIR__, 3) . '/chorarderir.com');
