<?php

// Shared by the panel and embedded in the exported website: no new runtime dependency.
function public_page_url(array $page): string
{
    return (($page['display_mode'] ?? 'section') === 'page' ? '/' : '/#') . $page['slug'];
}

function public_page_http_url(string $url): bool
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false
        && in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
        && !parse_url($url, PHP_URL_USER) && !parse_url($url, PHP_URL_PASS)
        && !preg_match('/[\x00-\x20\x7f]/', $url);
}

function public_page_menu(array $pages, bool $agenda, bool $partners, bool $corporate): array
{
    $items = [['key'=>'static:inicio', 'title'=>'Início', 'url'=>'/#inicio', 'order'=>null]];
    $sections = [];
    $standalone = [];
    foreach ($pages as $page) {
        if (empty($page['is_published']) || ($page['menu_location'] ?? 'main') !== 'main') { continue; }
        if ($page['slug'] === 'agenda' && !$agenda) { continue; }
        if ($page['slug'] === 'eventos-corporativos') { continue; }
        $item = ['key'=>'page:' . $page['id'], 'title'=>trim((string)($page['menu_label'] ?? '')) ?: $page['title'],
            'url'=>public_page_url($page), 'order'=>$page['menu_order'] ?? null];
        if (($page['display_mode'] ?? 'section') === 'page') { $standalone[] = $item; }
        else { $sections[] = $item; }
    }
    $items = array_merge($items, $sections, $standalone);
    if ($corporate) { $items[] = ['key'=>'static:corporativos','title'=>'Corporativos','url'=>'/eventos-corporativos/','order'=>null]; }
    $items[] = ['key'=>'static:blog','title'=>'Blog','url'=>'/blog','order'=>null];
    if ($partners) { $items[] = ['key'=>'static:parceiros','title'=>'Parceiros','url'=>'/#parceiros','order'=>null]; }
    $staticUrls = [];
    foreach ($items as $item) { if (strpos($item['key'], 'static:') === 0) { $staticUrls[$item['url']] = true; } }
    $unique = [];
    $items = array_values(array_filter($items, static function (array $item) use ($staticUrls, &$unique): bool {
        if (strpos($item['key'], 'page:') === 0 && isset($staticUrls[$item['url']])) { return false; }
        if (isset($unique[$item['url']])) { return false; }
        $unique[$item['url']] = true;
        return true;
    }));
    foreach ($items as $index => &$item) { $item['position'] = $index; $item['children'] = []; }
    unset($item);
    usort($items, static function (array $a, array $b): int {
        $comparison = ($a['order'] ?? $a['position']) <=> ($b['order'] ?? $b['position']);
        return $comparison ?: ($a['position'] <=> $b['position']);
    });
    $seen = [];
    foreach ($items as $item) { $seen[$item['url']] = true; }
    $children = array_filter($pages, static function (array $page): bool {
        return !empty($page['is_published']) && ($page['menu_location'] ?? '') === 'submenu';
    });
    usort($children, static function (array $a, array $b): int {
        return ((int)($a['menu_order'] ?? 0) <=> (int)($b['menu_order'] ?? 0)) ?: strcmp($a['title'], $b['title']);
    });
    foreach ($children as $page) {
        $url = public_page_url($page);
        if (isset($seen[$url])) { continue; }
        foreach ($items as &$item) {
            if ($item['key'] === ($page['menu_parent'] ?? '')) {
                $item['children'][] = ['title'=>trim((string)($page['menu_label'] ?? '')) ?: $page['title'], 'url'=>$url];
                $seen[$url] = true;
                break;
            }
        }
        unset($item);
    }
    return $items;
}

function public_page_related(array $page, array $pages, array $posts, array $events): array
{
    $selected = json_decode((string)($page['related_json'] ?? '[]'), true);
    if (!is_array($selected)) { return []; }
    $result = [];
    foreach (['page'=>$pages, 'blog'=>$posts, 'event'=>$events] as $type=>$records) {
        foreach ($records as $record) {
            if (!in_array($type . ':' . $record['id'], $selected, true)) { continue; }
            if ($type === 'page' && (empty($record['is_published']) || $record['id'] === $page['id'])) { continue; }
            if ($type === 'blog' && empty($record['is_published'])) { continue; }
            if ($type === 'event' && (empty($record['is_visible']) || empty($record['slug']))) { continue; }
            $url = $type === 'page' ? public_page_url($record) : '/' . ($type === 'blog' ? 'blog/' : 'eventos/') . $record['slug'];
            $result[$url] = ['title'=>$record['title'], 'url'=>$url];
        }
    }
    return array_values($result);
}

function public_page_safe_html(string $html, bool $standalone = false, string $ctaLabel = ''): string
{
    // Preserve validated links/titles; dedicated pages may use controlled brand button classes.
    $html = strip_tags($html, '<h1><h2><h3><h4><p><ul><ol><li><strong><em><a><blockquote><br><hr>');
    if ($standalone) { $html = preg_replace('/<(\/?\s*)h1\b/i', '<$1h2', $html) ?: ''; }
    $html = preg_replace_callback('/<([a-z0-9]+)\b[^>]*>/i', static function (array $match) use ($standalone): string {
        $tag = strtolower($match[1]);
        if ($tag !== 'a') { return '<' . $tag . '>'; }
        $attrs = [];
        preg_match_all('/\b(href|title|class)\s*=\s*(?:"([^"]*)"|\x27([^\x27]*)\x27|([^\s>]+))/i', $match[0], $attrs, PREG_SET_ORDER);
        $link = '<a';
        $href = ''; $buttonClass = '';
        foreach ($attrs as $attr) {
            $name = strtolower($attr[1]);
            $quoted = (string)($attr[2] ?? '') ?: (string)($attr[3] ?? '');
            $value = html_entity_decode($quoted !== '' ? $quoted : (string)($attr[4] ?? ''), ENT_QUOTES, 'UTF-8');
            if ($name === 'class') {
                if ($standalone && preg_match('/(?:^|\s)(?:btn(?:-[a-z-]+)?|cta|page-cta)(?:\s|$)/i', $value)) {
                    $buttonClass = preg_match('/(?:^|\s)btn-outline-[a-z-]+(?:\s|$)/i', $value) ? 'btn-outline-brand' : 'btn-brand';
                }
                continue;
            }
            if ($name === 'href') {
                if (!public_page_safe_link($value) && !($standalone && preg_match('/^tel:\+?[0-9][0-9().-]*$/i', $value))) { continue; }
                $href = $value;
            }
            $link .= ' ' . $name . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
        }
        if ($standalone && $href !== '') {
            $contact = preg_match('#^(?:mailto:|tel:|/?\#(?:contactos|pedido-proposta)$|https://(?:wa\.me/|(?:api|web)\.whatsapp\.com/send\b))#i', $href);
            if ($buttonClass !== '' || $contact) { $link .= ' class="btn ' . ($buttonClass ?: 'btn-brand') . ' page-cta"'; }
        }
        return $link . '>';
    }, $html) ?: '';
    if ($standalone && trim($ctaLabel) !== '') {
        $html = preg_replace_callback('#<a\b([^>]*)>(.*?)</a>#is', static function (array $match) use ($ctaLabel): string {
            $label = trim(html_entity_decode(strip_tags($match[2]), ENT_QUOTES, 'UTF-8'));
            if (strpos($match[1], 'href=') !== false && strpos($match[1], 'page-cta') === false && strcasecmp($label, trim($ctaLabel)) === 0) {
                return '<a' . $match[1] . ' class="btn btn-brand page-cta">' . $match[2] . '</a>';
            }
            return $match[0];
        }, $html) ?: '';
    }
    return $html;
}

function public_page_reserved_slugs(): array
{
    return ['index','index-php','blog','eventos','sitemap','sitemap-xml','robots','reserve','subscribe','contact',
        'stand-up-comedy','eventos-de-humor','eventos-corporativos','humoristas-para-eventos-empresas',
        'humorista-jantar-natal-empresa','team-building-com-humor','stand-up-comedy-para-empresas',
        'booking-humoristas','mestre-cerimonias-com-humor','comedy-club-para-bares-restaurantes',
        'producao-eventos-stand-up-comedy','booking-de-humoristas','producao-de-eventos',
        'stand-up-comedy-portugal','stand-up-comedy-aveiro','stand-up-comedy-porto','stand-up-comedy-lisboa',
        'stand-up-comedy-braga','stand-up-comedy-coimbra','stand-up-comedy-faro','stand-up-comedy-suica',
        'stand-up-comedy-franca','stand-up-comedy-luxemburgo'];
}

function public_page_css_url(string $url): string
{
    if (!public_page_http_url($url)) { return ''; }
    return strtr($url, ["\\"=>'%5C', "'"=>'%27', '"'=>'%22', '('=>'%28', ')'=>'%29', '<'=>'%3C', '>'=>'%3E']);
}

function public_page_safe_link(string $url): bool
{
    if (preg_match('/[\\\\\x00-\x20\x7f]/', $url)) { return false; }
    if (public_page_http_url($url)) { return true; }
    if (preg_match('/^mailto:[^\s]+$/i', $url)) { return true; }
    return $url !== '' && strpos($url, '//') !== 0 && strpos($url, ':') === false;
}
