<?php
require_once __DIR__ . '/../app/helpers/public_captcha.php';
require_once __DIR__ . '/../app/controllers/BaseController.php';
require_once __DIR__ . '/../app/controllers/PublicSiteController.php';
$checks = 0;
$assert = static function (bool $ok, string $message) use (&$checks): void { $checks++; if (!$ok) throw new RuntimeException($message); };
$config = ['mode' => 'v3', 'score' => 0.5, 'hosts' => ['chorarderir.com', 'www.chorarderir.com']];
$now = time();
$valid = ['success' => true, 'score' => 0.9, 'action' => 'reserve', 'hostname' => 'chorarderir.com', 'challenge_ts' => gmdate('c', $now)];
$assert(public_captcha_result_valid($valid, 'reserve', $config, $now), 'Legitimate v3 result rejected.');
foreach ([['success',false], ['success','true'], ['score',0.1], ['score',1.1], ['score','0.9'], ['action','contact'], ['hostname','evil.test'], ['hostname','chorarderir.com.evil.test'], ['challenge_ts',gmdate('c', $now - 121)], ['challenge_ts',gmdate('c', $now + 31)], ['challenge_ts','bad-date']] as $case) {
    $result = $valid; $result[$case[0]] = $case[1];
    $assert(!public_captcha_result_valid($result, 'reserve', $config, $now), 'Invalid v3 field accepted: ' . $case[0]);
}
foreach (array_keys($valid) as $key) {
    $result = $valid; unset($result[$key]);
    $assert(!public_captcha_result_valid($result, 'reserve', $config, $now), 'Missing v3 field accepted: ' . $key);
}
$assert(!public_captcha_result_valid(null, 'reserve', $config, $now), 'Network/JSON error accepted.');
$assert(!public_captcha_result_valid($valid, 'subscribe', $config, $now), 'Reservation token reused for newsletter.');
$assert(!public_captcha_result_valid($valid, 'contact', $config, $now), 'Reservation token reused for contact.');
$assert(public_captcha_result_valid(['success'=>true], 'reserve', ['mode'=>'v2']), 'Existing v2 mode broke.');
$_POST = ['g-recaptcha-response' => ['array']];
$assert(!public_captcha_verified('test-secret', 'reserve', $config), 'Array token accepted.');
$_POST = ['g-recaptcha-response' => ''];
$assert(!public_captcha_verified('test-secret', 'reserve', $config), 'Empty token accepted.');
$controller = (new ReflectionClass(PublicSiteController::class))->newInstanceWithoutConstructor();
$index = (new ReflectionMethod(PublicSiteController::class, 'buildPublicIndex'))->invoke($controller, '/tmp/unused.sqlite', [], [], '', 'test-site-key', [], $config);
$assert(strpos($index, 'api.js?render=') !== false && strpos($index, 'grecaptcha.execute') !== false, 'Exported index lacks v3 integration.');
$assert(strpos($index, '__RECAPTCHA_V3_JS__') === false && strpos($index, '__PUBLIC_CAPTCHA_SUPPORT__') === false, 'Export placeholders were not resolved.');
foreach (['Reserve'=>'reserve','Contact'=>'contact','Subscribe'=>'subscribe'] as $handler=>$action) {
    $source = (new ReflectionMethod(PublicSiteController::class, 'build' . $handler . 'Handler'))->invoke($controller, '/tmp/unused.sqlite', 'test-secret', $config);
    $assert(strpos($source, "public_captcha_verified(\$captchaSecret, '" . $action . "', \$publicCaptchaConfig)") !== false, 'Wrong expected action for ' . $handler);
    $assert(strpos($source, "'mode' => 'v3'") !== false && strpos($source, 'function public_captcha_result_valid') !== false, 'Standalone handler is missing the v3 verifier.');
}
$GLOBALS['publicCaptchaConfig'] = $config;
ob_start(); public_captcha_widget('test-site-key', true); $widget = ob_get_clean();
$assert($widget === '', 'v3 renders a checkbox.');
fwrite(STDOUT, "Automatic reCAPTCHA v3 passed ($checks checks).\n");
