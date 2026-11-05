<?php
require_once __DIR__ . '/../app/models/SiteSetting.php';
require_once __DIR__ . '/../app/controllers/BaseController.php';
require_once __DIR__ . '/../app/controllers/PublicSiteController.php';
require_once __DIR__ . '/../app/config/database.php';

function requireAdmin(): void {}
function flash(string $key, ?string $value = null) { $GLOBALS['captcha_test_flash'][$key] = $value; }
class CaptchaTestRedirect extends RuntimeException {}
class CaptchaPublishTestController extends PublicSiteController {
    protected function redirect(string $url): void { throw new CaptchaTestRedirect($url); }
}
define('BASE_URL', '/admin/index.php');
define('PUBLIC_SITE_DEFAULT_PATH', '/not-used');
$directory = sys_get_temp_dir() . '/captcha-settings-' . bin2hex(random_bytes(6));
mkdir($directory);
$checks = 0;
$assert = static function (bool $ok, string $message) use (&$checks): void { $checks++; if (!$ok) throw new RuntimeException($message); };
try {
    $path = $directory . '/db.sqlite';
    $db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $db->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    $settings = new SiteSetting($db);
    $site = '6L_test_site_' . str_repeat('a', 28);
    $secret = '6L_test_secret_' . str_repeat('b', 28);
    $settings->saveRecaptchaKeys(['recaptcha_site_key' => ' ' . $site . ' ', 'recaptcha_secret_key' => ' ' . $secret . ' ']);
    $assert($settings->get('recaptcha_site_key') === $site && $settings->get('recaptcha_secret_key') === $secret, 'Key pair was not persisted.');
    $settings = new SiteSetting($db); // Simulate a fresh page/request.
    foreach ([[], ['recaptcha_secret_key' => ''], ['recaptcha_site_key' => '', 'recaptcha_secret_key' => '   '], ['recaptcha_site_key' => $site, 'recaptcha_secret_key' => $secret]] as $post) {
        $keys = $settings->saveRecaptchaKeys($post);
        $assert($keys === ['site' => $site, 'secret' => $secret], 'Empty, missing or repeated input erased saved keys.');
    }
    foreach ([['recaptcha_site_key' => 'different_' . str_repeat('x', 30)], ['recaptcha_secret_key' => ['array']], ['recaptcha_secret_key' => 'invalid key with spaces']] as $post) {
        try { $settings->saveRecaptchaKeys($post); throw new RuntimeException('Invalid key update accepted.'); }
        catch (InvalidArgumentException $expected) {}
        $assert($settings->get('recaptcha_site_key') === $site && $settings->get('recaptcha_secret_key') === $secret, 'Failed update damaged the pair.');
    }
    $replacement = '6L_new_secret_' . str_repeat('c', 28);
    $settings->saveRecaptchaKeys(['recaptcha_secret_key' => $replacement]);
    $assert($settings->get('recaptcha_secret_key') === $replacement, 'Secret replacement failed.');
    putenv('SQLITE_PATH=' . $path);
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = ['target_path' => $directory . '/published', 'recaptcha_site_key' => $site, 'recaptcha_secret_key' => ''];
    try { (new CaptchaPublishTestController($db))->publish(); } catch (CaptchaTestRedirect $expected) {}
    $assert(isset($GLOBALS['captcha_test_flash']['success']), 'Publishing with a blank secret did not succeed.');
    $assert($settings->get('recaptcha_secret_key') === $replacement, 'Publish erased the saved secret.');
    $index = file_get_contents($directory . '/published/index.php');
    $handler = file_get_contents($directory . '/published/reserve.php');
    $assert(strpos($index, $site) !== false, 'Saved site key was not exported.');
    $assert(strpos($handler, $replacement) !== false, 'Saved secret was not exported into the handler.');
    $assert(strpos($index, $replacement) === false, 'Private key leaked into the public HTML template.');
    $view = file_get_contents(__DIR__ . '/../app/views/public_site/index.php');
    $assert(strpos($view, 'type="password" name="recaptcha_secret_key"') !== false && strpos($view, 'htmlspecialchars($recaptchaSecretKey') === false, 'Admin page exposes the secret value.');
    fwrite(STDOUT, "CAPTCHA persistence and publishing passed ($checks checks).\n");
} finally {
    putenv('SQLITE_PATH');
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $file) { $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname()); }
    rmdir($directory);
}
