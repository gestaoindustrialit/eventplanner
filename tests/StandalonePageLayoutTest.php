<?php

require_once __DIR__ . '/../app/models/PublicPage.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/BaseController.php';
require_once __DIR__ . '/../app/controllers/PublicSiteController.php';

$directory = sys_get_temp_dir() . '/standalone-layout-' . bin2hex(random_bytes(6));
mkdir($directory);
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    $checks++;
    if (!$condition) { throw new RuntimeException($message); }
};
try {
    $path = $directory . '/test.sqlite';
    $db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $db->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    putenv('SQLITE_PATH=' . $path);
    $db = (new Database())->getConnection();
    $model = new PublicPage($db);
    $editorial = '<h1>Título editorial existente</h1><p>Texto <strong>importante</strong>.</p><h2>Serviços</h2><ul><li>Um</li><li>Dois</li></ul><h3>Detalhes</h3><p><a class="btn btn-primary unsafe" href="/#contactos" onclick="alert(1)">Pedir proposta</a></p><p><a href="/blog">Artigos</a></p>';
    $data = ['title'=>'Página de serviço','slug'=>'pagina-layout','excerpt'=>'Resumo editorial preservado.',
        'content'=>$editorial,'hero_image_url'=>'https://example.com/banner.png','display_mode'=>'page',
        'section_type'=>'default','section_style'=>'card','sort_order'=>1,'is_published'=>1,
        'section_config_json'=>json_encode(['cta_text'=>'Texto CTA do gestor.','cta_button_text'=>'Pedir proposta']),
        'meta_title'=>'SEO existente','meta_description'=>'Descrição SEO existente'];
    $model->create($data);
    $id = (int)$db->lastInsertId();
    $controller = (new ReflectionClass(PublicSiteController::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod($controller, 'buildPublicIndex');
    $index = $method->invoke($controller, $path, [], ['enabled'=>true,'text'=>'Destaque global'], '', '', ['enabled'=>true]);
    file_put_contents($directory . '/index.php', $index);
    file_put_contents($directory . '/render.php', '<?php $_GET["page"]="pagina-layout"; $_SERVER["REQUEST_URI"]="/pagina-layout"; require __DIR__."/index.php";');
    $render = static function () use ($directory): string {
        $output = []; $code = 0;
        exec('php ' . escapeshellarg($directory . '/render.php') . ' 2>&1', $output, $code);
        if ($code !== 0) { throw new RuntimeException(implode("\n", $output)); }
        return implode("\n", $output);
    };
    $html = $render();
    $assert(substr_count($html, '<h1') === 1, 'Standalone page has more than one H1.');
    $assert(strpos($html, '<h2>Título editorial existente</h2>') !== false, 'Editorial H1 was not demoted without losing its text.');
    $assert(strpos($html, '<ul><li>Um</li><li>Dois</li></ul>') !== false && strpos($html, '<h3>Detalhes</h3>') !== false, 'Simple editorial markup changed.');
    $assert(strpos($html, '<a href="/#contactos" class="btn btn-brand page-cta">Pedir proposta</a>') !== false, 'Editorial CTA did not receive brand classes.');
    $assert(substr_count($html, '>Pedir proposta</a>') === 1 && strpos($html, '<aside class="standalone-cta"') === false, 'Configured CTA duplicates an editorial CTA.');
    $assert(strpos($html, 'onclick="alert') === false && strpos($html, 'class="unsafe') === false, 'Unsafe attributes survived.');
    $assert(strpos($html, '<a href="/blog">Artigos</a>') !== false, 'Ordinary internal links turned into buttons.');
    $assert(strpos($html, '<title>SEO existente</title>') !== false && strpos($html, 'content="Descrição SEO existente"') !== false, 'SEO changed.');
    $assert(strpos($html, 'rel="canonical" href="https://chorarderir.com/pagina-layout"') !== false, 'Public URL changed.');
    $assert(preg_match('#</nav>\s*<div class="standalone-hero">#', $html) === 1, 'Hero must be immediately after main navigation, before the optional global banner.');
    $assert(strpos($html, '<div class="standalone-hero">') < strpos($html, '<article class="standalone-page">'), 'Cover remains inside the article container.');
    $assert(strpos($html, 'width:100%; height:auto; object-fit:contain') !== false, 'Banner can crop or distort the image.');
    $assert($model->find($id)['content'] === $editorial, 'Stored editorial content was changed.');
    $assert(strpos(public_page_safe_html('<h1>Existing</h1><a class="btn" href="/blog">Blog</a>'), '<h1>Existing</h1><a href="/blog">Blog</a>') !== false, 'Default sanitization changed on other templates.');
    $assert(strpos(public_page_safe_html('<a class="btn btn-outline-primary" href="/blog">Ler</a>', true), 'btn-outline-brand page-cta') !== false, 'Outline CTA was not mapped to brand styles.');
    $assert(strpos(public_page_safe_html('<a href="tel:+351210000000">Ligar</a>', true), 'page-cta') !== false, 'Telephone CTA not supported.');
    $assert(strpos(public_page_safe_html('<a class="btn" href="javascript:alert(1)">Mau</a>', true), 'href=') === false, 'Unsafe button URL accepted.');
    $data['content'] = '<h2>Conteúdo sem botão</h2><p>Texto preservado.</p>';
    $model->update($id, $data);
    $html = $render();
    $assert(substr_count($html, '>Pedir proposta</a>') === 1 && strpos($html, 'Texto CTA do gestor.') !== false, 'Configured CTA is not reused when editorial CTA is absent.');
    $data['content'] = '<p><a href="/pedido">Pedir proposta</a></p>';
    $model->update($id, $data);
    $html = $render();
    $assert(substr_count($html, '>Pedir proposta</a>') === 1 && strpos($html, 'href="/pedido" class="btn btn-brand page-cta"') !== false, 'Configured CTA label did not identify the existing editorial button.');
    $data['hero_image_url'] = ''; $data['content'] = '<p>Sem capa.</p>'; $data['section_config_json'] = '{}';
    $model->update($id, $data);
    $html = $render();
    $assert(strpos($html, '<div class="standalone-hero">') === false && substr_count($html, '<h1') === 1, 'Page without a cover does not render correctly.');
    $assert(strpos($html, '<aside class="standalone-cta"') === false, 'Unconfigured CTA was invented.');
    fwrite(STDOUT, 'Standalone page layout passed (' . $checks . " checks).\n");
} finally {
    foreach (glob($directory . '/*') as $file) { unlink($file); }
    rmdir($directory);
}
