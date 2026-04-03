<?php
session_start();

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$modelFiles = glob(__DIR__ . '/../app/models/*.php') ?: [];
sort($modelFiles);
foreach ($modelFiles as $file) {
    require_once $file;
}

require_once __DIR__ . '/../app/controllers/BaseController.php';
$controllerFiles = glob(__DIR__ . '/../app/controllers/*.php') ?: [];
sort($controllerFiles);
foreach ($controllerFiles as $file) {
    if (basename($file) === 'BaseController.php') {
        continue;
    }
    require_once $file;
}

$db = (new Database())->getConnection();

$controllerName = strtolower($_GET['controller'] ?? 'dashboard');
$actionName = $_GET['action'] ?? 'index';

$map = [
    'auth' => AuthController::class,
    'dashboard' => DashboardController::class,
    'comedian' => ComedianController::class,
    'client' => ClientController::class,
    'event' => EventController::class,
    'reservation' => ReservationController::class,
    'publicsite' => PublicSiteController::class,
    'comedianarea' => ComedianAreaController::class,
];

if (!isset($map[$controllerName])) {
    http_response_code(404);
    echo 'Controller não encontrado.';
    exit;
}

$controller = new $map[$controllerName]($db);
if (!method_exists($controller, $actionName)) {
    http_response_code(404);
    echo 'Ação não encontrada.';
    exit;
}

$controller->$actionName();
