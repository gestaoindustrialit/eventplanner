<?php
require_once __DIR__ . '/../app/models/Reservation.php';
require_once __DIR__ . '/../app/controllers/BaseController.php';
require_once __DIR__ . '/../app/controllers/ReservationController.php';
function requireAdmin(): void { $GLOBALS['bulk_auth_checks']++; }
function flash(string $key, ?string $value = null) { $GLOBALS['bulk_flash'][$key] = $value; }
class BulkTestRedirect extends RuntimeException {}
class BulkTestController extends ReservationController {
    protected function redirect(string $url): void { throw new BulkTestRedirect($url); }
}
define('BASE_URL', '/admin/index.php');
$GLOBALS['bulk_auth_checks'] = 0;
$checks = 0;
$assert = static function (bool $ok, string $message) use (&$checks): void { $checks++; if (!$ok) throw new RuntimeException($message); };
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$db->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
$db->exec("INSERT INTO clients(name) VALUES ('Cliente'); INSERT INTO events(title,date,time,location,client_id,is_visible,reservations_open,reservation_capacity) VALUES ('Teste','2099-12-04','22:30','Porto',1,1,1,100)");
$model = new Reservation($db);
$create = static function (string $name, string $status = 'new') use ($model): int { return $model->create(['event_id' => 1, 'customer_name' => $name, 'customer_email' => '', 'tickets' => 2, 'status' => $status]); };
$a = $create('A'); $b = $create('B'); $c = $create('C', 'confirmed'); $other = $create('Outro');
$assert($model->applyBulkAction([$a, (string)$b, $a, $c, 999999], 'confirm') === [$a, $b], 'Confirmation did not target only newly confirmed rows.');
$assert($model->find($other)['status'] === 'new', 'Unselected reservation was modified.');
$assert($model->applyBulkAction([$a, $b, $c], 'confirm') === [], 'Repeated confirmation would resend emails.');
$assert(count($model->applyBulkAction([$a, $b], 'cancel')) === 2, 'Bulk cancellation failed.');
$token = $model->ticketsByReservation($a)[0]['ticket_token'];
$assert($model->validateTicket($token, 1, 1, true)['reason'] === 'cancelled', 'Cancelled ticket remains valid.');
$assert(count($model->ticketsByReservation($a)) === 2, 'Cancellation deleted tickets instead of preserving the record.');
$assert(count($model->applyBulkAction([$a, $b], 'delete')) === 2, 'Bulk deletion failed.');
$assert($model->find($a) === null && $model->ticketsByReservation($a) === [], 'Bulk deletion left reservation/tickets.');
$assert($model->validateTicket($token, 1, 1, true)['reason'] === 'not_found', 'Deleted ticket remains usable.');
$assert(count($model->ticketsByReservation($other)) === 2, 'Unselected tickets were deleted.');
foreach ([[[], 'delete'], [[$other], 'unknown'], [['1 OR 1=1'], 'delete'], [[-1], 'cancel'], [[['array']], 'confirm'], [array_fill(0, 501, $other), 'delete']] as $case) {
    try { $model->applyBulkAction($case[0], $case[1]); throw new RuntimeException('Invalid bulk input accepted.'); } catch (InvalidArgumentException $expected) {}
    $assert($model->find($other) !== null, 'Invalid selection changed a reservation.');
}
// Simulate a database failure halfway through a deletion: tickets and rows must roll back.
$db->exec("CREATE TRIGGER reject_delete BEFORE DELETE ON event_reservations WHEN OLD.id = $other BEGIN SELECT RAISE(ABORT, 'test failure'); END");
try { $model->applyBulkAction([$c, $other], 'delete'); throw new RuntimeException('Expected transaction failure.'); } catch (PDOException $expected) {}
$assert($model->find($c) !== null && count($model->ticketsByReservation($c)) === 2 && count($model->ticketsByReservation($other)) === 2, 'Partial bulk deletion was not rolled back.');
$db->exec('DROP TRIGGER reject_delete');
$controller = new BulkTestController($db);
$_SESSION = ['reservation_bulk_token' => str_repeat('a', 64)];
$_SERVER['REQUEST_METHOD'] = 'GET';
$controller->bulk();
$assert(http_response_code() === 405 && $model->find($other)['status'] === 'new', 'GET request can mutate reservations.');
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['bulk_action' => 'delete', 'reservation_ids' => [$other], 'bulk_token' => 'wrong'];
ob_start(); $controller->bulk(); ob_end_clean();
$assert(http_response_code() === 403 && $model->find($other) !== null, 'CSRF validation failed.');
$_POST = ['bulk_action' => 'cancel', 'reservation_ids' => [$other], 'bulk_token' => $_SESSION['reservation_bulk_token'], 'event_id' => '1', 'admission' => 'pending'];
try { $controller->bulk(); } catch (BulkTestRedirect $redirect) {
    $assert(strpos($redirect->getMessage(), 'event_id=1&admission=pending') !== false, 'Bulk action discarded filters.');
}
$assert($model->find($other)['status'] === 'cancelled', 'Controller cancellation failed.');
$_POST['bulk_action'] = 'confirm';
try { $controller->bulk(); } catch (BulkTestRedirect $expected) {}
$assert($model->find($other)['status'] === 'confirmed' && isset($GLOBALS['bulk_flash']['error']), 'Confirmation did not report email failure separately.');
unset($GLOBALS['bulk_flash']['error']);
try { $controller->bulk(); } catch (BulkTestRedirect $expected) {}
$assert(!isset($GLOBALS['bulk_flash']['error']), 'Already confirmed reservation attempted another email.');
$assert($GLOBALS['bulk_auth_checks'] === 5, 'A bulk route skipped authorization.');
fwrite(STDOUT, "Bulk reservation actions passed ($checks checks).\n");
