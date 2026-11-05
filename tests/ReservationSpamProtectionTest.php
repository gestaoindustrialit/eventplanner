<?php

require_once __DIR__ . '/../app/controllers/BaseController.php';
require_once __DIR__ . '/../app/controllers/PublicSiteController.php';
require_once __DIR__ . '/../app/models/Reservation.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/reservation_security.php';

$directory = sys_get_temp_dir() . '/reservation-spam-' . bin2hex(random_bytes(6));
mkdir($directory);
$server = null;
$checks = 0;
$assert = static function (bool $value, string $message) use (&$checks): void {
    $checks++;
    if (!$value) { throw new RuntimeException($message); }
};
try {
    $dbPath = $directory . '/test.sqlite';
    $db = new PDO('sqlite:' . $dbPath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $db->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    putenv('SQLITE_PATH=' . $dbPath);
    $db = (new Database())->getConnection();
    $db->exec("INSERT INTO clients (name) VALUES ('Teste');");
    $db->exec("INSERT INTO events (title,date,time,location,client_id,is_visible,reservations_open,reservation_capacity) VALUES ('Teste','2099-12-04','22:30','Porto',1,1,1,30)");
    $eventId = (int)$db->lastInsertId();
    $controller = (new ReflectionClass(PublicSiteController::class))->newInstanceWithoutConstructor();
    $index = (new ReflectionMethod(PublicSiteController::class, 'buildPublicIndex'))->invoke($controller, $dbPath, [], [], '', 'test-site-key', []);
    file_put_contents($directory . '/index.php', $index);
    $handler = new ReflectionMethod(PublicSiteController::class, 'buildReserveHandler');
    $reserve = $handler->invoke($controller, $dbPath, 'test-secret');
    file_put_contents($directory . '/reserve.php', $reserve);
    file_put_contents($directory . '/unconfigured.php', $handler->invoke($controller, $dbPath, ''));
    // Local test fixture only: emulate Google's response without any network or email.
    $mock = <<<'MOCK'
class CaptchaTestStream {
    public $context;
    private $position = 0;
    private $body = '';
    public function stream_open($path, $mode, $options, &$opened_path) {
        $this->body = json_encode(['success' => ($_POST['g-recaptcha-response'] ?? '') === 'valid-captcha']);
        return true;
    }
    public function stream_read($count) { $value = substr($this->body, $this->position, $count); $this->position += strlen($value); return $value; }
    public function stream_eof() { return $this->position >= strlen($this->body); }
    public function stream_stat() { return []; }
}
stream_wrapper_unregister('https');
stream_wrapper_register('https', CaptchaTestStream::class);
MOCK;
    file_put_contents($directory . '/reserve.php', str_replace('<?php', "<?php\n" . $mock . "\n", $reserve));
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    $server = proc_open([PHP_BINARY, '-c', php_ini_loaded_file(), '-d', 'sendmail_path=/bin/true', '-S', $address, '-t', $directory], [0 => ['pipe', 'r'], 1 => ['file', $directory . '/server.log', 'a'], 2 => ['file', $directory . '/server.log', 'a']], $pipes);
    for ($i = 0; $i < 50; $i++) {
        $connection = @stream_socket_client('tcp://' . $address, $errno, $error, .1);
        if ($connection) { fclose($connection); break; }
        usleep(20000);
    }
    $cookie = '';
    $request = static function (string $path, ?array $post = null) use ($address, &$cookie): array {
        $headers = $cookie !== '' ? 'Cookie: ' . $cookie . "\r\n" : '';
        if ($post !== null) { $headers .= "Content-Type: application/x-www-form-urlencoded\r\n"; }
        $context = stream_context_create(['http' => ['method' => $post === null ? 'GET' : 'POST', 'header' => $headers, 'content' => $post === null ? '' : http_build_query($post), 'follow_location' => 0, 'ignore_errors' => true, 'timeout' => 5]]);
        $body = file_get_contents('http://' . $address . $path, false, $context);
        $location = '';
        foreach ($http_response_header as $header) {
            if (stripos($header, 'Set-Cookie: ') === 0) { $cookie = explode(';', substr($header, 12))[0]; }
            if (stripos($header, 'Location: ') === 0) { $location = substr($header, 10); }
        }
        return ['body' => $body, 'location' => $location, 'headers' => $http_response_header];
    };
    $form = static function () use ($request, $eventId): array {
        $page = $request('/index.php');
        if (!preg_match('/name="reservation_token" value="([a-f0-9]{64})"/', $page['body'], $match)) {
            throw new RuntimeException('Exported public form does not render a session token. ' . substr($page['body'], 0, 350));
        }
        return ['reservation_token' => $match[1], 'event_id' => (string)$eventId, 'customer_name' => 'João D’Ávila', 'customer_email' => 'joao@example.test', 'customer_phone' => '+351 912345678', 'tickets' => '2', 'gdpr_consent' => '1', 'g-recaptcha-response' => 'valid-captcha'];
    };
    $post = $form();
    $bad = $post; unset($bad['reservation_token']);
    $assert(strpos($request('/reserve.php', $bad)['location'], 'msg=security') !== false, 'Direct POST accepted without token.');
    $bad = $post; $bad['reservation_website'] = 'spam';
    $assert(strpos($request('/reserve.php', $bad)['location'], 'msg=security') !== false, 'Honeypot was ignored.');
    foreach (['customer_email' => 'bad-email', 'customer_name' => '*** 💎 Casino bonus', 'customer_phone' => 'xzwhk', 'tickets' => '67', 'notes' => str_repeat('x', 2001)] as $key => $value) {
        $bad = $form(); $bad[$key] = $value;
        $assert(strpos($request('/reserve.php', $bad)['location'], 'msg=invalid') !== false, 'Invalid field accepted: ' . $key);
    }
    $bad = $form(); $bad['customer_email'] = ['array'];
    $assert(strpos($request('/reserve.php', $bad)['location'], 'msg=invalid') !== false, 'Array input accepted.');
    $assert(strpos($request('/unconfigured.php', $form())['location'], 'msg=captcha_unavailable') !== false, 'Missing CAPTCHA configuration failed open.');
    foreach (['', 'invalid-captcha'] as $captcha) {
        $bad = $form(); $bad['g-recaptcha-response'] = $captcha;
        $assert(strpos($request('/reserve.php', $bad)['location'], 'msg=captcha#') !== false, 'Invalid CAPTCHA accepted.');
    }
    $assert((int)$db->query('SELECT COUNT(*) FROM event_reservations')->fetchColumn() === 0, 'Rejected requests created reservations.');
    $db->exec('DELETE FROM reservation_attempts');
    $post = $form();
    $assert(strpos($request('/reserve.php', $post)['location'], 'msg=ok') !== false, 'Legitimate public reservation failed.');
    $assert((int)$db->query('SELECT COUNT(*) FROM event_reservation_tickets')->fetchColumn() === 2, 'Valid reservation ticket count is incorrect.');
    $assert(strpos($request('/reserve.php', $post)['location'], 'msg=security') !== false, 'Replayed POST accepted.');
    $id = (int)$db->query('SELECT id FROM event_reservations LIMIT 1')->fetchColumn();
    $reservationModel = new Reservation($db);
    $reservationModel->delete($id);
    $assert((int)$db->query('SELECT COUNT(*) FROM event_reservations')->fetchColumn() === 0, 'Delete recreated a reservation.');
    $assert((int)$db->query('SELECT COUNT(*) FROM event_reservation_tickets')->fetchColumn() === 0, 'Deleted reservation left usable tickets.');
    for ($i = 0; $i < 4; $i++) {
        $post = $form(); $post['customer_email'] = 'person' . $i . '@example.test';
        $assert(strpos($request('/reserve.php', $post)['location'], 'msg=ok') !== false, 'Allowed reservation was throttled early.');
    }
    $cookie = ''; // Resetting the browser session must not reset the IP limit.
    $post = $form(); $post['customer_email'] = 'different@example.test';
    $assert(strpos($request('/reserve.php', $post)['location'], 'msg=rate') !== false, 'New session or deletion bypassed rate limit.');
    $db->exec('DELETE FROM reservation_attempts');
    $db->exec('UPDATE events SET reservations_open=0');
    $assert(strpos($request('/reserve.php', $form())['location'], 'msg=closed') !== false, 'Closed event accepted a reservation.');
    $db->exec('UPDATE events SET reservations_open=1,reservation_capacity=8');
    $assert(strpos($request('/reserve.php', $form())['location'], 'msg=soldout') !== false, 'Sold-out event accepted a reservation.');
    $db->exec('DELETE FROM reservation_attempts');
    for ($i = 0; $i < 5; $i++) { $assert(reservation_rate_allowed($db, 'ip-' . $i, 'same@example.test'), 'Email limit too restrictive.'); }
    $assert(!reservation_rate_allowed($db, 'new-ip', 'SAME@example.test'), 'IP rotation bypassed email limit.');
    $db->exec('UPDATE reservation_attempts SET attempted_at = attempted_at - 3601');
    $assert(reservation_rate_allowed($db, 'new-ip', 'same@example.test'), 'Expired rate window did not reset.');
    $v3config = ['mode'=>'v3', 'score'=>0.5, 'hosts'=>['chorarderir.com']];
    $v3source = $handler->invoke($controller, $dbPath, 'test-secret', $v3config);
    $v3mock = str_replace("json_encode(['success' => (\$_POST['g-recaptcha-response'] ?? '') === 'valid-captcha'])", "json_encode(['success'=>true, 'score'=>(float)(\$_POST['_test_score'] ?? 0.9), 'action'=>\$_POST['_test_action'] ?? 'reserve', 'hostname'=>\$_POST['_test_host'] ?? 'chorarderir.com', 'challenge_ts'=>gmdate('c', time() - (int)(\$_POST['_test_age'] ?? 0))])", $mock);
    file_put_contents($directory . '/reserve-v3.php', str_replace('<?php', "<?php\n" . $v3mock . "\n", $v3source));
    $db->exec('UPDATE events SET reservation_capacity=100,reservations_open=1; DELETE FROM reservation_attempts');
    foreach (['_test_score'=>'0.1', '_test_action'=>'contact', '_test_host'=>'evil.test', '_test_age'=>'180'] as $key=>$value) {
        $db->exec('DELETE FROM reservation_attempts');
        $bad = $form(); $bad[$key] = $value;
        $assert(strpos($request('/reserve-v3.php', $bad)['location'], 'msg=captcha#') !== false, 'Exported v3 handler accepted invalid result: ' . $key);
    }
    $db->exec('DELETE FROM reservation_attempts');
    $assert(strpos($request('/reserve-v3.php', $form())['location'], 'msg=ok') !== false, 'Valid automatic v3 reservation failed.');
    fwrite(STDOUT, "Reservation spam protection passed ($checks checks).\n");
} finally {
    putenv('SQLITE_PATH');
    if (is_resource($server)) { proc_terminate($server); proc_close($server); }
    foreach (glob($directory . '/*') as $file) { unlink($file); }
    rmdir($directory);
}
