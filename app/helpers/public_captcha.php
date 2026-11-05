<?php

function public_captcha_result_valid($result, string $action, array $config, ?int $now = null): bool
{
    if (!is_array($result) || ($result['success'] ?? null) !== true) {
        return false;
    }
    if (($config['mode'] ?? 'v2') === 'v2') {
        return true;
    }
    $score = $result['score'] ?? null;
    $hostname = $result['hostname'] ?? null;
    $timestamp = $result['challenge_ts'] ?? null;
    if ((!is_int($score) && !is_float($score)) || $score < (float)($config['score'] ?? 0.5) || $score > 1
        || ($result['action'] ?? null) !== $action || !is_string($hostname)
        || !in_array(strtolower($hostname), $config['hosts'] ?? [], true) || !is_string($timestamp)) {
        return false;
    }
    $issued = strtotime($timestamp);
    $now = $now ?? time();
    return $issued !== false && $issued >= $now - 120 && $issued <= $now + 30;
}

function public_captcha_verified(string $secret, string $action, array $config): bool
{
    $token = $_POST['g-recaptcha-response'] ?? null;
    if ($secret === '' || !is_string($token) || trim($token) === '' || strlen($token) > 8192) {
        return false;
    }
    $response = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query(['secret' => $secret, 'response' => trim($token), 'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '']),
            'timeout' => 8,
        ],
    ]));
    return public_captcha_result_valid(is_string($response) ? json_decode($response, true) : null, $action, $config);
}

function public_captcha_widget(string $siteKey, bool $enabled): void
{
    if ($enabled && ($GLOBALS['publicCaptchaConfig']['mode'] ?? 'v2') === 'v2') {
        echo '<div class="g-recaptcha" data-sitekey="' . htmlspecialchars($siteKey, ENT_QUOTES, 'UTF-8') . '"></div>';
    }
}
