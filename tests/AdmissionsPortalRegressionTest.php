<?php

require_once __DIR__ . '/../app/models/Reservation.php';
require_once __DIR__ . '/../app/controllers/BaseController.php';
require_once __DIR__ . '/../app/controllers/ReservationController.php';

if (!method_exists(ReservationController::class, 'exportAdmissions')) {
    throw new RuntimeException('Admissions export action is not available to the application router.');
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
