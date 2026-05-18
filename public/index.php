<?php
session_start();

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$helperFiles = glob(__DIR__ . '/../app/helpers/*.php') ?: [];
sort($helperFiles);
foreach ($helperFiles as $file) {
    require_once $file;
}

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

try {
    $db = (new Database())->getConnection();
} catch (Throwable $e) {
    http_response_code(500);
    $errorMessage = 'Erro ao ligar à base de dados SQLite. '
        . 'Confirma permissões de escrita e executa /install.php. '
        . 'Detalhe: ' . $e->getMessage();
    echo nl2br(htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'));
    exit;
}

$controllerName = strtolower($_GET['controller'] ?? 'dashboard');
$actionName = $_GET['action'] ?? 'index';


if ($controllerName === 'presscontact' && !isAdmin()) {
    http_response_code(404);
    echo 'Página não encontrada.';
    exit;
}

$map = [
    'auth' => AuthController::class,
    'dashboard' => DashboardController::class,
    'comedian' => ComedianController::class,
    'client' => ClientController::class,
    'crm' => CrmController::class,
    'event' => EventController::class,
    'reservation' => ReservationController::class,
    'publicsite' => PublicSiteController::class,
    'publicpage' => PublicPageController::class,
    'comedianarea' => ComedianAreaController::class,
    'newsletter' => NewsletterController::class,
    'presscontact' => PressContactController::class,
    'partner' => PartnerController::class,
    'checklist' => ChecklistController::class,
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
