<?php

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controllers/BaseController.php';
require_once __DIR__ . '/../app/controllers/PublicSiteController.php';

$directory = sys_get_temp_dir() . '/homepage-seo-' . bin2hex(random_bytes(6));
mkdir($directory);
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    $checks++;
    if (!$condition) { throw new RuntimeException($message); }
};
try {
    $path = $directory . '/test.sqlite';
    $db = new PDO('sqlite:' . $path);
    $db->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    $controller = (new ReflectionClass(PublicSiteController::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod($controller, 'buildPublicIndex');
    $render = static function (array $copy, string $page = '') use ($controller, $method, $path, $directory): DOMXPath {
        file_put_contents($directory . '/index.php', $method->invoke($controller, $path, $copy, [], '', '', []));
        file_put_contents($directory . '/render.php', '<?php $_SERVER["REQUEST_URI"]="/"; $_GET["page"]=' . var_export($page, true) . '; require __DIR__."/index.php";');
        $output = []; $code = 0;
        exec('php ' . escapeshellarg($directory . '/render.php') . ' 2>&1', $output, $code);
        if ($code !== 0) { throw new RuntimeException(implode("\n", $output)); }
        $document = new DOMDocument();
        @$document->loadHTML(implode("\n", $output));
        return new DOMXPath($document);
    };
    $title = 'Eventos de Stand-Up Comedy e Contratação de Humoristas | Chorar de Rir';
    $description = 'Produção e booking de stand-up comedy em Portugal. Contrate humoristas para eventos empresariais, festas privadas e noites de comedy club.';
    foreach ([
        ['title'=>$title, 'description'=>$description, 'tagline'=>'Produção • Booking', 'background_url'=>'https://example.com/capa.jpg'],
        ['title'=>'Humor d\'autor "ao vivo" & diversão \\ Portugal', 'description'=>str_repeat('Produção "ao vivo", d\'autor & humor \\ Portugal. ', 6)],
    ] as $copy) {
        $html = $render($copy);
        $assert($html->evaluate('string(//title)') === $copy['title'], 'Exported title differs from configured title.');
        $assert($html->evaluate('string(//meta[@name="description"]/@content)') === $copy['description'], 'Exported description is missing, corrupted or truncated.');
        $assert($html->evaluate('string(//h1[@id="home-title"])') === $copy['title'], 'Homepage H1 did not retain configured text.');
        foreach (['og', 'twitter'] as $prefix) {
            $attribute = $prefix === 'og' ? 'property' : 'name';
            foreach (['title', 'description'] as $field) {
                $assert($html->evaluate('string(//meta[@' . $attribute . '="' . $prefix . ':' . $field . '"]/@content)') === $copy[$field], 'Social metadata differs from configured text.');
            }
        }
        $found = false;
        foreach ($html->query('//script[@type="application/ld+json"]') as $script) {
            $schema = json_decode($script->textContent, true);
            if (($schema['@type'] ?? '') === 'LocalBusiness') {
                $found = ($schema['description'] ?? '') === $copy['description'];
            }
        }
        $assert($found, 'Published JSON-LD description differs from configuration.');
        $assert($html->evaluate('string(//link[@rel="canonical"]/@href)') === 'https://chorarderir.com/', 'Homepage canonical changed.');
    }
    $html = $render([]);
    $assert($html->evaluate('string(//title)') === 'Stand Up Comedy e Eventos de Humor em Portugal | Chorar de Rir', 'Empty settings no longer retain the default SEO title.');
    $assert($html->evaluate('string(//meta[@name="description"]/@content)') === 'Eventos únicos, noites memoráveis e talento nacional num ambiente vibrante.', 'Empty settings no longer retain the default description.');
    $html = $render(['title'=>$title, 'description'=>$description], 'blog');
    $assert($html->evaluate('string(//title)') === 'Blog de Humor, Stand Up Comedy e Eventos | Chorar de Rir', 'Blog title was replaced by homepage settings.');
    fwrite(STDOUT, 'Homepage published SEO passed (' . $checks . " checks).\n");
} finally {
    foreach (glob($directory . '/*') as $file) { unlink($file); }
    rmdir($directory);
}
