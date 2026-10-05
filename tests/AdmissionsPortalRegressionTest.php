<?php

require_once __DIR__ . '/../app/models/Reservation.php';
require_once __DIR__ . '/../app/controllers/BaseController.php';
require_once __DIR__ . '/../app/controllers/ReservationController.php';

if (!method_exists(ReservationController::class, 'exportAdmissions')) {
    throw new RuntimeException('Admissions export action is not available to the application router.');
}
$controllerSource = (string)file_get_contents(__DIR__ . '/../app/controllers/ReservationController.php');
$menuSource = (string)file_get_contents(__DIR__ . '/../app/views/partials/header.php');
$portalHeaderSource = (string)file_get_contents(__DIR__ . '/../app/views/partials/admissions_header.php');
$authSource = (string)file_get_contents(__DIR__ . '/../includes/auth.php');
$userFormSource = (string)file_get_contents(__DIR__ . '/../app/views/users/form.php');
$routerSource = (string)file_get_contents(__DIR__ . '/../public/index.php');
if (strpos($controllerSource, "'admissions'") === false) {
    throw new RuntimeException('Admissions does not use its standalone layout.');
}
if (strpos($menuSource, "['Reservas','journal-check'") === false
    || strpos($menuSource, 'target="_blank"') === false
    || strpos($menuSource, '>Admissões ') === false) {
    throw new RuntimeException('Reservations and admissions menu entries are not separated.');
}
if (strpos($portalHeaderSource, 'sidebar') !== false
    || strpos($portalHeaderSource, 'action=logout&amp;portal=admissions') === false) {
    throw new RuntimeException('Standalone admissions navigation is invalid.');
}
if (strpos($authSource, "'admissions' => 'Apenas página de admissões'") === false
    || strpos($userFormSource, 'Abre diretamente o portal') === false
    || strpos($routerSource, 'isAdmissionsOnly()') === false) {
    throw new RuntimeException('The admissions-only profile option is not fully wired.');
}
$path = tempnam(sys_get_temp_dir(), 'admissions-');
try {
    $db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $db->exec((string)file_get_contents(__DIR__ . '/../database/schema.sql'));
    $db->exec("INSERT INTO event_reservations (event_id, customer_name, customer_email, tickets, status) VALUES (1, 'Teste', 'teste@example.test', 2, 'confirmed')");
    $model = new Reservation($db);
    $rows = $model->admissionsEventOverview();
    if (count($rows) !== 1 || (int)$rows[0]['active_tickets'] !== 2 || (int)$rows[0]['admitted_tickets'] !== 0) {
        throw new RuntimeException('Admissions overview totals are invalid.');
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . "\n");
    @unlink($path);
    exit(1);
}
@unlink($path);
fwrite(STDOUT, "Admissions portal regression test passed.\n");
