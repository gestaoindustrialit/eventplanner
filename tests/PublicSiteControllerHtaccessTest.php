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

$dynamicRoute = 'RewriteRule ^([a-z0-9-]+)/?$ index.php?page=$1 [L,QSA]';
$assert(
    str_contains($htaccess, "RewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\n" . $dynamicRoute),
    'New public page slugs and old redirect slugs must reach index.php without intercepting real files or directories.'
);
$assert(
    strpos($htaccess, $dynamicRoute) > strpos($htaccess, 'RewriteRule ^blog/'),
    'The generic page route must follow the existing event and blog routes.'
);

foreach (['contratar-humorista', 'servico-antigo', 'pagina-nova/'] as $path) {
    $matches = [];
    $assert(preg_match('#^([a-z0-9-]+)/?$#', $path, $matches) === 1, 'Dynamic slug is not routable: ' . $path);
    $assert($matches[1] === rtrim($path, '/'), 'The route must preserve the slug: ' . $path);
}
foreach (['index.php', 'reserve.php', 'assets/site.css', 'blog/artigo', 'eventos/sessao'] as $path) {
    $assert(preg_match('#^([a-z0-9-]+)/?$#', $path) === 0, 'Generic route intercepts an existing handler or nested route: ' . $path);
}

fwrite(STDOUT, "Public-site rewrite regression test passed.\n");
