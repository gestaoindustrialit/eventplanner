<?php

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/PublicPage.php';
require_once __DIR__ . '/../app/models/SiteSetting.php';
require_once __DIR__ . '/../app/controllers/BaseController.php';
require_once __DIR__ . '/../app/controllers/PublicPageController.php';
require_once __DIR__ . '/../app/controllers/PublicSiteController.php';

$directory = sys_get_temp_dir() . '/public-pages-' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/test.sqlite';
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    $checks++;
    if (!$condition) { throw new RuntimeException($message); }
};
$run = static function (string $file): string {
    $output = []; $code = 0;
    exec('php ' . escapeshellarg($file) . ' 2>&1', $output, $code);
    if ($code !== 0) { throw new RuntimeException(implode("\n", $output)); }
    return implode("\n", $output);
};

try {
    $db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $db->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    $legacy = $db->query('SELECT * FROM public_pages')->fetchAll();
    putenv('SQLITE_PATH=' . $path);
    $db = (new Database())->getConnection();
    $model = new PublicPage($db);
    public_page_migrate($db);
    public_page_migrate($db);
    foreach ($legacy as $record) {
        $current = $model->find((int)$record['id']);
        foreach ($record as $key=>$value) { $assert($current[$key] === $value, 'Migration changed legacy field ' . $key); }
        $assert($current['menu_location'] === 'main' && (int)$current['allow_indexing'] === 1, 'Incompatible migration defaults.');
    }
    $baselineMenu = public_page_menu($model->allPublished(), true, true, true);
    $assert(array_column($baselineMenu, 'title') === ['Início','Sobre nós','Serviços','Contactos','Corporativos','Blog','Parceiros'], 'Existing menu order changed.');
    $duplicates = $model->allPublished();
    $duplicates[] = ['id'=>999,'slug'=>'parceiros','title'=>'Parceiros','display_mode'=>'section','is_published'=>1];
    $assert(count(array_keys(array_column(public_page_menu($duplicates, true, true, true), 'url'), '/#parceiros', true)) === 1, 'Dynamic/static duplicate in menu.');
    $serviceParent = '';
    foreach ($baselineMenu as $item) { if ($item['url'] === '/#servicos') { $serviceParent = $item['key']; } }
    $assert($serviceParent !== '', 'Services parent missing.');
    $base = ['title'=>'Serviço de teste','slug'=>'servico-teste','excerpt'=>'Resumo do serviço', 'content'=>'<p>Conteúdo <strong>seguro</strong></p>',
        'hero_image_url'=>'','display_mode'=>'page','section_type'=>'default','section_style'=>'card','section_config_json'=>'{}',
        'is_published'=>1,'sort_order'=>1,'menu_location'=>'none','allow_indexing'=>1];
    $model->create($base);
    $id = (int)$db->lastInsertId();
    $data = $model->find($id);
    $url = public_page_url($data);
    $assert($url === '/servico-teste', 'Wrong public URL.');
    $assert(!in_array($url, array_column(public_page_menu($model->allPublished(), true, true, true), 'url'), true), 'Hidden page appears in menu.');
    $data['menu_location'] = 'main'; $model->update($id, $data);
    $menu = public_page_menu($model->allPublished(), true, true, true);
    $assert(count(array_keys(array_column($menu, 'url'), $url, true)) === 1, 'Main page missing or duplicated.');
    $data['menu_location'] = 'submenu'; $data['menu_parent'] = $serviceParent;
    $data['menu_label'] = 'Contratar artista'; $data['menu_order'] = 1; $model->update($id, $data);
    $menu = public_page_menu($model->allPublished(), true, true, true);
    foreach ($menu as $item) { if ($item['key'] === $serviceParent) { $assert($item['children'][0]['url'] === $url && $item['children'][0]['title'] === 'Contratar artista', 'Services submenu incorrect.'); } }
    $assert(public_page_url($model->find($id)) === $url, 'Menu change altered public URL.');
    $data['menu_parent'] = 'static:blog'; $model->update($id, $data);
    $menu = public_page_menu($model->allPublished(), true, true, true);
    foreach ($menu as $item) { if ($item['key'] === 'static:blog') { $assert(count($item['children']) === 1, 'Static menu parent not supported.'); } }
    $data['menu_location'] = 'main'; $model->update($id, $data);
    $assert(in_array($url, array_column(public_page_menu($model->allPublished(), true, true, true), 'url'), true), 'Cannot promote submenu to main.');
    $data['is_published'] = 0; $model->update($id, $data);
    $assert(!in_array($url, array_column(public_page_menu($model->allPublished(), true, true, true), 'url'), true), 'Unpublished page still in menu.');
    $data['is_published'] = 1; $data['menu_location'] = 'submenu'; $data['menu_parent'] = $serviceParent;
    $data['meta_title'] = str_repeat('Título SEO ', 10);
    $data['meta_description'] = str_repeat('Descrição do serviço. ', 12);
    $data['og_image_url'] = 'https://example.com/social.jpg';
    $data['schema_type'] = 'Service'; $data['service_area'] = 'Aveiro';
    $model->update($id, $data);
    try { $model->create($base); $assert(false, 'Duplicate slug accepted.'); } catch (InvalidArgumentException $expected) { $assert(true, 'Duplicate rejected.'); }
    $assert(!$model->slugAvailable('blog') && !$model->slugAvailable('eventos-corporativos'), 'Reserved route accepted.');
    $assert(!public_page_http_url('javascript:alert(1)') && !public_page_http_url('https://user:pass@example.com/') && public_page_http_url('https://example.com/'), 'URL validation failed.');
    $unsafe = '<p onclick="alert(1)">texto</p><a href="javascript:alert(2)" onfocus="alert(3)">mau</a><a href="/blog">bom</a><script>alert(4)</script>';
    $safe = public_page_safe_html($unsafe);
    $assert(strpos($safe, 'onclick') === false && strpos($safe, 'onfocus') === false && strpos($safe, 'javascript:') === false && strpos($safe, '<script') === false && strpos($safe, 'href="/blog"') !== false, 'Unsafe HTML attributes survived.');
    $relatedPage = $base; $relatedPage['slug'] = 'outro-servico'; $relatedPage['title'] = 'Outro serviço'; $model->create($relatedPage);
    $relatedId = (int)$db->lastInsertId();
    $db->exec("INSERT INTO blog_posts (title,slug,content,is_published) VALUES ('Artigo real','artigo-real','texto',1)");
    $blogId = (int)$db->lastInsertId();
    $db->exec("UPDATE events SET date='2099-01-01',is_visible=1 WHERE id=1");
    $data['related_json'] = json_encode(['page:' . $relatedId, 'blog:' . $blogId, 'event:1', 'page:9999']);
    $model->update($id, $data);
    $related = public_page_related($data, $model->allPublished(), $db->query('SELECT * FROM blog_posts')->fetchAll(), $db->query('SELECT * FROM events')->fetchAll());
    $assert(count($related) === 3, 'Related content filtering failed.');
    $db->exec('UPDATE public_pages SET is_published=0 WHERE id=' . $relatedId);
    $related = public_page_related($data, $model->allPublished(), $db->query('SELECT * FROM blog_posts')->fetchAll(), []);
    $assert(count($related) === 1, 'Inactive related content survived.');

    $_SESSION = [];
    $_GET = ['id'=>$id];
    $editor = new PublicPageController($db);
    $validate = new ReflectionMethod(PublicPageController::class, 'validatedData');
    $_POST = $data;
    $_POST['related'] = ['blog:' . $blogId, 'page:99999'];
    $validated = $validate->invoke($editor);
    $assert(json_decode($validated['related_json'], true) === ['blog:' . $blogId], 'Editor accepts nonexistent related content.');
    foreach (['javascript:alert(1)','https://user:pass@example.com/','https://example.com/#fragment'] as $invalid) {
        $_POST['canonical_url'] = $invalid;
        try { $validate->invoke($editor); $assert(false, 'Editor accepted invalid canonical.'); } catch (InvalidArgumentException $expected) { $assert(true, 'Canonical rejected.'); }
    }
    $_POST = $data; $_POST['meta_title'] = ['malformed'];
    try { $validate->invoke($editor); $assert(false, 'Array input accepted as metadata.'); } catch (InvalidArgumentException $expected) { $assert(true, 'Malformed metadata rejected.'); }
    $_POST = $data; $_POST['menu_parent'] = 'page:' . $id;
    try { $validate->invoke($editor); $assert(false, 'Page can be its own parent.'); } catch (InvalidArgumentException $expected) { $assert(true, 'Self-parent rejected.'); }
    $_POST = $data; $_POST['menu_location'] = 'invalid';
    try { $validate->invoke($editor); $assert(false, 'Invalid menu location accepted.'); } catch (InvalidArgumentException $expected) { $assert(true, 'Invalid location rejected.'); }
    $_POST = $data; $_POST['menu_order'] = 'abc';
    try { $validate->invoke($editor); $assert(false, 'Invalid menu order accepted.'); } catch (InvalidArgumentException $expected) { $assert(true, 'Invalid order rejected.'); }
    $assert(public_page_safe_html('<a href="../blog/artigo" title="">artigo</a>') === '<a href="../blog/artigo" title="">artigo</a>', 'Relative HTML links broken.');
    $assert(!public_page_safe_link('//example.com') && !public_page_safe_link("\\\\example.com"), 'Unsafe link origin accepted.');
    $assert(strpos(public_page_css_url("https://example.com/photo')x"), "'") === false, 'Unsafe cover CSS URL.');
    $_POST = []; $_GET = [];

    $controller = (new ReflectionClass(PublicSiteController::class))->newInstanceWithoutConstructor();
    $indexMethod = new ReflectionMethod(PublicSiteController::class, 'buildPublicIndex');
    $index = $indexMethod->invoke($controller, $path, [], [], '', '', ['enabled'=>true]);
    file_put_contents($directory . '/index.php', $index);
    $render = static function (string $slug) use ($directory, $run): string {
        file_put_contents($directory . '/render.php', '<?php $_GET["page"]=' . var_export($slug, true) . '; $_SERVER["REQUEST_URI"]=' . var_export('/' . $slug, true) . '; include __DIR__."/index.php";');
        return $run($directory . '/render.php');
    };
    $html = $render('servico-teste');
    $assert(strpos($html, htmlspecialchars(trim($data['meta_title']))) !== false, 'Meta title truncated or missing.');
    $assert(strpos($html, htmlspecialchars(trim($data['meta_description']))) !== false, 'Description truncated or missing.');
    $assert(strpos($html, '<link rel="canonical" href="https://chorarderir.com/servico-teste">') !== false, 'Canonical missing.');
    $assert(strpos($html, 'property="og:image" content="https://example.com/social.jpg"') !== false && strpos($html, 'name="twitter:card"') !== false, 'Social tags missing.');
    $assert(strpos($html, 'Conteúdos relacionados') !== false && strpos($html, '/blog/artigo-real') !== false && strpos($html, '/outro-servico') === false, 'Rendered related content incorrect.');
    $data['title'] = 'Serviço de teste </script><script>alert("schema-xss")</script>';
    $data['content'] = $unsafe;
    $model->update($id, $data);
    $html = $render('servico-teste');
    $assert(strpos($html, '</script><script>alert("schema-xss")') === false && strpos($html, 'href="javascript:') === false && strpos($html, '<p onclick=') === false, 'Unsafe values in final HTML/JSON-LD.');
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $schemas);
    $types = []; $service = null;
    foreach ($schemas[1] as $json) { $schema = json_decode($json, true); $assert(is_array($schema), 'Invalid JSON-LD.'); $types[] = $schema['@type']; if ($schema['@type'] === 'Service') { $service = $schema; } }
    $assert(count(array_keys($types, 'Organization', true)) === 1 && $service['areaServed'] === 'Aveiro' && isset($service['provider']['@id']) && !isset($service['offers']), 'Service schema missing, duplicated or invented.');
    $sitemapMethod = new ReflectionMethod(PublicSiteController::class, 'buildSitemapGenerator');
    file_put_contents($directory . '/sitemap.php', $sitemapMethod->invoke($controller, $path, ['enabled'=>true]));
    $xml = $run($directory . '/sitemap.php');
    $assert(simplexml_load_string($xml) !== false && substr_count($xml, '<loc>https://chorarderir.com/servico-teste</loc>') === 1, 'Sitemap inclusion/duplicates failed.');
    $assert(strpos($xml, '/sobre-nos</loc>') === false && strpos($xml, '<changefreq>') === false && strpos($xml, '<priority>') === false, 'Sitemap includes section URLs or invented signals.');
    $assert(strpos($xml, gmdate('Y-m-d')) !== false, 'Modification date missing.');
    $data['allow_indexing'] = 0; $model->update($id, $data);
    $assert(strpos($render('servico-teste'), 'content="noindex, follow"') !== false, 'Noindex missing from final HTML.');
    $assert(strpos($run($directory . '/sitemap.php'), '/servico-teste</loc>') === false, 'Noindex page in sitemap.');
    $data['allow_indexing'] = 1; $data['canonical_url'] = 'https://example.com/original'; $model->update($id, $data);
    $assert(strpos($render('servico-teste'), 'rel="canonical" href="https://example.com/original"') !== false, 'Custom canonical not rendered.');
    $assert(strpos($run($directory . '/sitemap.php'), '/servico-teste</loc>') === false, 'Noncanonical URL in sitemap.');
    $data['canonical_url'] = 'https://chorarderir.com/servico-teste/'; $model->update($id, $data);
    $canonicalXml = $run($directory . '/sitemap.php');
    $assert(strpos($canonicalXml, '<loc>https://chorarderir.com/servico-teste/</loc>') !== false && strpos($canonicalXml, '<loc>https://chorarderir.com/servico-teste</loc>') === false, 'Self canonical URL not used by sitemap.');
    $data['canonical_url'] = ''; $data['slug'] = 'servico-novo'; $model->update($id, $data);
    $data['slug'] = 'servico-final'; $model->update($id, $data);
    $assert((int)$db->query('SELECT COUNT(*) FROM public_page_redirects WHERE page_id=' . $id)->fetchColumn() === 2, 'Redirect history lost.');
    $assert(!$model->slugAvailable('servico-teste'), 'Redirect source can be reused.');
    $data['is_published'] = 0; $model->update($id, $data);
    $assert($render('servico-final') === 'Página não encontrada.', 'Unpublished page did not return 404.');
    $assert(strpos($run($directory . '/sitemap.php'), '/servico-final</loc>') === false, 'Draft in sitemap.');
    $data['is_published'] = 1; $model->update($id, $data);
    $assert(strpos($render('servico-final'), 'Serviço de teste') !== false, 'New slug inaccessible.');
    $assert(strpos($index, 'aria-expanded="false"') !== false && strpos($index, "event.key === 'Escape'") !== false && strpos($index, '(min-width:992px)') !== false, 'Keyboard/mobile submenu implementation missing.');
    $section = $base; $section['display_mode'] = 'section'; $section['slug'] = 'setor-antigo';
    $model->create($section); $sectionId = (int)$db->lastInsertId();
    $section['slug'] = 'setor-novo'; $model->update($sectionId, $section);
    $assert($db->query('SELECT old_mode FROM public_page_redirects WHERE page_id=' . $sectionId)->fetchColumn() === 'section', 'Legacy anchor history missing.');
    // Leave a repeatable local-only fixture for browser verification when requested.
    if (getenv('PUBLIC_PAGES_FIXTURE')) {
        $target = getenv('PUBLIC_PAGES_FIXTURE');
        if (!is_dir($target)) { mkdir($target, 0775, true); }
        copy($path, $target . '/test.sqlite');
        file_put_contents($target . '/index.php', $indexMethod->invoke($controller, $target . '/test.sqlite', [], [], '', '', ['enabled'=>true]));
        file_put_contents($target . '/sitemap.php', $sitemapMethod->invoke($controller, $target . '/test.sqlite', ['enabled'=>true]));
    }
    fwrite(STDOUT, 'Public pages navigation/SEO integration passed (' . $checks . " checks).\n");
} finally {
    foreach (glob($directory . '/*') as $file) { unlink($file); }
    rmdir($directory);
}
