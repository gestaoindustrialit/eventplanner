<?php

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Event.php';
require_once __DIR__ . '/../app/models/EventSeries.php';
require_once __DIR__ . '/../app/models/Reservation.php';
require_once __DIR__ . '/../app/controllers/BaseController.php';
require_once __DIR__ . '/../app/controllers/PublicSiteController.php';

$path = tempnam(sys_get_temp_dir(), 'event-series-');
$fail = static function (string $message) use ($path): void { @unlink($path); fwrite(STDERR, "FAIL: {$message}\n"); exit(1); };
$assert = static function (bool $condition, string $message) use ($fail): void { if (!$condition) $fail($message); };

try {
    $db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $db->exec((string)file_get_contents(__DIR__ . '/../database/schema.sql'));
    putenv('SQLITE_PATH=' . $path);
    $db = (new Database())->getConnection();
    $events = new EventModel($db);
    $seriesModel = new EventSeries($db);

    // 1/2: an independent legacy event and its stable slug remain available.
    $legacy = $events->find(1);
    $assert($legacy !== null && empty($legacy['series_id']), 'Independent legacy event was changed.');
    $assert((string)$legacy['slug'] !== '', 'Legacy URL slug was not backfilled.');

    // 3: create the generic series.
    $seriesId = $seriesModel->save(['name'=>'Lustre Comedy Club','slug'=>'lustre-comedy-club','location'=>'Lustre Bar — Braga','description'=>'Stand-up comedy em Braga','cover_image_url'=>null,'is_active'=>1]);
    $assert($seriesId > 0, 'Series was not created.');

    $base = ['title'=>'Lustre Comedy Club','series_id'=>$seriesId,'time'=>'22:30','location'=>'Lustre Bar — Braga','client_id'=>1,'is_visible'=>1,'reservations_open'=>1,'reservation_capacity'=>10,'admission_group'=>null,'cachet_total'=>0,'artist_map_link'=>'','artist_details'=>'','external_ticket_url'=>'','poster_url'=>'','notes'=>''];
    $octoberId = $events->create($base + ['slug'=>'lustre-outubro','date'=>'2026-10-02'], []);
    $reservationId = (new Reservation($db))->create(['event_id'=>$octoberId,'customer_name'=>'Cliente','customer_email'=>'cliente@example.test','tickets'=>2,'status'=>'new']);
    // 4: attaching a pre-existing event does not rewrite reservations.
    $db->prepare('UPDATE events SET series_id=:series WHERE id=:id')->execute(['series'=>$seriesId,'id'=>$octoberId]);
    $assert((int)$db->query('SELECT event_id FROM event_reservations WHERE id=' . $reservationId)->fetchColumn() === $octoberId, 'Reservation event_id changed while associating a series.');

    // 5/6: independent November/December sessions are sourced from events.
    $novemberId = $events->create($base + ['slug'=>'lustre-novembro','date'=>'2026-11-06'], []);
    $decemberId = $events->create($base + ['slug'=>'lustre-dezembro','date'=>'2026-12-04'], []);
    $assert(count($seriesModel->sessions($seriesId)) === 3, 'Series does not expose all associated sessions.');

    // 7/8: reservation and capacity are scoped to the selected session ID.
    (new Reservation($db))->create(['event_id'=>$novemberId,'customer_name'=>'Novembro','customer_email'=>'nov@example.test','tickets'=>4,'status'=>'new']);
    $counts = $db->query("SELECT event_id, SUM(tickets) total FROM event_reservations WHERE status != 'cancelled' GROUP BY event_id")->fetchAll(PDO::FETCH_KEY_PAIR);
    $assert((int)$counts[$novemberId] === 4 && (int)$counts[$octoberId] === 2 && !isset($counts[$decemberId]), 'Session capacities/reservations are not independent.');

    // 9: visibility drives the same public filter used by the publisher.
    $events->setVisibility($novemberId, false);
    $visible = $db->query("SELECT id FROM events WHERE series_id={$seriesId} AND is_visible=1 AND date >= date('now')")->fetchAll(PDO::FETCH_COLUMN);
    $assert(!in_array($novemberId, array_map('intval', $visible), true), 'Hidden session is still public.');

    // 10: series persists without future sessions.
    $db->prepare('UPDATE events SET is_visible=0 WHERE series_id=:id')->execute(['id'=>$seriesId]);
    $assert($seriesModel->find($seriesId) !== null, 'Series disappeared without future sessions.');

    // 11: collisions across event and series namespaces are rejected.
    try {
        $seriesModel->save(['name'=>'Duplicada','slug'=>(string)$legacy['slug'],'location'=>null,'description'=>null,'cover_image_url'=>null,'is_active'=>1]);
        $fail('Duplicate slug was accepted.');
    } catch (InvalidArgumentException $expected) {}

    // 12 and route/reservation regression: generated UI includes mobile rules,
    // nested session routing, and posts the concrete event_id.
    $controller = (new ReflectionClass(PublicSiteController::class))->newInstanceWithoutConstructor();
    $publicMethod = new ReflectionMethod(PublicSiteController::class, 'buildPublicIndex');
    $public = $publicMethod->invoke($controller, $path, [], [], '', '', ['enabled'=>true]);
    $htaccessMethod = new ReflectionMethod(PublicSiteController::class, 'buildHtaccess');
    $htaccess = $htaccessMethod->invoke($controller);
    $assert(strpos($public, '@media (max-width: 767px)') !== false, 'Mobile series layout is missing.');
    $assert(strpos($public, 'name="event_id" id="seriesReserveEventId"') !== false, 'Series reservation does not submit event_id.');
    $assert(strpos($htaccess, 'serie=$1&sessao=$2') !== false, 'Individual session route is missing.');
    $assert(strpos($htaccess, 'index.php?serie=$1&evento=$1') !== false, 'Series URL does not prioritize the series selector.');
} catch (Throwable $e) {
    $fail($e->getMessage());
} finally {
    putenv('SQLITE_PATH');
    @unlink($path);
}

fwrite(STDOUT, "Event series scenarios passed (12/12).\n");
