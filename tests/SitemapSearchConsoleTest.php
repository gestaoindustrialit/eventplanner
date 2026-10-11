<?php

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/BaseController.php';
require_once __DIR__ . '/../app/controllers/PublicSiteController.php';

$directory = sys_get_temp_dir() . '/sitemap-test-' . bin2hex(random_bytes(6));
mkdir($directory);
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    $checks++;
    if (!$condition) { throw new RuntimeException($message); }
};
try {
    $path = $directory . "/site's.sqlite";
    $db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $db->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    putenv('SQLITE_PATH=' . $path);
    $db = (new Database())->getConnection();
    $db->exec('DELETE FROM events; DELETE FROM event_series; DELETE FROM blog_posts; DELETE FROM public_pages;');
    $db->exec("INSERT INTO public_pages(title,slug,content,display_mode,is_published,allow_indexing,canonical_url,updated_at) VALUES
        ('Serviço','contratar-humorista','texto','page',1,1,'','2026-10-11 12:30:00'),
        ('Rascunho','rascunho','texto','page',0,1,'',''),
        ('Privado','sem-indexacao','texto','page',1,0,'',''),
        ('Cópia','copia','texto','page',1,1,'https://example.com/original',''),
        ('Setor','setor-home','texto','section',1,1,'',''),
        ('Canónica','canonica','texto','page',1,1,'https://chorarderir.com/canonica/','invalid'),
        ('Substitui estática','booking-humoristas','texto','page',1,0,'','')");
    $id = (int)$db->query("SELECT id FROM public_pages WHERE slug='contratar-humorista'")->fetchColumn();
    $db->exec("INSERT INTO public_page_redirects(old_slug,page_id,old_mode) VALUES ('slug-antigo',$id,'page'),('producao-de-eventos',$id,'page')");
    $db->exec("INSERT INTO blog_posts(title,slug,content,is_published,published_at) VALUES
        ('Artigo','artigo--real','texto',1,'2026-10-10 14:15:00'),
        ('Sem data','sem-data','texto',1,NULL),
        ('Data inválida','data-invalida','texto',1,'2026-02-31'),
        ('Privado','artigo-rascunho','texto',0,'2026-10-10')");
    $clientId = (int)$db->query('SELECT id FROM clients LIMIT 1')->fetchColumn();
    $db->exec("INSERT INTO events(title,slug,date,time,location,client_id,is_visible) VALUES
        ('Mesmo título',NULL,'2099-01-01','18:00','Porto',$clientId,1),
        ('Mesmo título',NULL,'2099-01-01','19:00','Porto',$clientId,1),
        ('Oculto','evento-oculto','2099-01-01','20:00','Porto',$clientId,0),
        ('Passado','evento-passado','2000-01-01','20:00','Porto',$clientId,1)");
    $db->exec("INSERT INTO event_series(name,slug,is_active,updated_at) VALUES ('Série','serie-ativa',1,'2026-10-09 11:00:00'),('Inativa','serie-inativa',0,'2026-10-09')");
    $controller = (new ReflectionClass(PublicSiteController::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod($controller, 'buildSitemapGenerator');
    $source = $method->invoke($controller, $path, ['enabled'=>true]);
    file_put_contents($directory . '/sitemap.php', $source);
    $render = static function () use ($directory): string {
        $output = []; $code = 0;
        exec('php -d display_errors=1 ' . escapeshellarg($directory . '/sitemap.php') . ' 2>' . escapeshellarg($directory . '/errors.log'), $output, $code);
        if ($code !== 0) { throw new RuntimeException('Sitemap process failed.'); }
        return implode("\n", $output);
    };
    $xml = $render();
    $assert(strpos($xml, '<?xml version="1.0" encoding="UTF-8"?>') === 0, 'Output before XML declaration.');
    $document = new DOMDocument();
    $assert($document->loadXML($xml), 'Invalid XML.');
    $assert($document->documentElement->namespaceURI === 'http://www.sitemaps.org/schemas/sitemap/0.9', 'Wrong sitemap namespace.');
    $locations = [];
    foreach ($document->getElementsByTagName('loc') as $loc) { $locations[] = $loc->textContent; }
    $assert(count($locations) === count(array_unique($locations)), 'Duplicate URLs.');
    foreach (['/contratar-humorista','/canonica/','/blog/artigo--real','/eventos/mesmo-titulo','/eventos/mesmo-titulo-2','/eventos/serie-ativa'] as $url) {
        $assert(in_array('https://chorarderir.com' . $url, $locations, true), 'Missing public/canonical URL: ' . $url);
    }
    foreach (['rascunho','sem-indexacao','copia','setor-home','slug-antigo','booking-humoristas','producao-de-eventos','artigo-rascunho','evento-oculto','evento-passado','serie-inativa','/admin','?','/blog/artigo-real'] as $fragment) {
        $assert(strpos(implode("\n", $locations), $fragment) === false, 'Non-public, altered or noncanonical URL: ' . $fragment);
    }
    $assert(strpos($xml, '2026-10-10T14:15:00+00:00') !== false, 'SQLite timestamp not normalized.');
    $assert(strpos($xml, '2026-02-31') === false && strpos($xml, '<lastmod>invalid') === false, 'Invalid modification dates emitted.');
    foreach ($document->getElementsByTagName('lastmod') as $date) {
        $assert(preg_match('/^\d{4}-\d{2}-\d{2}(?:T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2})?$/', $date->textContent) === 1, 'Non-W3C date.');
    }
    $rules = (new ReflectionMethod($controller, 'buildHtaccess'))->invoke($controller);
    $robots = (new ReflectionMethod($controller, 'buildRobotsTxt'))->invoke($controller);
    $assert(strpos($rules, 'RewriteRule ^sitemap\\.xml$ sitemap.php [L]') !== false, 'XML route missing.');
    $assert(strpos($robots, "Allow: /\nSitemap: https://chorarderir.com/sitemap.xml") !== false, 'Crawler access or sitemap discovery missing.');
    if (getenv('SITEMAP_FIXTURE')) {
        $target = getenv('SITEMAP_FIXTURE');
        if (!is_dir($target)) { mkdir($target, 0777, true); }
        copy($path, $target . '/test.sqlite');
        file_put_contents($target . '/sitemap.php', $method->invoke($controller, $target . '/test.sqlite', ['enabled'=>true]));
        file_put_contents($target . '/index.php', (new ReflectionMethod($controller, 'buildPublicIndex'))->invoke($controller, $target . '/test.sqlite', [], [], '', '', ['enabled'=>true]));
        file_put_contents($target . '/.htaccess', $rules);
        file_put_contents($target . '/robots.txt', $robots);
    }
    file_put_contents($directory . '/sitemap.php', $method->invoke($controller, $directory . '/missing.sqlite', ['enabled'=>true]));
    $failed = $render();
    $assert(strpos($failed, '<?xml') === 0 && strpos($failed, '<urlset') === false, 'Database failure silently returned a partial sitemap.');
    $assert(!file_exists($directory . '/missing.sqlite'), 'Sitemap created an empty database.');
    $assert(strpos(file_get_contents($directory . '/errors.log'), 'Sitemap generation failed:') !== false, 'Generation failure not logged.');
    fwrite(STDOUT, 'Sitemap Search Console regression passed (' . $checks . " checks).\n");
} finally {
    foreach (glob($directory . '/*') as $file) { unlink($file); }
    rmdir($directory);
}
