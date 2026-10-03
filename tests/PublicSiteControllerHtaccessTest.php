<?php

require_once __DIR__ . '/../app/controllers/BaseController.php';
require_once __DIR__ . '/../app/controllers/PublicSiteController.php';

$controller = (new ReflectionClass(PublicSiteController::class))->newInstanceWithoutConstructor();
$method = new ReflectionMethod(PublicSiteController::class, 'buildHtaccess');
$htaccess = $method->invoke($controller);
$publicIndexMethod = new ReflectionMethod(PublicSiteController::class, 'buildPublicIndex');
$publicIndex = $publicIndexMethod->invoke(
    $controller,
    '/tmp/eventplanner-test.sqlite',
    [],
    [],
    '',
    '',
    ['enabled' => true]
);

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
};

$assert(
    !str_contains($htaccess, 'RewriteCond %{QUERY_STRING}'),
    'The generated rules must not redirect an internally rewritten query string.'
);
$assert(
    str_contains($htaccess, 'index.php?page=$1 [L,QSA]'),
    'The clean public page route must still be rewritten to index.php.'
);
$assert(
    str_contains($publicIndex, "header('Location: ' . \$cleanLocation, true, 301);"),
    'index.php must continue to redirect direct legacy query-string requests.'
);

fwrite(STDOUT, "Public-site rewrite regression test passed.\n");
