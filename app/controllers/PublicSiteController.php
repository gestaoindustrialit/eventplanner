<?php

class PublicSiteController extends BaseController
{
    public function index(): void
    {
        requireAdmin();
        $defaultPath = PUBLIC_SITE_DEFAULT_PATH;
        $pages = (new PublicPage($this->db))->all();

        $settings = new SiteSetting($this->db);
        $homeTagline = $settings->get('home_tagline', 'Produção • Booking • Experiências');
        $homeTitle = $settings->get('home_title', 'Humor e espetáculos com um palco inesquecível.');
        $homeDescription = $settings->get('home_description', 'Layout inspirado no visual "Big Picture": imagem de fundo marcante, tipografia forte e conteúdo em cartões translúcidos para foco total no evento.');
        $homeBackgroundUrl = $settings->get('home_background_url', 'https://chorarderir.com/blog-img/cdr-stand-up-comedy-producao-booking.jpg');
        $newsletterConsentText = $settings->get('newsletter_consent_text', 'Autorizo o tratamento dos meus dados para receber comunicações de eventos e novidades, de acordo com o RGPD.');
        $recaptchaSiteKey = $settings->get('recaptcha_site_key', '6LcrsLOsAAAAAB9NZ-X2s7ugJ7LsNAamg4VXW0wt');
        $recaptchaSecretKey = $settings->get('recaptcha_secret_key', '6LcrsLOsAAAAAJniWgy3I-C6PPXk_yTlfFc2U-Hi');
        $siteBannerEnabled = $settings->get('site_banner_enabled', '0');
        $siteBannerText = $settings->get('site_banner_text', '');
        $siteBannerButtonText = $settings->get('site_banner_button_text', 'Ver eventos');
        $siteBannerUrl = $settings->get('site_banner_url', '/#agenda');
        $siteBannerImageUrl = $settings->get('site_banner_image_url', '');
        $corporateEventsPage = $this->corporateEventsPageSettings($settings);

        $this->render('public_site/index', compact('defaultPath', 'pages', 'corporateEventsPage', 'homeTagline', 'homeTitle', 'homeDescription', 'homeBackgroundUrl', 'newsletterConsentText', 'recaptchaSiteKey', 'recaptchaSecretKey', 'siteBannerEnabled', 'siteBannerText', 'siteBannerButtonText', 'siteBannerUrl', 'siteBannerImageUrl'));
    }

    public function publish(): void
    {
        requireAdmin();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect(BASE_URL . '?controller=publicsite&action=index');
        }

        $targetPath = trim((string)($_POST['target_path'] ?? PUBLIC_SITE_DEFAULT_PATH));
        if ($targetPath === '') {
            flash('error', 'Indica uma pasta de destino para o site público.');
            $this->redirect(BASE_URL . '?controller=publicsite&action=index');
        }

        if (!is_dir($targetPath) && !mkdir($targetPath, 0775, true) && !is_dir($targetPath)) {
            flash('error', 'Não foi possível criar a pasta de destino: ' . $targetPath);
            $this->redirect(BASE_URL . '?controller=publicsite&action=index');
        }

        try {
            $settings = new SiteSetting($this->db);
            $settings->set('home_tagline', trim((string)($_POST['home_tagline'] ?? '')));
            $settings->set('home_title', trim((string)($_POST['home_title'] ?? '')));
            $settings->set('home_description', trim((string)($_POST['home_description'] ?? '')));
            $settings->set('home_background_url', trim((string)($_POST['home_background_url'] ?? '')));
            $settings->set('newsletter_consent_text', trim((string)($_POST['newsletter_consent_text'] ?? '')));
            $settings->set('recaptcha_site_key', trim((string)($_POST['recaptcha_site_key'] ?? '')));
            $settings->set('recaptcha_secret_key', trim((string)($_POST['recaptcha_secret_key'] ?? '')));
            $settings->set('site_banner_enabled', isset($_POST['site_banner_enabled']) ? '1' : '0');
            $settings->set('site_banner_text', trim((string)($_POST['site_banner_text'] ?? '')));
            $settings->set('site_banner_button_text', trim((string)($_POST['site_banner_button_text'] ?? '')));
            $settings->set('site_banner_url', trim((string)($_POST['site_banner_url'] ?? '')));
            $settings->set('site_banner_image_url', trim((string)($_POST['site_banner_image_url'] ?? '')));
            $settings->set('corporate_events_enabled', isset($_POST['corporate_events_enabled']) ? '1' : '0');
            $settings->set('corporate_events_title', trim((string)($_POST['corporate_events_title'] ?? '')));
            $settings->set('corporate_events_description', trim((string)($_POST['corporate_events_description'] ?? '')));
            $settings->set('corporate_events_image_url', trim((string)($_POST['corporate_events_image_url'] ?? '')));
            $settings->set('corporate_events_heading', trim((string)($_POST['corporate_events_heading'] ?? '')));
            $settings->set('corporate_events_content', trim((string)($_POST['corporate_events_content'] ?? '')));
            $settings->set('corporate_events_form_title', trim((string)($_POST['corporate_events_form_title'] ?? '')));
            $settings->set('corporate_events_form_intro', trim((string)($_POST['corporate_events_form_intro'] ?? '')));
            $settings->set('corporate_events_contact_email_to', trim((string)($_POST['corporate_events_contact_email_to'] ?? '')));

            $dbPath = (new Database())->getSqlitePath();
            $homeCopy = [
                'tagline' => $settings->get('home_tagline', ''),
                'title' => $settings->get('home_title', ''),
                'description' => $settings->get('home_description', ''),
                'background_url' => $settings->get('home_background_url', ''),
            ];
            $newsletterConsentText = $settings->get('newsletter_consent_text', '');
            $recaptchaSiteKey = $settings->get('recaptcha_site_key', '6LcrsLOsAAAAAB9NZ-X2s7ugJ7LsNAamg4VXW0wt');
            $recaptchaSecretKey = $settings->get('recaptcha_secret_key', '6LcrsLOsAAAAAJniWgy3I-C6PPXk_yTlfFc2U-Hi');
            $siteBanner = [
                'enabled' => $settings->get('site_banner_enabled', '0') === '1',
                'text' => $settings->get('site_banner_text', ''),
                'button_text' => $settings->get('site_banner_button_text', 'Ver eventos'),
                'url' => $settings->get('site_banner_url', '/#agenda'),
                'image_url' => $settings->get('site_banner_image_url', ''),
            ];
            $corporateEventsPage = $this->corporateEventsPageSettings($settings);

            if (file_put_contents($targetPath . '/index.php', $this->buildPublicIndex($dbPath, $homeCopy, $siteBanner, $newsletterConsentText, $recaptchaSiteKey, $corporateEventsPage)) === false) {
                throw new RuntimeException('Falha ao escrever index.php no destino.');
            }
            if (file_put_contents($targetPath . '/index.html', $this->buildIndexHtmlRedirect()) === false) {
                throw new RuntimeException('Falha ao escrever index.html no destino.');
            }
            if (file_put_contents($targetPath . '/reserve.php', $this->buildReserveHandler($dbPath, $recaptchaSecretKey)) === false) {
                throw new RuntimeException('Falha ao escrever reserve.php no destino.');
            }
            if (file_put_contents($targetPath . '/subscribe.php', $this->buildSubscribeHandler($dbPath, $recaptchaSecretKey)) === false) {
                throw new RuntimeException('Falha ao escrever subscribe.php no destino.');
            }
            if (file_put_contents($targetPath . '/contact.php', $this->buildContactHandler($dbPath, $recaptchaSecretKey)) === false) {
                throw new RuntimeException('Falha ao escrever contact.php no destino.');
            }
            if (file_put_contents($targetPath . '/sitemap.php', $this->buildSitemapGenerator($dbPath, $corporateEventsPage)) === false) {
                throw new RuntimeException('Falha ao escrever sitemap.php no destino.');
            }
            if (file_put_contents($targetPath . '/robots.txt', $this->buildRobotsTxt()) === false) {
                throw new RuntimeException('Falha ao escrever robots.txt no destino.');
            }
            if (file_put_contents($targetPath . '/.htaccess', $this->buildHtaccess()) === false) {
                throw new RuntimeException('Falha ao escrever .htaccess no destino.');
            }
            $corporatePagePath = $targetPath . '/eventos-corporativos';
            if (!empty($corporateEventsPage['enabled'])) {
                if (!is_dir($corporatePagePath) && !mkdir($corporatePagePath, 0775, true) && !is_dir($corporatePagePath)) {
                    throw new RuntimeException('Falha ao criar pasta eventos-corporativos no destino.');
                }
                if (file_put_contents($corporatePagePath . '/index.php', $this->buildCleanPageRouter('eventos-corporativos')) === false) {
                    throw new RuntimeException('Falha ao escrever eventos-corporativos/index.php no destino.');
                }
            } elseif (is_file($corporatePagePath . '/index.php')) {
                unlink($corporatePagePath . '/index.php');
            }

            $logoSource = dirname(__DIR__, 2) . '/assets/branding/chorarderir-logo.svg';
            if (is_file($logoSource)) {
                copy($logoSource, $targetPath . '/chorarderir-logo.svg');
            }
        } catch (Throwable $e) {
            flash('error', 'Não foi possível publicar o website: ' . $e->getMessage());
            $this->redirect(BASE_URL . '?controller=publicsite&action=index');
        }

        flash('success', 'Site público publicado em: ' . $targetPath);
        $this->redirect(BASE_URL . '?controller=publicsite&action=index');
    }

    private function corporateEventsPageDefaults(): array
    {
        return [
            'enabled' => true,
            'title' => 'Eventos Corporativos de Humor',
            'description' => 'Humor para empresas, convenções, festas de equipa e ativações internas com linguagem adaptada à marca.',
            'image_url' => 'https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&w=1400&q=80',
            'heading' => 'Eventos corporativos que aproximam equipas e marcas',
            'content' => 'Criamos momentos de humor para convenções, jantares de empresa, kick-offs, festas de equipa, ativações internas e apresentações com anfitrião. A proposta inclui curadoria de humoristas, alinhamento do tom com a marca, logística e acompanhamento de produção.',
            'form_title' => 'Conta-nos o briefing do teu evento corporativo',
            'form_intro' => 'Partilha os detalhes essenciais para receberes uma proposta ajustada ao objetivo, público e contexto da tua empresa.',
            'contact_email_to' => 'info@chorarderir.com',
        ];
    }

    private function corporateEventsPageSettings(SiteSetting $settings): array
    {
        $defaults = $this->corporateEventsPageDefaults();
        return [
            'enabled' => $settings->get('corporate_events_enabled', $defaults['enabled'] ? '1' : '0') === '1',
            'title' => $settings->get('corporate_events_title', $defaults['title']),
            'description' => $settings->get('corporate_events_description', $defaults['description']),
            'image_url' => $settings->get('corporate_events_image_url', $defaults['image_url']),
            'heading' => $settings->get('corporate_events_heading', $defaults['heading']),
            'content' => $settings->get('corporate_events_content', $defaults['content']),
            'form_title' => $settings->get('corporate_events_form_title', $defaults['form_title']),
            'form_intro' => $settings->get('corporate_events_form_intro', $defaults['form_intro']),
            'contact_email_to' => $settings->get('corporate_events_contact_email_to', $defaults['contact_email_to']),
        ];
    }

    private function buildPublicIndex(string $dbPath, array $homeCopy, array $siteBanner, string $newsletterConsentText, string $recaptchaSiteKey, array $corporateEventsPage): string
    {
        $homeCopyJson = json_encode($homeCopy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $siteBannerJson = json_encode($siteBanner, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $corporateEventsPageJson = json_encode($corporateEventsPage, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $template = <<<'PHP'
<?php
$events = [];
$pages = [];
$partners = [];
$blogPosts = [];
$homeCopy = json_decode('__HOME_COPY_JSON__', true) ?: [];
$siteBanner = json_decode('__SITE_BANNER_JSON__', true) ?: [];
$corporateEventsPage = json_decode('__CORPORATE_EVENTS_PAGE_JSON__', true) ?: [];
$msg = $_GET['msg'] ?? '';
$pageSlug = trim((string)($_GET['page'] ?? ''));
$eventSlug = trim((string)($_GET['evento'] ?? ''));
$recaptchaSiteKey = trim((string)'__RECAPTCHA_SITE_KEY__');
$hasRecaptcha = $recaptchaSiteKey !== '';

// Keep old, indexed query-string links working, but consolidate their SEO value
// on the public URL. This is also a fallback for servers that do not apply the
// Apache rewrite rules generated below.
$requestPath = trim(parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/', '/');
if ($pageSlug === 'eventos-corporativos' && ($requestPath === '' || $requestPath === 'index.php')) {
    $redirectQuery = $_GET;
    unset($redirectQuery['page']);
    $cleanLocation = '/eventos-corporativos/';
    if ($redirectQuery !== []) {
        $cleanLocation .= '?' . http_build_query($redirectQuery);
    }
    header('Location: ' . $cleanLocation, true, 301);
    exit;
}

try {
    $db = new PDO('sqlite:__DB_PATH__', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $eventColumns = array_column($db->query('PRAGMA table_info(events)')->fetchAll(), 'name');
    $hasReservationsOpen = in_array('reservations_open', $eventColumns, true);
    $hasReservationCapacity = in_array('reservation_capacity', $eventColumns, true);
    $hasIsVisible = in_array('is_visible', $eventColumns, true);

    $eventSql = "SELECT e.*, c.name as client_name, COALESCE(SUM(CASE WHEN r.status != 'cancelled' THEN r.tickets ELSE 0 END), 0) AS active_tickets
                 FROM events e
                 LEFT JOIN clients c ON c.id = e.client_id
                 LEFT JOIN event_reservations r ON r.event_id = e.id
                 WHERE e.date >= date('now')";
    if ($hasIsVisible) {
        $eventSql .= " AND e.is_visible = 1";
    }
    $eventSql .= " GROUP BY e.id ORDER BY e.date ASC, e.time ASC";
    $events = $db->query($eventSql)->fetchAll() ?: [];

    $pageSql = 'SELECT * FROM public_pages';
    $pageColumns = array_column($db->query('PRAGMA table_info(public_pages)')->fetchAll(), 'name');
    if (in_array('is_published', $pageColumns, true)) {
        $pageSql .= ' WHERE is_published = 1';
    }
    if (in_array('sort_order', $pageColumns, true)) {
        $pageSql .= ' ORDER BY sort_order ASC, title ASC';
    } else {
        $pageSql .= ' ORDER BY title ASC';
    }
    $pages = $db->query($pageSql)->fetchAll() ?: [];

    $partnerColumns = array_column($db->query('PRAGMA table_info(partners)')->fetchAll(), 'name');
    if (count($partnerColumns) > 0) {
        $partnerSql = "SELECT * FROM partners
                       WHERE date(partnership_start_date) <= date('now')
                         AND date(partnership_start_date, '+1 year') > date('now')
                       ORDER BY sort_order ASC, company_name ASC";
        $partners = $db->query($partnerSql)->fetchAll() ?: [];
    }

    $blogColumns = array_column($db->query('PRAGMA table_info(blog_posts)')->fetchAll(), 'name');
    if (count($blogColumns) > 0) {
        $blogSql = 'SELECT * FROM blog_posts WHERE is_published = 1 ORDER BY sort_order ASC, published_at DESC, created_at DESC';
        $blogPosts = $db->query($blogSql)->fetchAll() ?: [];
    }

    if (!$hasReservationsOpen) {
        foreach ($events as &$legacyEvent) {
            $legacyEvent['reservations_open'] = 1;
            if (!$hasReservationCapacity) {
                $legacyEvent['reservation_capacity'] = 0;
            }
            if (!$hasIsVisible) {
                $legacyEvent['is_visible'] = 1;
            }
        }
        unset($legacyEvent);
    }
} catch (Throwable $e) {
    $events = [];
    $pages = [];
    $partners = [];
    $blogPosts = [];
}

$siteTitle = 'Chorar de Rir';
$defaultHomeCopy = [
    'tagline' => 'Produção • Booking • Experiências',
    'title' => 'Humor e espetáculos com um palco inesquecível.',
    'description' => 'Eventos únicos, noites memoráveis e talento nacional num ambiente vibrante.',
    'background_url' => 'https://chorarderir.com/blog-img/cdr-stand-up-comedy-producao-booking.jpg',
];
$homeCopy = [
    'tagline' => trim((string)($homeCopy['tagline'] ?? '')) !== '' ? (string)$homeCopy['tagline'] : $defaultHomeCopy['tagline'],
    'title' => trim((string)($homeCopy['title'] ?? '')) !== '' ? (string)$homeCopy['title'] : $defaultHomeCopy['title'],
    'description' => trim((string)($homeCopy['description'] ?? '')) !== '' ? (string)$homeCopy['description'] : $defaultHomeCopy['description'],
    'background_url' => trim((string)($homeCopy['background_url'] ?? '')) !== '' ? (string)$homeCopy['background_url'] : $defaultHomeCopy['background_url'],
];
$heroBackgroundUrl = trim((string)($homeCopy['background_url'] ?? ''));
$siteBanner = [
    'enabled' => !empty($siteBanner['enabled']),
    'text' => trim((string)($siteBanner['text'] ?? '')),
    'button_text' => trim((string)($siteBanner['button_text'] ?? '')) !== '' ? trim((string)$siteBanner['button_text']) : 'Ver eventos',
    'url' => trim((string)($siteBanner['url'] ?? '')) !== '' ? trim((string)$siteBanner['url']) : '/#agenda',
    'image_url' => trim((string)($siteBanner['image_url'] ?? '')),
];
if ($heroBackgroundUrl === '') {
    $heroBackgroundUrl = $defaultHomeCopy['background_url'];
}

$defaultCorporateEventsPage = [
    'enabled' => true,
    'title' => 'Eventos Corporativos de Humor',
    'description' => 'Humor para empresas, convenções, festas de equipa e ativações internas com linguagem adaptada à marca.',
    'image_url' => 'https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&w=1400&q=80',
    'heading' => 'Eventos corporativos que aproximam equipas e marcas',
    'content' => 'Criamos momentos de humor para convenções, jantares de empresa, kick-offs, festas de equipa, ativações internas e apresentações com anfitrião. A proposta inclui curadoria de humoristas, alinhamento do tom com a marca, logística e acompanhamento de produção.',
    'form_title' => 'Conta-nos o briefing do teu evento corporativo',
    'form_intro' => 'Partilha os detalhes essenciais para receberes uma proposta ajustada ao objetivo, público e contexto da tua empresa.',
    'contact_email_to' => 'info@chorarderir.com',
];
$corporateEventsPage = [
    'enabled' => !array_key_exists('enabled', $corporateEventsPage) || !empty($corporateEventsPage['enabled']),
    'title' => trim((string)($corporateEventsPage['title'] ?? '')) !== '' ? trim((string)$corporateEventsPage['title']) : $defaultCorporateEventsPage['title'],
    'description' => trim((string)($corporateEventsPage['description'] ?? '')) !== '' ? trim((string)$corporateEventsPage['description']) : $defaultCorporateEventsPage['description'],
    'image_url' => trim((string)($corporateEventsPage['image_url'] ?? '')) !== '' ? trim((string)$corporateEventsPage['image_url']) : $defaultCorporateEventsPage['image_url'],
    'heading' => trim((string)($corporateEventsPage['heading'] ?? '')) !== '' ? trim((string)$corporateEventsPage['heading']) : $defaultCorporateEventsPage['heading'],
    'content' => trim((string)($corporateEventsPage['content'] ?? '')) !== '' ? trim((string)$corporateEventsPage['content']) : $defaultCorporateEventsPage['content'],
    'form_title' => trim((string)($corporateEventsPage['form_title'] ?? '')) !== '' ? trim((string)$corporateEventsPage['form_title']) : $defaultCorporateEventsPage['form_title'],
    'form_intro' => trim((string)($corporateEventsPage['form_intro'] ?? '')) !== '' ? trim((string)$corporateEventsPage['form_intro']) : $defaultCorporateEventsPage['form_intro'],
    'contact_email_to' => trim((string)($corporateEventsPage['contact_email_to'] ?? '')) !== '' ? trim((string)$corporateEventsPage['contact_email_to']) : $defaultCorporateEventsPage['contact_email_to'],
];

$baseUrl = 'https://chorarderir.com';
$currentPath = trim(parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/', '/');

function seo_slug(string $value): string {
    $value = trim(function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value));
    $map = ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','í'=>'i','ì'=>'i','î'=>'i','ï'=>'i','ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o','ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n'];
    $value = strtr($value, $map);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: '';
    return trim($value, '-') ?: 'pagina';
}

function absolute_url(string $path = ''): string {
    $base = 'https://chorarderir.com';
    $path = trim($path);
    if ($path === '' || $path === '/') { return $base . '/'; }
    if (preg_match('#^https?://#i', $path)) { return $path; }
    return $base . '/' . ltrim($path, '/');
}

function truncate_text(string $value, int $limit = 155): string {
    $value = trim(preg_replace('/\s+/', ' ', strip_tags($value)) ?: '');
    if ((function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value)) <= $limit) { return $value; }
    return rtrim(function_exists('mb_substr') ? mb_substr($value, 0, $limit - 1, 'UTF-8') : substr($value, 0, $limit - 1)) . '…';
}

function seo_page_url(string $slug): string { return absolute_url($slug); }

function safe_content(?string $html): string {
    return strip_tags((string)$html, '<h1><h2><h3><h4><p><ul><ol><li><strong><em><a><blockquote><br><hr>');
}

function build_event_schema_payload(array $event): array {
    return [
        'title' => (string)($event['title'] ?? ''),
        'description' => (string)($event['notes'] ?? ''),
        'date' => (string)($event['date'] ?? ''),
        'time' => (string)($event['time'] ?? ''),
        'location' => (string)($event['location'] ?? ''),
        'poster_url' => (string)($event['poster_url'] ?? ''),
        'external_ticket_url' => (string)($event['external_ticket_url'] ?? ''),
        'price_currency' => 'EUR',
        'reservations_open' => (int)($event['reservations_open'] ?? 0),
        'status' => ((string)($event['date'] ?? '') >= date('Y-m-d')) ? 'scheduled' : 'cancelled',
    ];
}


function event_public_url(array $event, array $eventSlugById): string {
    $id = (int)($event['id'] ?? 0);
    if ($id <= 0 || !isset($eventSlugById[$id])) {
        return '/#agenda';
    }
    return '/eventos/' . rawurlencode((string)$eventSlugById[$id]);
}

function page_mode(array $page): string {
    return (($page['display_mode'] ?? 'section') === 'page') ? 'page' : 'section';
}

function section_type(array $page): string {
    $type = (string)($page['section_type'] ?? 'default');
    if (in_array($type, ['default', 'about', 'services', 'contact_form'], true) && $type !== 'default') {
        return $type;
    }
    $config = section_config($page);
    if (!empty($config['services']) && is_array($config['services'])) {
        return 'services';
    }
    if (!empty($config['contact_email_to']) || !empty($config['contact_fields'])) {
        return 'contact_form';
    }
    return 'default';
}

function section_style(array $page): string {
    $style = (string)($page['section_style'] ?? 'card');
    return in_array($style, ['card', 'split', 'icons', 'highlight'], true) ? $style : 'card';
}

function section_config(array $page): array {
    $decoded = json_decode((string)($page['section_config_json'] ?? ''), true);
    return is_array($decoded) ? $decoded : [];
}

function bootstrap_icon_class(string $name): string {
    $clean = strtolower(trim($name));
    $clean = str_replace(['bi ', 'bi-', '_'], ['', '', '-'], $clean);
    if (!preg_match('/^[a-z0-9-]+$/', $clean)) {
        return 'bi-stars';
    }
    return 'bi-' . $clean;
}

$sectionPages = [];
$standalonePages = [];
$activeStandalonePage = null;

foreach ($pages as $page) {
    if (page_mode($page) === 'page') {
        $standalonePages[] = $page;
        if (($page['slug'] ?? '') === $pageSlug) {
            $activeStandalonePage = $page;
        }
    } else {
        $sectionPages[] = $page;
    }
}

$isStandaloneView = $activeStandalonePage !== null;
$hasAgendaEvents = count($events) > 0;
$selectedEvent = null;
$selectedEventSlug = '';
$eventSlugById = [];
$eventIdBySlug = [];
$usedSlugs = [];
foreach ($events as $index => $event) {
    $baseSlug = seo_slug((string)($event['title'] ?? ''));
    if ($baseSlug === '') {
        $baseSlug = 'evento-' . (int)($event['id'] ?? ($index + 1));
    }
    $slug = $baseSlug;
    $suffix = 2;
    while (isset($usedSlugs[$slug])) {
        $slug = $baseSlug . '-' . $suffix;
        $suffix++;
    }
    $usedSlugs[$slug] = true;
    $eventSlugById[(int)$event['id']] = $slug;
    $eventIdBySlug[$slug] = (int)$event['id'];
}
if ($eventSlug !== '' && isset($eventIdBySlug[$eventSlug])) {
    foreach ($events as $event) {
        if ((int)$event['id'] === (int)$eventIdBySlug[$eventSlug]) {
            $selectedEvent = $event;
            $selectedEventSlug = $eventSlug;
            break;
        }
    }
}
$isEventView = $selectedEvent !== null;
$hasReservableEvents = false;
$hasPartners = count($partners) > 0;
foreach ($events as $event) {
    if ((int)($event['reservations_open'] ?? 0) !== 1) {
        continue;
    }
    $capacity = (int)($event['reservation_capacity'] ?? 0);
    $activeTickets = (int)($event['active_tickets'] ?? 0);
    $hasAvailability = $capacity <= 0 || ($capacity - $activeTickets) > 0;
    if ($hasAvailability) {
        $hasReservableEvents = true;
        break;
    }
}

$servicePages = [
    'stand-up-comedy' => ['title' => 'Stand Up Comedy em Portugal', 'type' => 'service', 'description' => 'Espetáculos de stand up comedy para teatros, bares, empresas e eventos privados, com humoristas selecionados e produção completa.'],
    'eventos-de-humor' => ['title' => 'Eventos de Humor', 'type' => 'service', 'description' => 'Criação e produção de eventos de humor ao vivo, da curadoria de artistas à operação no dia do espetáculo.'],
    'eventos-corporativos' => ['title' => $corporateEventsPage['title'], 'type' => 'service', 'description' => $corporateEventsPage['description']],
    'humoristas-para-eventos-empresas' => ['title' => 'Humoristas para Eventos de Empresas', 'type' => 'service', 'description' => 'Humoristas para eventos de empresa, convenções, festas e encontros de equipa, escolhidos de acordo com o público e a cultura da organização.', 'heading' => 'O humorista certo para o seu evento de empresa', 'content' => 'Tratamos da seleção do humorista, briefing, contratação e acompanhamento. Adaptamos o formato ao número de convidados, ao espaço e aos objetivos da empresa, com uma proposta clara e produção profissional.'],
    'humorista-jantar-natal-empresa' => ['title' => 'Humorista para Jantar de Natal da Empresa', 'type' => 'service', 'description' => 'Animação para jantar de empresa com humor e stand-up comedy adaptados à equipa, ao espaço e ao ambiente da celebração.', 'heading' => 'Animação com humor para o jantar de Natal', 'content' => 'Criamos um momento de comédia que envolve colaboradores e convidados sem comprometer o tom da celebração. A solução pode incluir atuação de stand-up, apresentação da noite e conteúdos preparados a partir do briefing da empresa.'],
    'team-building-com-humor' => ['title' => 'Team Building com Humor', 'type' => 'service', 'description' => 'Dinâmicas de team building com humor para aproximar equipas, aumentar a energia e criar memórias positivas.', 'heading' => 'Team building com humor feito para a sua equipa', 'content' => 'Desenhamos experiências participativas que usam o humor para estimular comunicação, criatividade e ligação entre colegas. Ajustamos duração, nível de participação e linguagem à cultura e aos objetivos da organização.'],
    'stand-up-comedy-para-empresas' => ['title' => 'Stand-up Comedy para Empresas', 'type' => 'service', 'description' => 'Espetáculos de stand-up comedy para empresas, convenções, kick-offs, festas internas e eventos para clientes.', 'heading' => 'Stand-up comedy pensado para contexto empresarial', 'content' => 'Selecionamos humoristas com o registo adequado, alinhamos temas e limites no briefing e coordenamos todos os detalhes da atuação. O resultado é um espetáculo profissional, relevante e confortável para o público da empresa.'],
    'booking-humoristas' => ['title' => 'Booking de Humoristas', 'type' => 'service', 'description' => 'Booking de humoristas para eventos empresariais, privados e culturais, com curadoria, contratação e gestão logística.', 'heading' => 'Booking profissional de humoristas', 'content' => 'Centralizamos disponibilidade, proposta, contratação, deslocações e necessidades técnicas. A nossa curadoria considera o perfil do público, o objetivo, a duração, o local e o orçamento do evento.'],
    'mestre-cerimonias-com-humor' => ['title' => 'Mestre de Cerimónias com Humor', 'type' => 'service', 'description' => 'Mestre de cerimónias com humor para apresentar galas, convenções, prémios, conferências e festas de empresa.', 'heading' => 'Ritmo, ligação e humor em palco', 'content' => 'Um apresentador com experiência em comédia conduz o alinhamento, apresenta intervenientes e mantém a energia da sala. Preparamos guiões, transições e momentos de interação em articulação com a produção.'],
    'comedy-club-para-bares-restaurantes' => ['title' => 'Comedy Club para Bares e Restaurantes', 'type' => 'service', 'description' => 'Noites de comedy club para bares, restaurantes e hotéis que querem atrair público com programação regular de humor.', 'heading' => 'Uma noite de comedy club no seu espaço', 'content' => 'Criamos um conceito adequado ao local, selecionamos o elenco e apoiamos calendário, comunicação, bilheteira e operação. É possível desenvolver uma sessão única ou uma programação recorrente.'],
    'producao-eventos-stand-up-comedy' => ['title' => 'Produção de Eventos de Stand-up Comedy', 'type' => 'service', 'description' => 'Produção de eventos de stand-up comedy, do conceito e booking à técnica, comunicação, bilheteira e operação de sala.', 'heading' => 'Produção completa de eventos de stand-up comedy', 'content' => 'Coordenamos conceito, espaço, artistas, rider técnico, cronograma, promoção, bilheteira e equipa no terreno. Cada etapa é planeada para oferecer uma experiência consistente ao cliente, aos humoristas e ao público.'],
    'booking-de-humoristas' => ['title' => 'Booking de Humoristas', 'type' => 'service', 'description' => 'Contratar humorista ou stand-up comedy com acompanhamento profissional, briefing e gestão logística.'],
    'producao-de-eventos' => ['title' => 'Produção de Eventos de Humor', 'type' => 'service', 'description' => 'Produção integral de espetáculos de humor: conceito, agenda, artistas, bilheteira, comunicação e experiência de público.'],
];
$localPages = [];
foreach (['Portugal','Aveiro','Porto','Lisboa','Braga','Coimbra','Faro','Suíça','França','Luxemburgo'] as $place) {
    $slug = 'stand-up-comedy-' . seo_slug($place);
    $localPages[$slug] = ['title' => 'Stand Up Comedy e Eventos de Humor em ' . $place, 'type' => 'local', 'place' => $place, 'description' => 'Organização de stand up comedy, eventos de humor, humor ao vivo e booking de humoristas para ' . $place . '.'];
}
if (!$corporateEventsPage['enabled']) {
    unset($servicePages['eventos-corporativos']);
}
$virtualPages = $servicePages + $localPages;
$activeVirtualSlug = $currentPath !== '' ? $currentPath : $pageSlug;
$activeVirtualPage = $virtualPages[$activeVirtualSlug] ?? null;
$corporateEventsImageUrl = $corporateEventsPage['image_url'];
$activeBlogIndex = $activeVirtualSlug === 'blog';
$activeBlogPost = null;
$relatedBlogPosts = [];
if (substr($activeVirtualSlug, 0, 5) === 'blog/') {
    $postSlug = substr($activeVirtualSlug, 5);
    foreach ($blogPosts as $post) {
        if ((string)($post['slug'] ?? '') === $postSlug) {
            $activeBlogPost = $post;
            break;
        }
    }
}
if ($activeBlogPost !== null) {
    $sameCategoryPosts = [];
    $otherCategoryPosts = [];
    $activeCategory = trim((string)($activeBlogPost['category'] ?? ''));
    foreach ($blogPosts as $post) {
        if ((int)($post['id'] ?? 0) === (int)($activeBlogPost['id'] ?? 0)) {
            continue;
        }
        if ($activeCategory !== '' && strcasecmp(trim((string)($post['category'] ?? '')), $activeCategory) === 0) {
            $sameCategoryPosts[] = $post;
        } else {
            $otherCategoryPosts[] = $post;
        }
    }
    $relatedBlogPosts = array_slice(array_merge($sameCategoryPosts, $otherCategoryPosts), 0, 3);
}
if ($activeVirtualPage !== null || $activeBlogIndex || $activeBlogPost !== null) { $isStandaloneView = false; $isEventView = false; }

function render_corporate_proposal_form(array $corporateEventsPage, string $recaptchaSiteKey, bool $hasRecaptcha, string $msg, string $pageSlug = 'eventos-corporativos', string $subject = 'Pedido de proposta - eventos corporativos'): void {
    ?>
    <section id="pedido-proposta" class="surface-card seo-content-card proposal-form-card p-4 p-lg-5 mt-4 fade-in">
      <span class="eyebrow"><i class="bi bi-send-check"></i> Pedido de proposta</span>
      <h2><?php echo htmlspecialchars($corporateEventsPage['form_title']); ?></h2>
      <p class="text-secondary"><?php echo htmlspecialchars($corporateEventsPage['form_intro']); ?></p>
      <?php if ($msg === 'contact_ok'): ?><div class="alert alert-success">Pedido enviado com sucesso. Vamos responder brevemente.</div><?php elseif ($msg === 'contact_error' || $msg === 'contact_captcha'): ?><div class="alert alert-danger">Não foi possível enviar o pedido. Confirma os dados e tenta novamente.</div><?php endif; ?>
      <form method="post" action="/contact.php" class="row g-3">
        <input type="hidden" name="page_slug" value="<?php echo htmlspecialchars($pageSlug); ?>">
        <input type="hidden" name="subject" value="<?php echo htmlspecialchars($subject); ?>">
        <div class="col-md-6"><label class="form-label" for="proposal_name">Nome</label><input id="proposal_name" class="form-control form-control-lg" name="name" autocomplete="name" required></div>
        <div class="col-md-6"><label class="form-label" for="proposal_company">Empresa</label><input id="proposal_company" class="form-control form-control-lg" name="company" autocomplete="organization" required></div>
        <div class="col-md-6"><label class="form-label" for="proposal_email">Email</label><input id="proposal_email" type="email" class="form-control form-control-lg" name="email" autocomplete="email" required></div>
        <div class="col-md-6"><label class="form-label" for="proposal_phone">Telefone</label><input id="proposal_phone" class="form-control form-control-lg" name="phone" autocomplete="tel"></div>
        <div class="col-md-6"><label class="form-label" for="proposal_date">Data prevista</label><input id="proposal_date" type="date" class="form-control form-control-lg" name="event_date"></div>
        <div class="col-md-6"><label class="form-label" for="proposal_location">Local / cidade</label><input id="proposal_location" class="form-control form-control-lg" name="event_location" placeholder="Ex.: Lisboa, Porto, online"></div>
        <div class="col-md-6"><label class="form-label" for="proposal_audience">Nº de participantes</label><input id="proposal_audience" type="number" min="1" class="form-control form-control-lg" name="audience_size" placeholder="Ex.: 120"></div>
        <div class="col-md-6"><label class="form-label" for="proposal_format">Formato pretendido</label><select id="proposal_format" class="form-select form-select-lg" name="event_format"><option value="">Selecionar formato</option><option>Stand up comedy</option><option>Apresentação / hosting</option><option>Team building com humor</option><option>Ativação de marca</option><option>Outro formato</option></select></div>
        <div class="col-12"><label class="form-label" for="proposal_message">Objetivo e briefing</label><textarea id="proposal_message" class="form-control form-control-lg" name="message" rows="5" placeholder="Objetivo do evento, perfil do público, timings, orçamento indicativo ou notas relevantes." required></textarea></div>
        <?php if ($hasRecaptcha): ?><div class="col-12"><div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptchaSiteKey); ?>"></div></div><?php endif; ?>
        <div class="col-12"><button class="btn btn-brand btn-lg px-5">Enviar pedido de proposta</button></div>
      </form>
    </section>
    <?php
}

function render_partners_section(array $partners): void {
    if (count($partners) === 0) {
        return;
    }
    ?>
    <section id="parceiros" class="section-block pt-0">
      <div class="container">
        <div class="surface-card p-4 p-lg-5 fade-in">
          <h2 class="section-heading mb-4 text-center">Parceiros</h2>
          <div id="partnersCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
              <?php foreach (array_chunk($partners, 4) as $chunkIndex => $partnersChunk): ?>
                <div class="carousel-item <?php echo $chunkIndex === 0 ? 'active' : ''; ?>">
                  <div class="row g-3 justify-content-center align-items-center">
                    <?php foreach ($partnersChunk as $partner): ?>
                      <div class="col-12 col-sm-6 col-lg-3 text-center">
                        <a href="<?php echo htmlspecialchars((string)($partner['company_url'] ?? '#')); ?>" target="_blank" rel="noopener noreferrer" class="partner-card">
                          <span class="partner-logo"><img src="<?php echo htmlspecialchars((string)$partner['logo_url']); ?>" alt="Logótipo de <?php echo htmlspecialchars((string)$partner['company_name']); ?>"></span>
                          <strong><?php echo htmlspecialchars((string)$partner['company_name']); ?></strong>
                          <span class="partner-type"><?php echo htmlspecialchars((string)($partner['partnership_type'] ?? 'Parceiro')); ?></span>
                        </a>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </section>
    <?php
}
?>
<!doctype html>
<html lang="pt">
<head>
  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-TPWEL0LHMH"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-TPWEL0LHMH');
  </script>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php
    $seoTitle = 'Stand Up Comedy e Eventos de Humor em Portugal | Chorar de Rir';
    $seoDescription = truncate_text((string)($homeCopy['description'] ?? 'Eventos de stand up comedy, humor ao vivo, eventos corporativos de humor, booking de humoristas e produção de eventos em Portugal.'), 158);
    $canonicalUrl = absolute_url('/');
    $ogImage = absolute_url((string)$heroBackgroundUrl);
    $schemaType = 'WebPage';
    if ($isEventView && $selectedEvent) {
        $seoTitle = truncate_text((string)$selectedEvent['title'] . ' | Evento de Stand Up Comedy | Chorar de Rir', 62);
        $seoDescription = truncate_text((string)($selectedEvent['notes'] ?: 'Espetáculo de humor ao vivo com reservas e informação do evento.'), 158);
        $canonicalUrl = absolute_url('eventos/' . $selectedEventSlug);
        $ogImage = !empty($selectedEvent['poster_url']) ? absolute_url((string)$selectedEvent['poster_url']) : $ogImage;
        $schemaType = 'Event';
    } elseif ($activeStandalonePage) {
        $seoTitle = truncate_text((string)$activeStandalonePage['title'] . ' | Chorar de Rir', 62);
        $seoDescription = truncate_text((string)($activeStandalonePage['excerpt'] ?: $activeStandalonePage['content'] ?: $seoDescription), 158);
        $canonicalUrl = absolute_url((string)$activeStandalonePage['slug']);
        $ogImage = !empty($activeStandalonePage['hero_image_url']) ? absolute_url((string)$activeStandalonePage['hero_image_url']) : $ogImage;
    } elseif ($activeVirtualPage) {
        $seoTitle = truncate_text((string)$activeVirtualPage['title'] . ' | Chorar de Rir', 62);
        $seoDescription = truncate_text((string)$activeVirtualPage['description'], 158);
        $canonicalUrl = absolute_url($activeVirtualSlug);
        if ($activeVirtualSlug === 'eventos-corporativos') {
            $canonicalUrl = absolute_url('eventos-corporativos/');
            $ogImage = absolute_url($corporateEventsImageUrl);
        }
    } elseif ($activeBlogPost) {
        $seoTitle = truncate_text((string)($activeBlogPost['meta_title'] ?: $activeBlogPost['title'] . ' | Blog Chorar de Rir'), 62);
        $seoDescription = truncate_text((string)($activeBlogPost['meta_description'] ?: $activeBlogPost['excerpt'] ?: $activeBlogPost['content']), 158);
        $canonicalUrl = absolute_url('blog/' . (string)$activeBlogPost['slug']);
        $ogImage = !empty($activeBlogPost['hero_image_url']) ? absolute_url((string)$activeBlogPost['hero_image_url']) : $ogImage;
        $schemaType = 'Article';
    } elseif ($activeBlogIndex) {
        $seoTitle = 'Blog de Humor, Stand Up Comedy e Eventos | Chorar de Rir';
        $seoDescription = 'Artigos sobre stand up comedy, eventos de humor, humor para empresas e produção de espetáculos ao vivo.';
        $canonicalUrl = absolute_url('blog');
    }
  ?>
  <title><?php echo htmlspecialchars($seoTitle); ?></title>
  <meta name="description" content="<?php echo htmlspecialchars($seoDescription); ?>">
  <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl); ?>">
  <meta property="og:type" content="<?php echo $schemaType === 'Article' ? 'article' : 'website'; ?>">
  <meta property="og:site_name" content="Chorar de Rir">
  <meta property="og:title" content="<?php echo htmlspecialchars($seoTitle); ?>">
  <meta property="og:description" content="<?php echo htmlspecialchars($seoDescription); ?>">
  <meta property="og:url" content="<?php echo htmlspecialchars($canonicalUrl); ?>">
  <meta property="og:image" content="<?php echo htmlspecialchars($ogImage); ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?php echo htmlspecialchars($seoTitle); ?>">
  <meta name="twitter:description" content="<?php echo htmlspecialchars($seoDescription); ?>">
  <meta name="twitter:image" content="<?php echo htmlspecialchars($ogImage); ?>">
  <link rel="icon" type="image/svg+xml" href="/chorarderir-logo.svg">
  <?php
    $jsonLd = [
      ['@context'=>'https://schema.org','@type'=>'Organization','name'=>'Chorar de Rir','url'=>absolute_url('/'),'logo'=>absolute_url('/chorarderir-logo.svg'),'sameAs'=>[]],
      ['@context'=>'https://schema.org','@type'=>'LocalBusiness','name'=>'Chorar de Rir','url'=>absolute_url('/'),'image'=>$ogImage,'areaServed'=>['Portugal','Suíça','França','Luxemburgo'],'priceRange'=>'€€','description'=>$seoDescription],
      ['@context'=>'https://schema.org','@type'=>'WebSite','name'=>'Chorar de Rir','url'=>absolute_url('/'),'inLanguage'=>'pt-PT'],
    ];
    $breadcrumbs = [['@type'=>'ListItem','position'=>1,'name'=>'Início','item'=>absolute_url('/')]];
    if ($activeVirtualPage) { $breadcrumbs[] = ['@type'=>'ListItem','position'=>2,'name'=>$activeVirtualPage['title'],'item'=>$canonicalUrl]; }
    if ($activeBlogIndex) { $breadcrumbs[] = ['@type'=>'ListItem','position'=>2,'name'=>'Blog','item'=>$canonicalUrl]; }
    if ($activeBlogPost) { $breadcrumbs[] = ['@type'=>'ListItem','position'=>2,'name'=>'Blog','item'=>absolute_url('blog')]; $breadcrumbs[] = ['@type'=>'ListItem','position'=>3,'name'=>$activeBlogPost['title'],'item'=>$canonicalUrl]; }
    if ($activeStandalonePage) { $breadcrumbs[] = ['@type'=>'ListItem','position'=>2,'name'=>$activeStandalonePage['title'],'item'=>$canonicalUrl]; }
    if ($isEventView && $selectedEvent) { $breadcrumbs[] = ['@type'=>'ListItem','position'=>2,'name'=>'Eventos','item'=>absolute_url('/#agenda')]; $breadcrumbs[] = ['@type'=>'ListItem','position'=>3,'name'=>$selectedEvent['title'],'item'=>$canonicalUrl]; }
    $jsonLd[] = ['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>$breadcrumbs];
    if ($activeVirtualPage) {
      $jsonLd[] = ['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>[
        ['@type'=>'Question','name'=>'Como pedir orçamento para eventos de humor?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Envia cidade, data, público, objetivo e formato pretendido através dos contactos.']],
        ['@type'=>'Question','name'=>'A Chorar de Rir trabalha fora de Portugal?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Sim. O website está preparado para Portugal, Suíça, França e Luxemburgo.']]
      ]];
    }
    if ($activeBlogPost) {
      $jsonLd[] = ['@context'=>'https://schema.org','@type'=>'Article','headline'=>$activeBlogPost['title'],'description'=>$seoDescription,'datePublished'=>(string)($activeBlogPost['published_at'] ?? ''),'author'=>['@type'=>'Organization','name'=>'Chorar de Rir'],'publisher'=>['@type'=>'Organization','name'=>'Chorar de Rir','logo'=>['@type'=>'ImageObject','url'=>absolute_url('/chorarderir-logo.svg')]],'mainEntityOfPage'=>$canonicalUrl];
    }
  ?>
  <?php foreach ($jsonLd as $schema): ?><script type="application/ld+json"><?php echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script><?php endforeach; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    :root {
      --primary: #E10600;
      --primary-hover: #FF2A1F;
      --background-main: #0B0B0D;
      --background-secondary: #1A1A1D;
      --text-primary: #FFFFFF;
      --text-secondary: #B3B3B3;
      --accent: #FFC300;
      --accent-secondary: #2A0F2E;
      --nav-height: 74px;
    }
    body {
      font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
      color: var(--text-primary);
      background: var(--background-main);
      min-height: 100vh;
      margin: 0;
      display: flex;
      flex-direction: column;
      scroll-behavior: smooth;
    }
    body::before {
      content: '';
      position: fixed;
      inset: 0;
      pointer-events: none;
      background:
        radial-gradient(circle at 8% 18%, rgba(225,6,0,.22), transparent 33%),
        radial-gradient(circle at 86% 2%, rgba(255,195,0,.1), transparent 28%),
        radial-gradient(circle at 52% 112%, rgba(42,15,46,.45), transparent 40%);
      z-index: 0;
    }
    .site-main { flex: 1 0 auto; }
    .navbar {
      min-height: var(--nav-height);
      backdrop-filter: blur(10px);
      background: rgba(11, 11, 13, 0.9);
      border-bottom: 1px solid rgba(225, 6, 0, .35);
      transition: box-shadow .35s ease;
      position: relative;
      z-index: 30;
    }
    .navbar.scrolled { box-shadow: 0 16px 44px rgba(0, 0, 0, .48); }
    .navbar-brand { display: inline-flex; align-items: center; line-height: 1; padding-top: .2rem; padding-bottom: .2rem; }
    .navbar-brand img { height: 28px; max-height: 28px; width: auto; display: block; filter: brightness(0) invert(1); }
    .nav-link {
      color: var(--text-secondary);
      position: relative;
      transition: color .25s ease;
      font-weight: 600;
      letter-spacing: .04em;
      text-transform: uppercase;
      font-size: .86rem;
    }
    .nav-link::after {
      content: '';
      position: absolute;
      left: .5rem;
      right: .5rem;
      bottom: .2rem;
      height: 2px;
      border-radius: 999px;
      background: linear-gradient(90deg, var(--primary), var(--primary-hover));
      transform: scaleX(0);
      transition: transform .25s ease;
    }
    .nav-link.active, .nav-link:hover { color: #fff !important; text-shadow: 0 0 16px rgba(225, 6, 0, .3); }
    .nav-link.active::after, .nav-link:hover::after { transform: scaleX(1); }
    .hero {
      min-height: clamp(320px, 48vw, 560px);
      position: relative;
      z-index: 1;
      overflow: hidden;
      background-position: center right;
      background-size: cover;
      background-repeat: no-repeat;
    }
    .hero-content {
      padding-top: 1.25rem;
      background: var(--background-main);
    }
    .hero-panel {
      position: relative;
      z-index: 2;
      width: 100%;
      background: rgba(11, 11, 13, 0.92);
      border: 1px solid rgba(255,255,255,.12);
      border-radius: 1.15rem;
      box-shadow: 0 28px 72px rgba(0, 0, 0, .55);
      backdrop-filter: blur(14px);
    }
    .hero-content .btn-brand { padding: .8rem 1.5rem; }
    .hero-tagline { color: #fff !important; }
    .section-block {
      padding: clamp(3rem, 7vw, 5rem) 0;
      scroll-margin-top: calc(var(--nav-height) + 1rem);
      position: relative;
      z-index: 1;
      background: var(--background-main);
    }
    .section-block:nth-of-type(even) { background: var(--background-secondary); }
    .section-heading { font-weight: 800; margin-bottom: 1.2rem; color: var(--text-primary); letter-spacing: -.01em; }
    .partner-card {
      min-height: 190px;
      height: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: .65rem;
      padding: 1.25rem;
      color: var(--text-primary);
      text-decoration: none;
      border: 1px solid rgba(255, 255, 255, .1);
      border-radius: 1rem;
      background: rgba(255, 255, 255, .025);
      transition: transform .25s ease, border-color .25s ease, background .25s ease;
    }
    .partner-card:hover { color: #fff; transform: translateY(-4px); border-color: rgba(225, 6, 0, .65); background: rgba(225, 6, 0, .08); }
    .partner-logo { min-height: 72px; display: flex; align-items: center; justify-content: center; width: 100%; }
    .partner-logo img { max-height: 64px; max-width: 100%; object-fit: contain; filter: grayscale(100%); opacity: .92; transition: filter .25s ease, opacity .25s ease; }
    .partner-card:hover .partner-logo img { filter: grayscale(0); opacity: 1; }
    .partner-card strong { font-size: 1rem; line-height: 1.25; }
    .partner-type { color: var(--text-secondary); font-size: .78rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; }
    .text-secondary { color: var(--text-secondary) !important; }
    .surface-card {
      border-radius: 1rem;
      border: 1px solid rgba(255,255,255,.08);
      background: rgba(255,255,255,.02);
      box-shadow: 0 16px 34px rgba(0, 0, 0, 0.28);
    }
    .event-card { padding: 1.25rem; height: 100%; }
    .event-card h4 { font-weight: 800; }
    .event-card p, .event-card .small { color: var(--text-secondary) !important; }
    .event-card input, .event-card textarea {
      border-color: rgba(255,255,255,.12);
      background: rgba(255,255,255,.06);
      color: #fff;
    }
    .event-card input::placeholder, .event-card textarea::placeholder { color: rgba(255,255,255,.58); }
    .event-card input:focus, .event-card textarea:focus { border-color: var(--primary-hover); box-shadow: 0 0 0 .2rem rgba(225,6,0,.2); }
    .btn-brand {
      background: linear-gradient(90deg, #E10600, #FF2A1F);
      color: #fff;
      border: none;
      font-weight: 700;
      border-radius: .75rem;
      transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
    }
    .btn-brand:hover { color: #fff; transform: translateY(-2px) scale(1.01); box-shadow: 0 12px 26px rgba(225,6,0,.42); filter: brightness(1.03); }
    .btn-outline-brand {
      border: 1px solid rgba(255,255,255,.35);
      color: #fff;
      border-radius: .75rem;
      font-weight: 600;
    }
    .btn-outline-brand:hover { border-color: var(--primary-hover); color: #fff; box-shadow: 0 0 18px rgba(225,6,0,.38); }
    .page-cover { min-height: 240px; border-radius: .95rem; background-size: cover; background-position: center; margin-bottom: 1.2rem; }
    .page-content { line-height: 1.8; color: var(--text-secondary); }
    .newsletter-panel {
      background: linear-gradient(145deg, rgba(42,15,46,.45), rgba(26,26,29,.92));
      border: 1px solid rgba(255,255,255,.09);
    }
    .newsletter-panel h3 { color: #fff; }
    .newsletter-panel input { background: rgba(255,255,255,.08); color: #fff; border: 1px solid rgba(255,255,255,.12); }
    .newsletter-panel input::placeholder { color: rgba(255,255,255,.62); }
    .about-split-image {
      aspect-ratio: 3 / 4;
      width: min(100%, 460px);
      min-height: 320px;
      border-radius: 1rem;
      background-size: cover;
      background-position: center;
      margin-inline: auto;
      box-shadow: inset 0 0 0 1px rgba(255,255,255,.2), 0 18px 34px rgba(0, 0, 0, .35);
    }
    .services-grid .service-item {
      text-align: center;
      padding: 1.25rem;
      border-radius: .95rem;
      background: rgba(255,255,255,.02);
      border: 1px solid rgba(255,255,255,.08);
      height: 100%;
      transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
    }
    .services-grid .service-item:hover {
      transform: translateY(-4px);
      border-color: rgba(225,6,0,.45);
      box-shadow: 0 14px 28px rgba(0,0,0,.35), 0 0 24px rgba(225,6,0,.18);
    }
    .services-grid .service-icon {
      width: 82px;
      height: 82px;
      margin: 0 auto 1rem;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 2.1rem;
      color: #fff;
      border: 1px solid rgba(255,255,255,.18);
      background: radial-gradient(circle at 30% 30%, rgba(255,42,31,.8), rgba(225,6,0,.65) 45%, rgba(42,15,46,.95) 100%);
      box-shadow: 0 0 0 4px rgba(225,6,0,.12), 0 10px 26px rgba(225,6,0,.35);
    }
    .services-grid h5 { color: #fff; font-weight: 700; }
    .services-grid p { color: var(--text-secondary) !important; }
    .agenda-section .section-heading {
      text-align: center;
      font-weight: 500;
      letter-spacing: 0;
      text-transform: none;
      margin-bottom: 1.75rem;
    }
    .agenda-section .event-card h4 {
      font-weight: 600;
      text-transform: none;
      letter-spacing: 0;
    }
    .agenda-section .event-meta-label {
      font-weight: 500;
      color: var(--text-primary);
    }
    .contact-special {
      position: relative;
      color: #fff;
      border: none;
      background: transparent;
    }
    .contact-special .form-control { background: rgba(255,255,255,.94); border: none; }
    .full-bleed {
      width: 100%;
      margin-left: 0;
      margin-right: 0;
    }
    .site-banner { position: relative; z-index: 20; background: linear-gradient(90deg, rgba(225,6,0,.95), rgba(42,15,46,.96)); border-bottom: 1px solid rgba(255,255,255,.16); }
    .site-banner a { color: #fff; text-decoration: none; }
    .site-banner-img { max-height: 86px; width: auto; max-width: 100%; object-fit: contain; }
    .site-banner-text { color: #fff; font-weight: 800; letter-spacing: -.01em; }
    .final-cta {
      background: linear-gradient(120deg, rgba(225,6,0,.22), rgba(42,15,46,.72));
      border-top: 1px solid rgba(225,6,0,.38);
      border-bottom: 1px solid rgba(225,6,0,.2);
    }
    .final-cta h2 { font-weight: 900; letter-spacing: -.02em; }
    footer {
      flex-shrink: 0;
      border-top: 1px solid rgba(255,255,255,.08);
      background: #09090b;
      color: var(--text-secondary);
    }
    footer a { color: var(--text-secondary); text-decoration: none; }
    footer a:hover { color: #fff; }
    .fade-in { opacity: 0; transform: translateY(18px); transition: opacity .5s ease, transform .5s ease; }
    .fade-in.show { opacity: 1; transform: translateY(0); }

    .seo-landing { padding-top: clamp(5rem, 9vw, 7rem); }
    .seo-breadcrumb a { color: #fff; text-decoration-color: rgba(225,6,0,.8); }
    .seo-breadcrumb .active { color: var(--text-secondary); }
    .eyebrow { display:inline-flex; align-items:center; gap:.45rem; color:#fff; font-weight:800; text-transform:uppercase; letter-spacing:.08em; font-size:.78rem; margin-bottom:.85rem; }
    .seo-hero-card { border:1px solid rgba(255,255,255,.11); border-radius:1.25rem; padding:clamp(2rem,5vw,4rem); background:linear-gradient(135deg, rgba(11,11,13,.94), rgba(42,15,46,.72) 48%, rgba(225,6,0,.24)); box-shadow:0 28px 72px rgba(0,0,0,.46); overflow:hidden; position:relative; }
    .seo-hero-card::after { content:''; position:absolute; inset:auto -12% -42% 42%; height:260px; background:radial-gradient(circle, rgba(255,42,31,.32), transparent 62%); pointer-events:none; }
    .seo-hero-card h1, .blog-article h1 { font-weight:900; letter-spacing:-.035em; font-size:clamp(2.35rem,5vw,4.85rem); line-height:1.02; }
    .seo-hero-card .lead { color:rgba(255,255,255,.78); max-width:760px; }
    .seo-feature-panel { min-height:280px; border-radius:1rem; padding:2rem; background:rgba(255,255,255,.045); border:1px solid rgba(255,255,255,.1); display:flex; flex-direction:column; justify-content:end; }
    .seo-feature-panel i { font-size:3rem; color:var(--accent); margin-bottom:1rem; }
    .seo-feature-panel h2 { font-size:1.45rem; font-weight:800; }
    .seo-content-card h2 { font-weight:850; letter-spacing:-.02em; }
    .seo-mini-grid > div > div { height:100%; padding:1.25rem; border-radius:.95rem; background:rgba(255,255,255,.035); border:1px solid rgba(255,255,255,.08); }
    .seo-mini-grid i { color:var(--primary-hover); font-size:1.65rem; }
    .seo-mini-grid h3 { font-size:1.08rem; margin-top:.8rem; }
    .seo-mini-grid p, .seo-link-list { color:var(--text-secondary); }
    .proposal-form-card .form-label { color:var(--text-primary); font-weight:700; }
    .proposal-form-card .form-control, .proposal-form-card .form-select { background:rgba(255,255,255,.08); border-color:rgba(255,255,255,.16); color:var(--text-primary); }
    .proposal-form-card .form-control::placeholder { color:rgba(255,255,255,.55); }
    .proposal-form-card .form-text, .proposal-form-card .form-check-label { color:var(--text-secondary); }
    .seo-link-list { list-style:none; padding:0; margin:0; display:grid; gap:.7rem; }
    .seo-link-list a, .blog-card a { color:#fff; text-decoration:none; }
    .seo-link-list a:hover, .blog-card a:hover { color:var(--primary-hover); }
    .blog-card { overflow:hidden; transition:transform .22s ease, border-color .22s ease; }
    .blog-card:hover { transform:translateY(-4px); border-color:rgba(225,6,0,.45); }
    .blog-card img {
      width:100%;
      aspect-ratio:16 / 9;
      object-fit:contain;
      display:block;
      background:#08080a;
    }
    .blog-article { max-width:980px; margin-inline:auto; }
    .blog-article-cover {
      width:100%;
      height:auto;
      object-fit:contain;
      display:block;
      background:#08080a;
      border-radius:1rem;
      margin-bottom:1.5rem;
    }

    @media (max-width: 767.98px) {
      .hero {
        min-height: 0;
        aspect-ratio: 2.8 / 1;
        background-color: #050505;
        background-position: center;
        background-size: contain;
      }
      .hero-panel { background: rgba(11, 11, 13, .9); }
      .navbar-brand img { height: 24px; max-height: 24px; }
      .navbar { padding-top: .45rem; padding-bottom: .45rem; }
      .navbar .navbar-toggler { padding: .3rem .45rem; }
      .navbar .navbar-collapse {
        margin-top: .6rem;
        background: rgba(11, 11, 13, 0.97);
        border: 1px solid rgba(225, 6, 0, .35);
        border-radius: 10px;
        padding: .45rem .6rem;
      }
      .nav-link { padding: .5rem .35rem; }
    }
  </style>
</head>
  <body>
  <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
      <a class="navbar-brand" href="/#inicio">
        <img src="/chorarderir-logo.svg" alt="Chorar de Rir">
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPublico" aria-controls="menuPublico" aria-expanded="false" aria-label="Abrir menu de navegação">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="menuPublico">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link <?php echo (!$isStandaloneView && !$isEventView) ? 'active' : ''; ?>" href="/#inicio">Início</a></li>
          <?php foreach ($sectionPages as $page): ?>
            <?php $menuSlug = trim((string)($page['slug'] ?? '')); ?>
            <?php if ($menuSlug === '' || ($menuSlug === 'agenda' && !$hasAgendaEvents)) { continue; } ?>
            <li class="nav-item"><a class="nav-link" href="/#<?php echo htmlspecialchars($menuSlug); ?>"><?php echo htmlspecialchars((string)($page['title'] ?? ucfirst(str_replace('-', ' ', $menuSlug)))); ?></a></li>
          <?php endforeach; ?>
          <?php foreach ($standalonePages as $page): ?>
            <?php $menuSlug = trim((string)($page['slug'] ?? '')); ?>
            <?php if ($menuSlug === '' || $menuSlug === 'eventos-corporativos') { continue; } ?>
            <li class="nav-item"><a class="nav-link <?php echo $activeStandalonePage && (int)($activeStandalonePage['id'] ?? 0) === (int)($page['id'] ?? 0) ? 'active' : ''; ?>" href="/<?php echo htmlspecialchars($menuSlug); ?>"><?php echo htmlspecialchars((string)($page['title'] ?? ucfirst(str_replace('-', ' ', $menuSlug)))); ?></a></li>
          <?php endforeach; ?>
          <?php if (!empty($corporateEventsPage['enabled'])): ?>
            <li class="nav-item"><a class="nav-link <?php echo $activeVirtualSlug === 'eventos-corporativos' ? 'active' : ''; ?>" href="/eventos-corporativos/">Corporativos</a></li>
          <?php endif; ?>
          <li class="nav-item"><a class="nav-link" href="/blog">Blog</a></li>
          <?php if ($hasPartners): ?>
            <li class="nav-item"><a class="nav-link" href="/#parceiros">Parceiros</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

  <?php if (!empty($siteBanner['enabled']) && ($siteBanner['text'] !== '' || $siteBanner['image_url'] !== '')): ?>
    <aside class="site-banner py-3" aria-label="Destaque">
      <div class="container d-flex flex-column flex-md-row align-items-center justify-content-center gap-3 text-center text-md-start">
        <?php if ($siteBanner['image_url'] !== ''): ?>
          <a href="<?php echo htmlspecialchars(absolute_url((string)$siteBanner['url'])); ?>"><img class="site-banner-img" src="<?php echo htmlspecialchars((string)$siteBanner['image_url']); ?>" alt="Banner Chorar de Rir"></a>
        <?php endif; ?>
        <?php if ($siteBanner['text'] !== ''): ?>
          <div class="site-banner-text flex-grow-1"><?php echo htmlspecialchars((string)$siteBanner['text']); ?></div>
        <?php endif; ?>
        <a class="btn btn-light fw-bold px-4" href="<?php echo htmlspecialchars(absolute_url((string)$siteBanner['url'])); ?>"><?php echo htmlspecialchars((string)$siteBanner['button_text']); ?></a>
      </div>
    </aside>
  <?php endif; ?>

  <main class="site-main">
    <?php if ($activeVirtualPage !== null): ?>
      <section class="section-block seo-landing">
        <div class="container">
          <nav aria-label="breadcrumb" class="mb-4"><ol class="breadcrumb seo-breadcrumb"><li class="breadcrumb-item"><a href="/">Início</a></li><li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($activeVirtualPage['title']); ?></li></ol></nav>
          <article class="seo-hero-card fade-in show">
            <div class="row g-4 align-items-center">
              <div class="col-lg-7">
                <span class="eyebrow"><i class="bi bi-mic-fill"></i> Humor ao vivo • Produção • Booking</span>
                <h1><?php echo htmlspecialchars($activeVirtualPage['title']); ?></h1>
                <p class="lead"><?php echo htmlspecialchars($activeVirtualPage['description']); ?></p>
                <div class="d-flex flex-wrap gap-2 mt-4"><a class="btn btn-brand" href="<?php echo $activeVirtualSlug === 'eventos-corporativos' ? '#pedido-proposta' : '/#contactos'; ?>">Pedir proposta</a><a class="btn btn-outline-brand" href="/#agenda">Ver agenda</a></div>
              </div>
              <div class="col-lg-5">
                <?php if ($activeVirtualSlug === 'eventos-corporativos'): ?><div class="seo-feature-panel overflow-hidden p-0"><img src="<?php echo htmlspecialchars($corporateEventsImageUrl); ?>" alt="Equipa numa conferência corporativa com apresentação em palco" class="w-100" style="height:260px;object-fit:cover;"><div class="p-4"><i class="bi bi-building-check"></i><h2>Humor para empresas</h2><p><?php echo htmlspecialchars($corporateEventsPage['description']); ?></p></div></div><?php else: ?><div class="seo-feature-panel"><i class="bi bi-stars"></i><h2>Estratégia feita à medida</h2><p>Conteúdo otimizado para pesquisa sem duplicar texto, mantendo o visual escuro, vermelho e premium da Chorar de Rir.</p></div><?php endif; ?>
              </div>
            </div>
          </article>
          <div class="row g-4 mt-1">
            <div class="col-lg-8"><section class="surface-card seo-content-card p-4 p-lg-5 fade-in"><h2><?php echo $activeVirtualSlug === 'eventos-corporativos' ? htmlspecialchars($corporateEventsPage['heading']) : htmlspecialchars((string)($activeVirtualPage['heading'] ?? ($activeVirtualPage['type'] === 'local' ? 'Eventos de stand up comedy em ' . $activeVirtualPage['place'] : 'Soluções de humor ao vivo'))); ?></h2><p class="page-content"><?php echo $activeVirtualSlug === 'eventos-corporativos' ? nl2br(htmlspecialchars($corporateEventsPage['content'])) : nl2br(htmlspecialchars((string)($activeVirtualPage['content'] ?? 'Planeamos stand-up comedy, eventos de humor e experiências para empresas com curadoria de artistas, briefing, produção técnica e acompanhamento até ao fim do evento.'))); ?></p><h2>O que torna a experiência diferente</h2><div class="row g-3 seo-mini-grid"><div class="col-md-4"><div><i class="bi bi-person-check"></i><h3>Curadoria</h3><p>Humoristas adequados ao público, contexto e objetivo.</p></div></div><div class="col-md-4"><div><i class="bi bi-calendar2-check"></i><h3>Produção</h3><p>Coordenação de palco, horários, comunicação e operação.</p></div></div><div class="col-md-4"><div><i class="bi bi-geo-alt"></i><h3>Local</h3><p>Conteúdo preparado para Portugal e expansão internacional.</p></div></div></div></section></div>
            <div class="col-lg-4"><aside class="surface-card seo-content-card p-4 fade-in"><h2 class="h4">Ligações úteis</h2><ul class="seo-link-list"><li><a href="/#servicos">Serviços na homepage</a></li><li><a href="/#agenda">Eventos próximos</a></li><li><a href="<?php echo $activeVirtualSlug === 'eventos-corporativos' ? '#pedido-proposta' : '/#contactos'; ?>">Contactos e propostas</a></li><li><a href="/blog">Artigos e recursos do blog</a></li></ul><h2 class="h4 mt-4">Soluções para empresas</h2><ul class="seo-link-list"><li><a href="/humoristas-para-eventos-empresas">Humoristas para eventos de empresas</a></li><li><a href="/stand-up-comedy-para-empresas">Stand-up comedy para empresas</a></li><li><a href="/humorista-jantar-natal-empresa">Humor para jantares de Natal</a></li><li><a href="/team-building-com-humor">Team building com humor</a></li><li><a href="/booking-humoristas">Booking de humoristas</a></li><li><a href="/mestre-cerimonias-com-humor">Mestre de cerimónias com humor</a></li><li><a href="/comedy-club-para-bares-restaurantes">Comedy club para espaços</a></li><li><a href="/producao-eventos-stand-up-comedy">Produção de stand-up comedy</a></li></ul><h2 class="h4 mt-4">Perguntas frequentes</h2><h3>Como pedir orçamento?</h3><p>Indica cidade, data, público, objetivo e formato pretendido.</p><h3>Trabalham fora de Portugal?</h3><p>Sim, com páginas e conteúdo preparados para Portugal, Suíça, França e Luxemburgo.</p></aside></div>
          </div>
          <?php if ($activeVirtualSlug === 'eventos-corporativos') { render_corporate_proposal_form($corporateEventsPage, $recaptchaSiteKey, $hasRecaptcha, $msg); } ?>
        </div>
      </section>
    <?php elseif ($activeBlogIndex): ?>
      <section class="section-block seo-landing">
        <div class="container">
          <article class="seo-hero-card fade-in show mb-4"><span class="eyebrow"><i class="bi bi-journal-richtext"></i> Blog</span><h1>Blog de Stand Up Comedy e Eventos de Humor</h1><p class="lead">Guias, ideias e estratégias para criar eventos de humor, contratar humoristas e produzir experiências memoráveis.</p></article>
          <div class="row g-4">
            <?php if (count($blogPosts) === 0): ?><div class="col-12"><div class="surface-card p-4"><h2 class="h4">Novos artigos em preparação</h2><p class="text-secondary mb-0">Em breve encontra aqui guias sobre contratação de humoristas, eventos para empresas e produção de stand-up comedy.</p></div></div><?php endif; ?>
            <?php foreach ($blogPosts as $post): ?>
              <div class="col-md-6 col-xl-4"><article class="blog-card surface-card h-100 fade-in"><?php if (!empty($post['hero_image_url'])): ?><img src="<?php echo htmlspecialchars((string)$post['hero_image_url']); ?>" alt="Imagem do artigo <?php echo htmlspecialchars((string)$post['title']); ?>"><?php endif; ?><div class="p-4"><span class="eyebrow"><?php echo htmlspecialchars((string)($post['category'] ?: 'Blog')); ?></span><h2 class="h4"><a href="/blog/<?php echo htmlspecialchars((string)$post['slug']); ?>"><?php echo htmlspecialchars((string)$post['title']); ?></a></h2><p class="text-secondary"><?php echo htmlspecialchars(truncate_text((string)($post['excerpt'] ?: $post['content']), 130)); ?></p><a class="btn btn-sm btn-outline-brand" href="/blog/<?php echo htmlspecialchars((string)$post['slug']); ?>">Ler artigo</a></div></article></div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php elseif ($activeBlogPost !== null): ?>
      <section class="section-block seo-landing">
        <div class="container">
          <nav aria-label="breadcrumb" class="mb-4"><ol class="breadcrumb seo-breadcrumb"><li class="breadcrumb-item"><a href="/">Início</a></li><li class="breadcrumb-item"><a href="/blog">Blog</a></li><li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars((string)$activeBlogPost['title']); ?></li></ol></nav>
          <article class="surface-card blog-article p-4 p-lg-5 fade-in show">
            <?php if (!empty($activeBlogPost['hero_image_url'])): ?><img class="blog-article-cover" src="<?php echo htmlspecialchars((string)$activeBlogPost['hero_image_url']); ?>" alt="Imagem do artigo <?php echo htmlspecialchars((string)$activeBlogPost['title']); ?>"><?php endif; ?>
            <span class="eyebrow"><?php echo htmlspecialchars((string)($activeBlogPost['category'] ?: 'Blog')); ?><?php if (!empty($activeBlogPost['published_at'])): ?> • <?php echo htmlspecialchars((string)$activeBlogPost['published_at']); ?><?php endif; ?></span>
            <h1><?php echo htmlspecialchars((string)$activeBlogPost['title']); ?></h1>
            <?php if (!empty($activeBlogPost['excerpt'])): ?><p class="lead text-secondary"><?php echo htmlspecialchars((string)$activeBlogPost['excerpt']); ?></p><?php endif; ?>
            <div class="page-content"><?php echo safe_content($activeBlogPost['content'] ?? ''); ?></div>
            <div class="d-flex flex-wrap gap-2 mt-4"><a class="btn btn-brand" href="/#contactos">Pedir proposta</a><a class="btn btn-outline-brand" href="/blog">Voltar ao blog</a></div>
          </article>
          <?php if (!empty($activeBlogPost['show_corporate_form'])) { render_corporate_proposal_form($corporateEventsPage, $recaptchaSiteKey, $hasRecaptcha, $msg, 'blog/' . (string)$activeBlogPost['slug'], 'Pedido de proposta - ' . (string)$activeBlogPost['title']); } ?>
          <?php if (count($relatedBlogPosts) > 0): ?>
            <section class="mt-5" aria-labelledby="related-posts-title">
              <div class="d-flex justify-content-between align-items-end gap-3 mb-3"><div><span class="eyebrow"><i class="bi bi-journals"></i> Continuar a ler</span><h2 id="related-posts-title" class="section-heading h3 mb-0">Outros artigos que podem interessar</h2></div><a class="btn btn-sm btn-outline-brand" href="/blog">Ver todos</a></div>
              <div class="row g-4">
                <?php foreach ($relatedBlogPosts as $relatedPost): ?>
                  <div class="col-md-4"><article class="blog-card surface-card h-100 fade-in"><?php if (!empty($relatedPost['hero_image_url'])): ?><img src="<?php echo htmlspecialchars((string)$relatedPost['hero_image_url']); ?>" alt="Imagem do artigo <?php echo htmlspecialchars((string)$relatedPost['title']); ?>"><?php endif; ?><div class="p-4"><span class="eyebrow"><?php echo htmlspecialchars((string)($relatedPost['category'] ?: 'Blog')); ?></span><h3 class="h5"><a href="/blog/<?php echo htmlspecialchars((string)$relatedPost['slug']); ?>"><?php echo htmlspecialchars((string)$relatedPost['title']); ?></a></h3><p class="text-secondary"><?php echo htmlspecialchars(truncate_text((string)($relatedPost['excerpt'] ?: $relatedPost['content']), 100)); ?></p><a class="btn btn-sm btn-outline-brand" href="/blog/<?php echo htmlspecialchars((string)$relatedPost['slug']); ?>">Ler artigo</a></div></article></div>
                <?php endforeach; ?>
              </div>
            </section>
          <?php endif; ?>
        </div>
      </section>
    <?php elseif (!$isStandaloneView && !$isEventView): ?>
      <section id="inicio" class="hero" aria-label="Imagem de destaque" style="background-image: url('<?php echo htmlspecialchars((string)$heroBackgroundUrl); ?>');"></section>
      <section class="hero-content section-block" aria-labelledby="home-title">
        <div class="container">
          <div class="hero-panel p-4 p-lg-5 col-12 fade-in show">
            <span class="hero-comedy-icon"><i class="bi bi-mic-fill"></i></span>
            <p class="hero-tagline text-uppercase small mb-2 fw-semibold"><?php echo htmlspecialchars((string)($homeCopy['tagline'] ?? '')); ?></p>
            <h1 id="home-title" class="display-4 fw-bold mb-3"><?php echo htmlspecialchars((string)($homeCopy['title'] ?? '')); ?></h1>
            <p class="lead mb-4 text-light"><?php echo htmlspecialchars((string)($homeCopy['description'] ?? '')); ?></p>
            <div class="d-flex flex-wrap gap-2">
              <?php if ($hasAgendaEvents): ?>
                <a href="#agenda" class="btn btn-brand px-4 fw-semibold">Agenda</a>
              <?php endif; ?>
              <?php if ($hasReservableEvents): ?>
                <a href="#contactos" class="btn btn-outline-brand px-4 fw-semibold">Reservar</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </section>

      <?php $partnersRendered = false; ?>
      <?php foreach ($sectionPages as $page): ?>
        <?php $sectionId = trim((string)($page['slug'] ?? '')); ?>
        <?php if ($sectionId === '') { continue; } ?>
        <?php if ($sectionId === 'contactos' && $hasPartners && !$partnersRendered): ?>
          <?php render_partners_section($partners); ?>
          <?php $partnersRendered = true; ?>
        <?php endif; ?>
        <?php $sectionType = section_type($page); ?>
        <?php $sectionConfig = section_config($page); ?>
        <?php if ($sectionId === 'agenda' && $hasAgendaEvents): ?>
          <section id="agenda" class="section-block agenda-section">
            <div class="container">
              <?php if ($msg === 'ok'): ?>
                <div class="alert alert-success">Reserva enviada com sucesso! Vamos confirmar por email/telefone.</div>
              <?php elseif ($msg === 'error'): ?>
                <div class="alert alert-danger">Não foi possível registar a reserva. Tenta novamente.</div>
              <?php elseif ($msg === 'subscribed'): ?>
                <div class="alert alert-success">Subscrição da newsletter confirmada com sucesso.</div>
              <?php elseif ($msg === 'duplicate'): ?>
                <div class="alert alert-warning">Este email já está registado na newsletter.</div>
              <?php elseif ($msg === 'consent'): ?>
                <div class="alert alert-warning">Precisas de aceitar o consentimento RGPD para subscrever.</div>
              <?php elseif ($msg === 'gdpr'): ?>
                <div class="alert alert-warning">Para concluir a reserva, é necessário aceitar o tratamento de dados pessoais indicado no formulário.</div>
              <?php elseif ($msg === 'closed'): ?>
                <div class="alert alert-warning">As reservas para esse evento estão fechadas.</div>
              <?php elseif ($msg === 'soldout'): ?>
                <div class="alert alert-warning">Não existem lugares suficientes disponíveis para essa reserva.</div>
              <?php elseif ($msg === 'captcha'): ?>
                <div class="alert alert-warning">Validação de segurança falhou. Confirma o reCAPTCHA e tenta novamente.</div>
              <?php endif; ?>
              <h2 class="section-heading"><?php echo htmlspecialchars((string)$page['title']); ?></h2>
              <?php if ($selectedEvent !== null): ?>
                <div class="event-card surface-card fade-in show mb-4">
                  <?php if (!empty($selectedEvent['poster_url'])): ?>
                    <img src="<?php echo htmlspecialchars((string)$selectedEvent['poster_url']); ?>" alt="Cartaz de <?php echo htmlspecialchars((string)$selectedEvent['title']); ?>" class="img-fluid rounded mb-3" style="max-height: 280px; width: 100%; object-fit: cover;">
                  <?php endif; ?>
                  <h3><?php echo htmlspecialchars((string)$selectedEvent['title']); ?></h3>
                  <p class="mb-1"><span class="event-meta-label">Data:</span> <?php echo htmlspecialchars((string)$selectedEvent['date']); ?> às <?php echo htmlspecialchars(substr((string)$selectedEvent['time'], 0, 5)); ?></p>
                  <p class="mb-3"><span class="event-meta-label">Local:</span> <?php echo htmlspecialchars((string)$selectedEvent['location']); ?></p>
                  <?php if (!empty($selectedEvent['notes'])): ?>
                    <p class="text-secondary mb-3"><?php echo nl2br(htmlspecialchars((string)$selectedEvent['notes'])); ?></p>
                  <?php endif; ?>
                </div>
                <?php $eventSchemaScript = function_exists('renderEventSchema') ? renderEventSchema(build_event_schema_payload($selectedEvent)) : ''; ?>
                <?php if ($eventSchemaScript !== ''): ?>
                  <?php echo $eventSchemaScript; ?>
                <?php endif; ?>
              <?php endif; ?>
              <div class="row g-4 mb-5 justify-content-center">
                <?php foreach ($events as $event): ?>
                  <?php if ($selectedEvent !== null && (int)$event['id'] === (int)$selectedEvent['id']) { continue; } ?>
                  <div class="col-lg-6">
                    <div class="event-card surface-card fade-in">
                      <?php if (!empty($event['poster_url'])): ?>
                        <img src="<?php echo htmlspecialchars((string)$event['poster_url']); ?>" alt="Cartaz de <?php echo htmlspecialchars((string)$event['title']); ?>" class="img-fluid rounded mb-3" style="max-height: 220px; width: 100%; object-fit: cover;">
                      <?php endif; ?>
                      <h4><?php echo htmlspecialchars($event['title']); ?></h4>
                      <p class="mb-1"><span class="event-meta-label">Data:</span> <?php echo htmlspecialchars($event['date']); ?> às <?php echo htmlspecialchars(substr($event['time'], 0, 5)); ?></p>
                      <p class="mb-3"><span class="event-meta-label">Local:</span> <?php echo htmlspecialchars($event['location']); ?></p>
                      <?php
                        $capacity = (int)($event['reservation_capacity'] ?? 0);
                        $activeTickets = (int)($event['active_tickets'] ?? 0);
                        $available = $capacity > 0 ? max(0, $capacity - $activeTickets) : null;
                      ?>
                      <?php if ($available !== null): ?>
                        <p class="small text-secondary mb-3"><span class="event-meta-label">Lugares disponíveis:</span> <?php echo $available; ?> / <?php echo $capacity; ?></p>
                      <?php endif; ?>

                      <?php $externalTicketUrl = trim((string)($event['external_ticket_url'] ?? '')); ?>
                      <?php if ((int)($event['reservations_open'] ?? 0) !== 1 && $externalTicketUrl !== ''): ?>
                        <a class="btn btn-brand" href="<?php echo htmlspecialchars($externalTicketUrl); ?>" target="_blank" rel="noopener noreferrer">Comprar bilhetes</a>
                      <?php elseif ((int)($event['reservations_open'] ?? 0) !== 1): ?>
                        <div class="alert alert-secondary py-2 mb-0">Reservas fechadas para este evento.</div>
                      <?php elseif ($available !== null && $available <= 0): ?>
                        <div class="alert alert-warning py-2 mb-0">Esgotado. Não existem mais lugares disponíveis.</div>
                      <?php else: ?>
                        <button
                          type="button"
                          class="btn btn-brand"
                          data-bs-toggle="modal"
                          data-bs-target="#reserveModal"
                          data-event-id="<?php echo (int)$event['id']; ?>"
                          data-event-title="<?php echo htmlspecialchars((string)$event['title'], ENT_QUOTES); ?>"
                          data-event-date="<?php echo htmlspecialchars((string)$event['date'], ENT_QUOTES); ?>"
                          data-event-time="<?php echo htmlspecialchars(substr((string)$event['time'], 0, 5), ENT_QUOTES); ?>"
                          data-event-location="<?php echo htmlspecialchars((string)$event['location'], ENT_QUOTES); ?>"
                          data-max-tickets="<?php echo $available !== null ? (int)$available : 0; ?>"
                        >
                          Reservar lugar
                        </button>
                      <?php endif; ?>
                      <div class="mt-2 mb-2">
                        <a class="btn btn-sm btn-outline-light" href="<?php echo htmlspecialchars(event_public_url($event, $eventSlugById)); ?>">Ver detalhes</a>
                      </div>
                    </div>
                    <?php $eventSchemaScript = function_exists('renderEventSchema') ? renderEventSchema(build_event_schema_payload($event)) : ''; ?>
                    <?php if ($eventSchemaScript !== ''): ?>
                      <?php echo $eventSchemaScript; ?>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </section>
          <?php continue; ?>
        <?php endif; ?>
        <?php if ($sectionId === 'agenda' && !$hasAgendaEvents): ?>
          <?php continue; ?>
        <?php endif; ?>
        <section id="<?php echo htmlspecialchars($sectionId); ?>" class="section-block<?php echo (!empty($page['hero_image_url']) && $sectionType === 'default') ? ' pt-0' : ''; ?>">
          <div class="container">
            <?php if ($sectionType === 'about'): ?>
              <div class="p-4 p-lg-5 fade-in">
                <div class="row g-4 align-items-center">
                  <div class="col-lg-7">
                    <h2 class="section-heading mb-3"><?php echo htmlspecialchars((string)$page['title']); ?></h2>
                    <?php if (!empty($page['excerpt'])): ?><p class="lead text-secondary"><?php echo htmlspecialchars((string)$page['excerpt']); ?></p><?php endif; ?>
                    <div class="page-content mb-3"><?php echo safe_content($page['content'] ?? ''); ?></div>
                    <?php if (!empty($sectionConfig['cta_text'])): ?><p class="mb-0 fw-semibold"><?php echo htmlspecialchars((string)$sectionConfig['cta_text']); ?></p><?php endif; ?>
                  </div>
                  <div class="col-lg-5">
                    <div class="about-split-image" style="background-image:url('<?php echo htmlspecialchars((string)($page['hero_image_url'] ?: 'https://images.unsplash.com/photo-1509824227185-9c5a01ceba0d?auto=format&fit=crop&w=1400&q=80')); ?>');"></div>
                  </div>
                </div>
              </div>
            <?php elseif ($sectionType === 'services'): ?>
              <div class="p-4 p-lg-5 fade-in services-grid">
                <h2 class="section-heading mb-3 text-center"><?php echo htmlspecialchars((string)$page['title']); ?></h2>
                <?php if (!empty($page['excerpt'])): ?><p class="lead text-secondary text-center"><?php echo htmlspecialchars((string)$page['excerpt']); ?></p><?php endif; ?>
                <div class="row g-3 mt-1">
                  <?php $services = $sectionConfig['services'] ?? []; ?>
                  <?php if (is_array($services) && count($services) > 0): ?>
                    <?php foreach ($services as $service): ?>
                      <div class="col-12 col-md-6 col-lg-3">
                        <div class="service-item">
                          <span class="service-icon"><i class="<?php echo htmlspecialchars(bootstrap_icon_class((string)($service['icon'] ?? 'stars'))); ?>"></i></span>
                          <h5 class="mb-2"><?php echo htmlspecialchars((string)($service['name'] ?? 'Serviço')); ?></h5>
                          <p class="text-secondary mb-0"><?php echo htmlspecialchars((string)($service['description'] ?? '')); ?></p>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <div class="col-12"><div class="alert alert-light mb-0">Configura os serviços no backoffice para apresentar nome, ícone e descrição.</div></div>
                  <?php endif; ?>
                </div>
              </div>
            <?php elseif ($sectionType === 'contact_form'): ?>
              <?php $contactFields = $sectionConfig['contact_fields'] ?? ['name', 'email', 'message']; ?>
              <?php if (!is_array($contactFields)) { $contactFields = ['name', 'email', 'message']; } ?>
              <div class="contact-special full-bleed py-5 py-lg-6 fade-in" style="--section-bg: url('<?php echo htmlspecialchars((string)($page['hero_image_url'] ?: '')); ?>');">
                <div class="container">
                  <h2 class="text-center mb-3"><?php echo htmlspecialchars((string)$page['title']); ?></h2>
                  <?php if (!empty($sectionConfig['cta_text'])): ?><p class="text-center mb-4"><?php echo htmlspecialchars((string)$sectionConfig['cta_text']); ?></p><?php endif; ?>
                  <?php if ($msg === 'contact_ok'): ?>
                    <div class="alert alert-success col-md-8 mx-auto">Mensagem enviada com sucesso. Obrigado pelo contacto!</div>
                  <?php elseif ($msg === 'contact_error' || $msg === 'contact_captcha'): ?>
                    <div class="alert alert-danger col-md-8 mx-auto">Não foi possível enviar o contacto. Confirma os dados e tenta novamente.</div>
                  <?php endif; ?>
                  <form method="post" action="/contact.php" class="row g-3 justify-content-center">
                    <input type="hidden" name="page_slug" value="<?php echo htmlspecialchars($sectionId); ?>">
                    <?php if (in_array('name', $contactFields, true)): ?><div class="col-md-8"><input class="form-control form-control-lg" name="name" placeholder="*Nome" required></div><?php endif; ?>
                    <?php if (in_array('email', $contactFields, true)): ?><div class="col-md-8"><input type="email" class="form-control form-control-lg" name="email" placeholder="*Email" required></div><?php endif; ?>
                    <?php if (in_array('phone', $contactFields, true)): ?><div class="col-md-8"><input class="form-control form-control-lg" name="phone" placeholder="Telefone"></div><?php endif; ?>
                    <?php if (in_array('subject', $contactFields, true)): ?><div class="col-md-8"><input class="form-control form-control-lg" name="subject" placeholder="Assunto"></div><?php endif; ?>
                    <?php if (in_array('message', $contactFields, true)): ?><div class="col-md-8"><textarea class="form-control form-control-lg" name="message" rows="4" placeholder="Mensagem" required></textarea></div><?php endif; ?>
                    <?php if ($hasRecaptcha): ?>
                      <div class="col-md-8 d-flex justify-content-center">
                        <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptchaSiteKey); ?>"></div>
                      </div>
                    <?php endif; ?>
                    <div class="col-md-8 text-center">
                      <button class="btn btn-warning px-5 py-2 fw-semibold"><?php echo htmlspecialchars((string)($sectionConfig['cta_button_text'] ?? 'Enviar mensagem')); ?></button>
                    </div>
                  </form>
                </div>
              </div>
            <?php else: ?>
              <div class="p-4 p-lg-5 fade-in">
                <?php if (!empty($page['hero_image_url'])): ?>
                  <div class="page-cover" style="background-image:url('<?php echo htmlspecialchars((string)$page['hero_image_url']); ?>');"></div>
                <?php endif; ?>
                <h2 class="section-heading mb-3"><?php echo htmlspecialchars((string)$page['title']); ?></h2>
                <?php if (!empty($page['excerpt'])): ?>
                  <p class="lead text-secondary"><?php echo htmlspecialchars((string)$page['excerpt']); ?></p>
                <?php endif; ?>
                <div class="page-content"><?php echo safe_content($page['content'] ?? ''); ?></div>
              </div>
            <?php endif; ?>
          </div>
        </section>
      <?php endforeach; ?>
      <?php if ($hasPartners && !$partnersRendered): ?>
        <?php render_partners_section($partners); ?>
      <?php endif; ?>

      <?php if (count($pages) === 0): ?>
        <section class="section-block pt-0">
          <div class="container">
            <div class="alert alert-info">Cria páginas públicas para adicionar setores na home ou páginas dedicadas.</div>
          </div>
        </section>
      <?php endif; ?>

      <div class="modal fade" id="reserveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content bg-dark text-white border border-light border-opacity-10">
            <div class="modal-header border-secondary border-opacity-25">
              <h5 class="modal-title">Reservar evento</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
              <p class="small text-secondary mb-3" id="reserveEventInfo">Preenche os dados para concluir a tua reserva.</p>
              <form method="post" action="/reserve.php" class="row g-2" id="reserveModalForm">
                <input type="hidden" name="event_id" id="reserveEventId">
                <div class="col-12"><input name="customer_name" required class="form-control" placeholder="Nome"></div>
                <div class="col-md-6"><input type="email" name="customer_email" required class="form-control" placeholder="Email"></div>
                <div class="col-md-6"><input name="customer_phone" class="form-control" placeholder="Telefone"></div>
                <div class="col-md-6"><input type="number" min="1" value="1" name="tickets" id="reserveTickets" class="form-control" placeholder="Nº bilhetes"></div>
                <div class="col-12">
                  <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" value="1" name="gdpr_consent" id="reserveGdprConsent" required>
                    <label class="form-check-label small text-secondary" for="reserveGdprConsent">Autorizo o tratamento dos meus dados pessoais para gestão desta reserva, nos termos do Regulamento Geral sobre a Proteção de Dados (RGPD). Compreendo que posso retirar o consentimento a qualquer momento, sem comprometer a licitude do tratamento efetuado anteriormente.</label>
                  </div>
                </div>
                <?php if ($hasRecaptcha): ?>
                  <div class="col-12 d-flex justify-content-center">
                    <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptchaSiteKey); ?>"></div>
                  </div>
                <?php endif; ?>
                <div class="col-md-6"><button class="btn btn-brand w-100">Confirmar reserva</button></div>
                <div class="col-12"><textarea name="notes" class="form-control" rows="2" placeholder="Notas (opcional)"></textarea></div>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php elseif ($isEventView): ?>
      <section class="section-block agenda-section pt-5">
        <div class="container">
          <div class="mb-4">
            <a class="btn btn-sm btn-outline-light" href="/#agenda">← Voltar à agenda</a>
          </div>
          <div class="event-card surface-card fade-in show mb-4">
            <?php if (!empty($selectedEvent['poster_url'])): ?>
              <img src="<?php echo htmlspecialchars((string)$selectedEvent['poster_url']); ?>" alt="Cartaz de <?php echo htmlspecialchars((string)$selectedEvent['title']); ?>" class="img-fluid rounded mb-3" style="max-height: 360px; width: 100%; object-fit: cover;">
            <?php endif; ?>
            <h1><?php echo htmlspecialchars((string)$selectedEvent['title']); ?></h1>
            <p class="mb-1"><span class="event-meta-label">Data:</span> <?php echo htmlspecialchars((string)$selectedEvent['date']); ?> às <?php echo htmlspecialchars(substr((string)$selectedEvent['time'], 0, 5)); ?></p>
            <p class="mb-3"><span class="event-meta-label">Local:</span> <?php echo htmlspecialchars((string)$selectedEvent['location']); ?></p>
            <?php if (!empty($selectedEvent['notes'])): ?>
              <p class="text-secondary mb-3"><?php echo nl2br(htmlspecialchars((string)$selectedEvent['notes'])); ?></p>
            <?php endif; ?>
            <div class="row g-4 mt-2 mb-3">
              <div class="col-md-4"><h2 class="h4">Artistas</h2><p class="text-secondary"><?php echo htmlspecialchars((string)($selectedEvent['artist_details'] ?: 'Line-up de humoristas confirmado pela produção.')); ?></p></div>
              <div class="col-md-4"><h2 class="h4">Mapa e local</h2><p class="text-secondary"><?php echo htmlspecialchars((string)$selectedEvent['location']); ?></p><?php if (!empty($selectedEvent['artist_map_link'])): ?><a class="btn btn-sm btn-outline-light" href="<?php echo htmlspecialchars((string)$selectedEvent['artist_map_link']); ?>" target="_blank" rel="noopener noreferrer">Abrir mapa</a><?php endif; ?></div>
              <div class="col-md-4"><h2 class="h4">Galeria</h2><p class="text-secondary">Cartaz, imagens e conteúdos do espetáculo serão atualizados nesta página para reforçar a pesquisa orgânica.</p></div>
            </div>
            <h2 class="h4">Perguntas frequentes sobre o evento</h2>
            <div class="page-content mb-3"><h3>Como reservar?</h3><p>Usa o botão de reserva ou compra de bilhetes nesta página.</p><h3>Quando devo chegar?</h3><p>Recomendamos chegar com antecedência para garantir a melhor experiência de humor ao vivo.</p></div>
            <?php
              $selectedCapacity = (int)($selectedEvent['reservation_capacity'] ?? 0);
              $selectedActiveTickets = (int)($selectedEvent['active_tickets'] ?? 0);
              $selectedAvailable = $selectedCapacity > 0 ? max(0, $selectedCapacity - $selectedActiveTickets) : null;
            ?>
            <?php if ($selectedAvailable !== null): ?>
              <p class="small text-secondary mb-3"><span class="event-meta-label">Lugares disponíveis:</span> <?php echo $selectedAvailable; ?> / <?php echo $selectedCapacity; ?></p>
            <?php endif; ?>
            <?php $selectedExternalTicketUrl = trim((string)($selectedEvent['external_ticket_url'] ?? '')); ?>
            <?php if ((int)($selectedEvent['reservations_open'] ?? 0) !== 1 && $selectedExternalTicketUrl !== ''): ?>
              <a class="btn btn-brand" href="<?php echo htmlspecialchars($selectedExternalTicketUrl); ?>" target="_blank" rel="noopener noreferrer">Comprar bilhetes</a>
            <?php elseif ((int)($selectedEvent['reservations_open'] ?? 0) !== 1): ?>
              <div class="alert alert-secondary py-2 mb-0">Reservas fechadas para este evento.</div>
            <?php elseif ($selectedAvailable !== null && $selectedAvailable <= 0): ?>
              <div class="alert alert-warning py-2 mb-0">Esgotado. Não existem mais lugares disponíveis.</div>
            <?php else: ?>
              <div class="surface-card p-3 p-lg-4 mt-4 reservation-inline-card">
                <h2 class="h4 mb-2">Reserva o teu lugar</h2>
                <p class="small text-secondary">Preenche os dados abaixo e recebe a confirmação da reserva.</p>
                <form method="post" action="/reserve.php" class="row g-3">
                  <input type="hidden" name="event_id" value="<?php echo (int)$selectedEvent['id']; ?>">
                  <div class="col-12"><label class="form-label" for="eventReserveName">Nome</label><input id="eventReserveName" name="customer_name" required class="form-control" autocomplete="name"></div>
                  <div class="col-md-6"><label class="form-label" for="eventReserveEmail">Email</label><input id="eventReserveEmail" type="email" name="customer_email" required class="form-control" autocomplete="email"></div>
                  <div class="col-md-6"><label class="form-label" for="eventReservePhone">Telefone</label><input id="eventReservePhone" name="customer_phone" class="form-control" autocomplete="tel"></div>
                  <div class="col-md-4"><label class="form-label" for="eventReserveTickets">N.º de bilhetes</label><input id="eventReserveTickets" type="number" min="1" <?php if ($selectedAvailable !== null): ?>max="<?php echo (int)$selectedAvailable; ?>"<?php endif; ?> value="1" name="tickets" required class="form-control"></div>
                  <div class="col-12"><label class="form-label" for="eventReserveNotes">Notas <span class="text-secondary">(opcional)</span></label><textarea id="eventReserveNotes" name="notes" class="form-control" rows="2"></textarea></div>
                  <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" value="1" name="gdpr_consent" id="eventReserveGdpr" required><label class="form-check-label small text-secondary" for="eventReserveGdpr">Autorizo o tratamento dos meus dados pessoais para gestão desta reserva, nos termos do Regulamento Geral sobre a Proteção de Dados (RGPD). Compreendo que posso retirar o consentimento a qualquer momento, sem comprometer a licitude do tratamento efetuado anteriormente.</label></div></div>
                  <?php if ($hasRecaptcha): ?><div class="col-12"><div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptchaSiteKey); ?>"></div></div><?php endif; ?>
                  <div class="col-12 col-md-5"><button class="btn btn-brand btn-lg w-100">Confirmar reserva</button></div>
                </form>
              </div>
            <?php endif; ?>
          </div>
          <?php $eventSchemaScript = function_exists('renderEventSchema') ? renderEventSchema(build_event_schema_payload($selectedEvent)) : ''; ?>
          <?php if ($eventSchemaScript !== ''): ?>
            <?php echo $eventSchemaScript; ?>
          <?php endif; ?>
        </div>
      </section>
      <div class="modal fade" id="reserveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content bg-dark text-white border border-light border-opacity-10">
            <div class="modal-header border-secondary border-opacity-25">
              <h5 class="modal-title">Reservar evento</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
              <p class="small text-secondary mb-3" id="reserveEventInfo">Preenche os dados para concluir a tua reserva.</p>
              <form method="post" action="/reserve.php" class="row g-2" id="reserveModalForm">
                <input type="hidden" name="event_id" id="reserveEventId">
                <div class="col-12"><input name="customer_name" required class="form-control" placeholder="Nome"></div>
                <div class="col-md-6"><input type="email" name="customer_email" required class="form-control" placeholder="Email"></div>
                <div class="col-md-6"><input name="customer_phone" class="form-control" placeholder="Telefone"></div>
                <div class="col-md-6"><input type="number" min="1" value="1" name="tickets" id="reserveTickets" class="form-control" placeholder="Nº bilhetes"></div>
                <div class="col-12">
                  <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" value="1" name="gdpr_consent" id="reserveGdprConsent" required>
                    <label class="form-check-label small text-secondary" for="reserveGdprConsent">Autorizo o tratamento dos meus dados pessoais para gestão desta reserva, nos termos do Regulamento Geral sobre a Proteção de Dados (RGPD). Compreendo que posso retirar o consentimento a qualquer momento, sem comprometer a licitude do tratamento efetuado anteriormente.</label>
                  </div>
                </div>
                <?php if ($hasRecaptcha): ?>
                  <div class="col-12 d-flex justify-content-center">
                    <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptchaSiteKey); ?>"></div>
                  </div>
                <?php endif; ?>
                <div class="col-md-6"><button class="btn btn-brand w-100">Confirmar reserva</button></div>
                <div class="col-12"><textarea name="notes" class="form-control" rows="2" placeholder="Notas (opcional)"></textarea></div>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php else: ?>
      <section class="section-block">
        <div class="container">
          <div class="surface-card p-4 p-lg-5 fade-in show">
            <?php if (!empty($activeStandalonePage['hero_image_url'])): ?>
              <div class="page-cover" style="background-image:url('<?php echo htmlspecialchars((string)$activeStandalonePage['hero_image_url']); ?>');"></div>
            <?php endif; ?>
            <h1 class="section-heading mb-3"><?php echo htmlspecialchars((string)$activeStandalonePage['title']); ?></h1>
            <?php if (!empty($activeStandalonePage['excerpt'])): ?>
              <p class="lead text-secondary"><?php echo htmlspecialchars((string)$activeStandalonePage['excerpt']); ?></p>
            <?php endif; ?>
            <div class="page-content"><?php echo safe_content($activeStandalonePage['content'] ?? ''); ?></div>
          </div>
        </div>
      </section>
    <?php endif; ?>

  </main>

  <footer class="py-4">
    <div class="container d-flex flex-column flex-md-row gap-2 justify-content-between align-items-md-center">
      <div>
        <strong class="text-white">© <?php echo date('Y'); ?> Chorar de Rir</strong>
        <div class="small">Stand-up comedy • Produção • Booking • Experiência ao vivo</div>
      </div>
      <div class="small d-flex gap-3">
        <?php if ($hasAgendaEvents): ?><a href="#agenda">Agenda</a><?php endif; ?>
        <?php if ($hasPartners): ?><a href="#parceiros">Parceiros</a><?php endif; ?>
        <a href="/#servicos">Serviços</a>
        <a href="/blog">Blog</a>
        <a href="/#contactos">Contactos</a>
      </div>
    </div>
  </footer>

  <?php if ($hasRecaptcha): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <?php endif; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const navbar = document.querySelector('.navbar');
    const fadeItems = document.querySelectorAll('.fade-in');
    const navLinks = document.querySelectorAll('.navbar .nav-link[href*="#"]');
    const menuCollapse = document.getElementById('menuPublico');
    const bsCollapse = menuCollapse ? bootstrap.Collapse.getOrCreateInstance(menuCollapse, {toggle: false}) : null;
    const isStandaloneView = <?php echo $isStandaloneView ? 'true' : 'false'; ?>;

    window.addEventListener('scroll', () => {
      if (navbar) {
        navbar.classList.toggle('scrolled', window.scrollY > 10);
      }
    });

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('show');
        }
      });
    }, { threshold: 0.16 });
    fadeItems.forEach((item) => observer.observe(item));

    if (!isStandaloneView) {
      const sectionObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          const id = entry.target.getAttribute('id');
          navLinks.forEach((link) => {
            const href = link.getAttribute('href') || '';
            link.classList.toggle('active', href.endsWith(`#${id}`));
          });
        });
      }, { rootMargin: '-45% 0px -48% 0px', threshold: 0.01 });

      document.querySelectorAll('section[id]').forEach((section) => sectionObserver.observe(section));
    }

    navLinks.forEach((link) => {
      link.addEventListener('click', () => {
        if (window.innerWidth < 992 && bsCollapse && menuCollapse.classList.contains('show')) {
          bsCollapse.hide();
        }
      });
    });

    const reserveModal = document.getElementById('reserveModal');
    if (reserveModal) {
      reserveModal.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (!trigger) return;

        const eventId = trigger.getAttribute('data-event-id') || '';
        const title = trigger.getAttribute('data-event-title') || '';
        const date = trigger.getAttribute('data-event-date') || '';
        const time = trigger.getAttribute('data-event-time') || '';
        const location = trigger.getAttribute('data-event-location') || '';
        const maxTickets = parseInt(trigger.getAttribute('data-max-tickets') || '0', 10);

        const reserveEventId = document.getElementById('reserveEventId');
        const reserveTickets = document.getElementById('reserveTickets');
        const reserveEventInfo = document.getElementById('reserveEventInfo');

        if (reserveEventId) reserveEventId.value = eventId;
        if (reserveTickets) {
          reserveTickets.value = '1';
          if (!Number.isNaN(maxTickets) && maxTickets > 0) {
            reserveTickets.max = String(maxTickets);
          } else {
            reserveTickets.removeAttribute('max');
          }
        }
        if (reserveEventInfo) {
          reserveEventInfo.textContent = `${title} • ${date} às ${time} • ${location}`;
        }
      });

      const reserveParams = new URLSearchParams(window.location.search);
      const reserveEventIdFromUrl = (reserveParams.get('reserve_event') || '').trim();
      if (reserveEventIdFromUrl !== '') {
        const directReserveTrigger = document.querySelector(`[data-bs-target="#reserveModal"][data-event-id="${reserveEventIdFromUrl}"]`);
        if (directReserveTrigger) {
          const reserveModalInstance = bootstrap.Modal.getOrCreateInstance(reserveModal);
          reserveModalInstance.show(directReserveTrigger);
        }
      }
    }

    if (window.location.search.includes('msg=')) {
      history.replaceState(null, '', window.location.pathname + window.location.hash);
    }
  </script>
</body>
</html>
PHP;

        $template = str_replace('__SITE_BANNER_JSON__', addslashes($siteBannerJson), $template);
        $template = str_replace('__CORPORATE_EVENTS_PAGE_JSON__', str_replace("'", "\\'", (string)$corporateEventsPageJson), $template);
        $template = str_replace('__DB_PATH__', addslashes($dbPath), $template);
        $template = str_replace('__HOME_COPY_JSON__', addslashes((string)$homeCopyJson), $template);
        $template = str_replace('__NEWSLETTER_CONSENT__', addslashes($newsletterConsentText), $template);
        return str_replace('__RECAPTCHA_SITE_KEY__', addslashes(trim($recaptchaSiteKey)), $template);
    }

    private function buildCleanPageRouter(string $slug): string
    {
        $safeSlug = var_export($slug, true);
        return <<<PHP
<?php
\$_GET['page'] = {$safeSlug};
require dirname(__DIR__) . '/index.php';
PHP;
    }

    private function buildIndexHtmlRedirect(): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="pt">
<head>
  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-TPWEL0LHMH"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-TPWEL0LHMH');
  </script>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chorar de Rir</title>
  <link rel="icon" type="image/svg+xml" href="/chorarderir-logo.svg">
  <meta http-equiv="refresh" content="0; url=/">
  <script>
    (function () {
      var query = window.location.search || '';
      var hash = window.location.hash || '';
      window.location.replace('/' + query + hash);
    })();
  </script>
</head>
<body>
  <p>A redirecionar para <a href="/">/</a>…</p>
</body>
</html>
HTML;
    }

    private function buildHtaccess(): string
    {
        return <<<'HTACCESS'
DirectoryIndex index.php

RewriteEngine On
# Redirect the legacy query-string URL before the internal clean-URL rewrite.
RewriteCond %{QUERY_STRING} (^|&)page=eventos-corporativos(&|$) [NC]
RewriteRule ^(?:index\.php)?$ /eventos-corporativos/ [R=301,L,NE,QSD]
RewriteRule ^sitemap\.xml$ sitemap.php [L]
RewriteRule ^eventos/([^/]+)/?$ index.php?evento=$1 [L,QSA]
RewriteRule ^blog/?$ index.php?page=blog [L,QSA]
RewriteRule ^blog/([^/]+)/?$ index.php?page=blog/$1 [L,QSA]
RewriteRule ^(stand-up-comedy|eventos-de-humor|eventos-corporativos|humoristas-para-eventos-empresas|humorista-jantar-natal-empresa|team-building-com-humor|stand-up-comedy-para-empresas|booking-humoristas|mestre-cerimonias-com-humor|comedy-club-para-bares-restaurantes|producao-eventos-stand-up-comedy|booking-de-humoristas|producao-de-eventos|stand-up-comedy-portugal|stand-up-comedy-aveiro|stand-up-comedy-porto|stand-up-comedy-lisboa|stand-up-comedy-braga|stand-up-comedy-coimbra|stand-up-comedy-faro|stand-up-comedy-suica|stand-up-comedy-franca|stand-up-comedy-luxemburgo)/?$ index.php?page=$1 [L,QSA]
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/css text/javascript application/javascript application/json application/xml image/svg+xml
</IfModule>
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType image/svg+xml "access plus 1 year"
  ExpiresByType text/css "access plus 1 month"
  ExpiresByType application/javascript "access plus 1 month"
</IfModule>
HTACCESS;
    }

    private function buildRobotsTxt(): string
    {
        return "User-agent: *\nAllow: /\nSitemap: https://chorarderir.com/sitemap.xml\n";
    }

    private function buildSitemapGenerator(string $dbPath, array $corporateEventsPage): string
    {
        $includeCorporateEventsPage = !empty($corporateEventsPage['enabled']) ? '1' : '0';
        $template = <<<'PHP'
<?php
header('Content-Type: application/xml; charset=UTF-8');
$baseUrl = 'https://chorarderir.com';
function sitemap_slug(string $value): string {
    $value = trim(function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value));
    $value = strtr($value, ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','í'=>'i','ì'=>'i','î'=>'i','ï'=>'i','ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o','ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n']);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: '';
    return trim($value, '-') ?: 'pagina';
}
$urls = [['loc' => $baseUrl . '/', 'priority' => '1.0']];
$static = ['stand-up-comedy','eventos-de-humor','eventos-corporativos','humoristas-para-eventos-empresas','humorista-jantar-natal-empresa','team-building-com-humor','stand-up-comedy-para-empresas','booking-humoristas','mestre-cerimonias-com-humor','comedy-club-para-bares-restaurantes','producao-eventos-stand-up-comedy','booking-de-humoristas','producao-de-eventos','stand-up-comedy-portugal','stand-up-comedy-aveiro','stand-up-comedy-porto','stand-up-comedy-lisboa','stand-up-comedy-braga','stand-up-comedy-coimbra','stand-up-comedy-faro','stand-up-comedy-suica','stand-up-comedy-franca','stand-up-comedy-luxemburgo'];
if ('__INCLUDE_CORPORATE_EVENTS_PAGE__' !== '1') {
    $static = array_values(array_diff($static, ['eventos-corporativos']));
}
foreach ($static as $slug) { $urls[] = ['loc' => $baseUrl . '/' . $slug . ($slug === 'eventos-corporativos' ? '/' : ''), 'priority' => '0.8']; }
try {
    $db = new PDO('sqlite:__DB_PATH__', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $pageColumns = array_column($db->query('PRAGMA table_info(public_pages)')->fetchAll(), 'name');
    if ($pageColumns) {
        $where = in_array('is_published', $pageColumns, true) ? ' WHERE is_published = 1' : '';
        foreach (($db->query('SELECT slug FROM public_pages' . $where)->fetchAll() ?: []) as $page) {
            $slug = trim((string)($page['slug'] ?? ''));
            if ($slug !== '') { $urls[] = ['loc' => $baseUrl . '/' . sitemap_slug($slug), 'priority' => '0.7']; }
        }
    }
    $blogColumns = array_column($db->query('PRAGMA table_info(blog_posts)')->fetchAll(), 'name');
    if ($blogColumns) {
        $publishedPosts = $db->query('SELECT slug, published_at FROM blog_posts WHERE is_published = 1 ORDER BY sort_order ASC, published_at DESC')->fetchAll() ?: [];
        $urls[] = ['loc' => $baseUrl . '/blog', 'priority' => '0.8'];
        foreach ($publishedPosts as $post) {
            $urls[] = ['loc' => $baseUrl . '/blog/' . sitemap_slug((string)$post['slug']), 'priority' => '0.7', 'lastmod' => (string)($post['published_at'] ?? '')];
        }
    }

    $eventColumns = array_column($db->query('PRAGMA table_info(events)')->fetchAll(), 'name');
    if ($eventColumns) {
        $where = in_array('is_visible', $eventColumns, true) ? ' AND is_visible = 1' : '';
        foreach (($db->query("SELECT id, title, date FROM events WHERE date >= date('now')" . $where . " ORDER BY date ASC")->fetchAll() ?: []) as $event) {
            $urls[] = ['loc' => $baseUrl . '/eventos/' . sitemap_slug((string)$event['title']), 'priority' => '0.9', 'lastmod' => (string)$event['date']];
        }
    }
} catch (Throwable $e) {}
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
$seen = [];
foreach ($urls as $url) {
    if (isset($seen[$url['loc']])) { continue; }
    $seen[$url['loc']] = true;
    echo "  <url><loc>" . htmlspecialchars($url['loc'], ENT_XML1) . "</loc>";
    if (!empty($url['lastmod'])) { echo "<lastmod>" . htmlspecialchars($url['lastmod'], ENT_XML1) . "</lastmod>"; }
    echo "<changefreq>weekly</changefreq><priority>" . htmlspecialchars($url['priority'], ENT_XML1) . "</priority></url>\n";
}
echo "</urlset>\n";
PHP;
        $template = str_replace('__INCLUDE_CORPORATE_EVENTS_PAGE__', $includeCorporateEventsPage, $template);
        return str_replace('__DB_PATH__', addslashes($dbPath), $template);
    }

    private function buildReserveHandler(string $dbPath, string $recaptchaSecretKey): string
    {
        $template = <<<'PHP'
<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

if ((string)($_POST['gdpr_consent'] ?? '') !== '1') {
    header('Location: /?msg=gdpr#eventos');
    exit;
}

$captchaSecret = trim((string)'__RECAPTCHA_SECRET_KEY__');
if ($captchaSecret !== '') {
    $captchaToken = trim((string)($_POST['g-recaptcha-response'] ?? ''));
    if ($captchaToken === '') {
        header('Location: /?msg=captcha#eventos');
        exit;
    }

    $captchaCheck = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query([
                'secret' => $captchaSecret,
                'response' => $captchaToken,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]),
            'timeout' => 8,
        ],
    ]));
    $captchaResult = is_string($captchaCheck) ? json_decode($captchaCheck, true) : null;
    if (!is_array($captchaResult) || empty($captchaResult['success'])) {
        header('Location: /?msg=captcha#eventos');
        exit;
    }
}

try {
    $db = new PDO('sqlite:__DB_PATH__', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $eventId = (int)($_POST['event_id'] ?? 0);
    $tickets = max(1, (int)($_POST['tickets'] ?? 1));

    $eventStmt = $db->prepare("SELECT e.id, e.title, e.date, e.time, e.reservations_open, e.reservation_capacity, COALESCE(SUM(CASE WHEN r.status != 'cancelled' THEN r.tickets ELSE 0 END), 0) AS active_tickets FROM events e LEFT JOIN event_reservations r ON r.event_id = e.id WHERE e.id = :event_id GROUP BY e.id");
    $eventStmt->execute(['event_id' => $eventId]);
    $event = $eventStmt->fetch();

    if (!$event || (int)$event['reservations_open'] !== 1) {
        header('Location: /?msg=closed#eventos');
        exit;
    }

    $capacity = (int)($event['reservation_capacity'] ?? 0);
    $activeTickets = (int)($event['active_tickets'] ?? 0);
    if ($capacity > 0 && ($activeTickets + $tickets) > $capacity) {
        header('Location: /?msg=soldout#eventos');
        exit;
    }

    $consentText = 'Autorizo o tratamento dos meus dados pessoais para gestão desta reserva, nos termos do Regulamento Geral sobre a Proteção de Dados (RGPD). Compreendo que posso retirar o consentimento a qualquer momento, sem comprometer a licitude do tratamento efetuado anteriormente.';
    $stmt = $db->prepare('INSERT INTO event_reservations (event_id, customer_name, customer_email, customer_phone, tickets, notes, gdpr_consent, gdpr_consent_at, gdpr_consent_text, status) VALUES (:event_id, :customer_name, :customer_email, :customer_phone, :tickets, :notes, 1, CURRENT_TIMESTAMP, :gdpr_consent_text, :status)');

    $stmt->execute([
        'event_id' => $eventId,
        'customer_name' => trim((string)($_POST['customer_name'] ?? '')),
        'customer_email' => trim((string)($_POST['customer_email'] ?? '')),
        'customer_phone' => trim((string)($_POST['customer_phone'] ?? '')),
        'tickets' => $tickets,
        'notes' => trim((string)($_POST['notes'] ?? '')) ?: null,
        'gdpr_consent_text' => $consentText,
        'status' => 'new',
    ]);
    $reservationId = (int)$db->lastInsertId();

    $db->exec(
        'CREATE TABLE IF NOT EXISTS event_reservation_tickets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            reservation_id INTEGER NOT NULL,
            event_id INTEGER NOT NULL,
            ticket_no INTEGER NOT NULL,
            ticket_token TEXT NOT NULL UNIQUE,
            qr_payload TEXT NOT NULL,
            is_used INTEGER NOT NULL DEFAULT 0,
            used_at TEXT DEFAULT NULL,
            used_by_user_id INTEGER DEFAULT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $db->exec(
        'CREATE TABLE IF NOT EXISTS site_settings (
            setting_key TEXT PRIMARY KEY,
            setting_value TEXT NOT NULL,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $settingStmt = $db->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('reservation_email_template_a', 'reservation_email_template_b', 'reservation_email_template_selected', 'reservation_validation_base_url')");
    $rows = $settingStmt->fetchAll() ?: [];
    $settings = [];
    foreach ($rows as $row) {
        $settings[(string)$row['setting_key']] = (string)$row['setting_value'];
    }

    $defaultTemplateA = "Olá {customer_name},\n\nRecebemos a tua reserva para \"{event_title}\" no dia {event_date} às {event_time}.\nBilhetes reservados: {tickets}.\n\nObrigado!";
    $defaultTemplateB = "Olá {customer_name},\n\nA tua reserva para \"{event_title}\" foi submetida com sucesso.\nData: {event_date} às {event_time}\nNº de bilhetes: {tickets}\n\nEntraremos em contacto em breve para confirmação final.";
    $templateA = trim((string)($settings['reservation_email_template_a'] ?? '')) !== '' ? (string)$settings['reservation_email_template_a'] : $defaultTemplateA;
    $templateB = trim((string)($settings['reservation_email_template_b'] ?? '')) !== '' ? (string)$settings['reservation_email_template_b'] : $defaultTemplateB;
    $selectedTemplate = (string)($settings['reservation_email_template_selected'] ?? 'a');
    $bodyTemplate = $selectedTemplate === 'b' ? $templateB : $templateA;
    $validationBaseUrl = trim((string)($settings['reservation_validation_base_url'] ?? ''));
    if ($validationBaseUrl === '') {
        $validationBaseUrl = 'RESERVA:';
    }

    $customerName = trim((string)($_POST['customer_name'] ?? ''));
    $customerEmail = trim((string)($_POST['customer_email'] ?? ''));
    $customerPhone = trim((string)($_POST['customer_phone'] ?? ''));
    $messageBody = strtr($bodyTemplate, [
        '{customer_name}' => $customerName,
        '{event_title}' => (string)($event['title'] ?? ''),
        '{event_date}' => (string)($event['date'] ?? ''),
        '{event_time}' => substr((string)($event['time'] ?? ''), 0, 5),
        '{tickets}' => (string)$tickets,
        '{customer_email}' => $customerEmail,
        '{customer_phone}' => $customerPhone,
    ]);

    $ticketInsert = $db->prepare('INSERT INTO event_reservation_tickets (reservation_id, event_id, ticket_no, ticket_token, qr_payload) VALUES (:reservation_id, :event_id, :ticket_no, :ticket_token, :qr_payload)');
    $ticketsData = [];
    for ($ticketNo = 1; $ticketNo <= $tickets; $ticketNo++) {
        $token = bin2hex(random_bytes(16));
        $qrPayload = substr($validationBaseUrl, 0, 4) === 'http'
            ? $validationBaseUrl . urlencode($token)
            : ('RESERVA:' . $token);
        $ticketInsert->execute([
            'reservation_id' => $reservationId,
            'event_id' => $eventId,
            'ticket_no' => $ticketNo,
            'ticket_token' => $token,
            'qr_payload' => $qrPayload,
        ]);
        $ticketsData[] = [
            'ticket_no' => $ticketNo,
            'token' => $token,
            'payload' => $qrPayload,
        ];
    }

    if ($customerEmail !== '') {
        $subject = 'Confirmação de reserva - ' . (string)($event['title'] ?? 'Evento');
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: noreply@chorarderir.com',
            'Reply-To: noreply@chorarderir.com',
        ];

        $eventDate = htmlspecialchars((string)($event['date'] ?? ''));
        $eventTime = htmlspecialchars(substr((string)($event['time'] ?? ''), 0, 5));
        $eventTitle = htmlspecialchars((string)($event['title'] ?? 'Evento'));
        $customerNameSafe = htmlspecialchars($customerName);
        $intro = nl2br(htmlspecialchars($messageBody));
        $logoUrl = 'https://chorarderir.com/chorarderir-logo.svg';
        $ticketHtml = '';
        foreach ($ticketsData as $ticket) {
            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=230x230&data=' . rawurlencode((string)$ticket['payload']);
            $ticketHtml .= '<div style="border:1px solid #dedede;border-left:4px solid #b30000;border-radius:10px;padding:18px;margin:14px 0;background:#fafafa">'
                . '<p style="margin:0 0 10px;font-size:18px;font-weight:700">Bilhete #' . (int)$ticket['ticket_no'] . '</p>'
                . '<p style="margin:0 0 8px">Evento: <strong>' . $eventTitle . '</strong><br>Data: ' . $eventDate . ' às ' . $eventTime . '</p>'
                . '<p style="margin:0 0 8px;font-size:12px;color:#475569">Token: ' . htmlspecialchars((string)$ticket['token']) . '</p>'
                . '<img src="' . htmlspecialchars($qrUrl) . '" alt="QR Bilhete #' . (int)$ticket['ticket_no'] . '" width="200" height="200" style="display:block;max-width:100%;height:auto;margin:14px auto 0">'
                . '</div>';
        }
        $htmlBody = '<div style="margin:0;padding:20px;background:#f4f4f4;font-family:Arial,sans-serif;color:#151515">'
            . '<div style="max-width:680px;margin:0 auto;background:#fff;border:1px solid #ddd;border-radius:14px;overflow:hidden">'
            . '<div style="padding:22px 24px;background:#050505;border-bottom:5px solid #b30000"><img src="' . htmlspecialchars($logoUrl) . '" alt="Chorar de Rir" width="190" style="display:block;max-width:55%;height:auto;filter:invert(1)"></div>'
            . '<div style="padding:28px 24px">'
            . '<div style="font-size:16px;line-height:1.6">' . $intro . '</div>'
            . $ticketHtml
            . '<p style="margin-top:22px;padding-top:16px;border-top:1px solid #e5e5e5;color:#666;font-size:13px">Cada QR code só pode ser validado uma vez. Guarda este e-mail até ao dia do evento.</p>'
            . '</div></div></div>';

        @mail($customerEmail, $subject, $htmlBody, implode("\r\n", $headers));
    }

    header('Location: /?msg=ok#eventos');
    exit;
} catch (Throwable $e) {
    header('Location: /?msg=error#eventos');
    exit;
}
PHP;

        $template = str_replace('__DB_PATH__', addslashes($dbPath), $template);
        return str_replace('__RECAPTCHA_SECRET_KEY__', addslashes(trim($recaptchaSecretKey)), $template);
    }

    private function buildSubscribeHandler(string $dbPath, string $recaptchaSecretKey): string
    {
        $template = <<<'PHP'
<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

$captchaSecret = trim((string)'__RECAPTCHA_SECRET_KEY__');
if ($captchaSecret !== '') {
    $captchaToken = trim((string)($_POST['g-recaptcha-response'] ?? ''));
    if ($captchaToken === '') {
        header('Location: /?msg=captcha#newsletter');
        exit;
    }

    $captchaCheck = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query([
                'secret' => $captchaSecret,
                'response' => $captchaToken,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]),
            'timeout' => 8,
        ],
    ]));
    $captchaResult = is_string($captchaCheck) ? json_decode($captchaCheck, true) : null;
    if (!is_array($captchaResult) || empty($captchaResult['success'])) {
        header('Location: /?msg=captcha#newsletter');
        exit;
    }
}

$email = trim((string)($_POST['email'] ?? ''));
$name = trim((string)($_POST['name'] ?? ''));
$consent = (int)($_POST['gdpr_consent'] ?? 0);

if ($email === '' || $consent !== 1) {
    header('Location: /?msg=consent#newsletter');
    exit;
}

try {
    $db = new PDO('sqlite:__DB_PATH__', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $db->exec(
        'CREATE TABLE IF NOT EXISTS newsletter_subscriptions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            name TEXT DEFAULT NULL,
            gdpr_consent INTEGER NOT NULL DEFAULT 0,
            consent_text TEXT NOT NULL,
            source TEXT DEFAULT NULL,
            status TEXT NOT NULL DEFAULT "active" CHECK (status IN ("active", "unsubscribed")),
            subscribed_at TEXT DEFAULT CURRENT_TIMESTAMP,
            unsubscribed_at TEXT DEFAULT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $stmt = $db->prepare('INSERT INTO newsletter_subscriptions (email, name, gdpr_consent, consent_text, source, status) VALUES (:email, :name, :gdpr_consent, :consent_text, :source, :status)');
    $stmt->execute([
        'email' => $email,
        'name' => $name !== '' ? $name : null,
        'gdpr_consent' => 1,
        'consent_text' => '__NEWSLETTER_CONSENT__',
        'source' => 'public_site',
        'status' => 'active',
    ]);

    header('Location: /?msg=subscribed#newsletter');
    exit;
} catch (Throwable $e) {
    $code = (string)$e->getCode();
    if ($code === '23000') {
        header('Location: /?msg=duplicate#newsletter');
        exit;
    }

    header('Location: /?msg=error#newsletter');
    exit;
}
PHP;

        $template = str_replace('__DB_PATH__', addslashes($dbPath), $template);
        $template = str_replace('__NEWSLETTER_CONSENT__', addslashes(trim((string)($_POST['newsletter_consent_text'] ?? ''))), $template);
        return str_replace('__RECAPTCHA_SECRET_KEY__', addslashes(trim($recaptchaSecretKey)), $template);
    }

    private function buildContactHandler(string $dbPath, string $recaptchaSecretKey): string
    {
        $template = <<<'PHP'
<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

$pageSlug = trim((string)($_POST['page_slug'] ?? 'contactos'));
$isBlogPost = preg_match('#^blog/[a-z0-9-]+$#', $pageSlug) === 1;
$anchor = ($pageSlug === 'eventos-corporativos' || $isBlogPost) ? 'pedido-proposta' : ($pageSlug !== '' ? $pageSlug : 'contactos');
$returnBase = $pageSlug === 'eventos-corporativos' ? '/eventos-corporativos/' : ($isBlogPost ? '/' . $pageSlug : '/');
$captchaSecret = trim((string)'__RECAPTCHA_SECRET_KEY__');
if ($captchaSecret !== '') {
    $captchaToken = trim((string)($_POST['g-recaptcha-response'] ?? ''));
    if ($captchaToken === '') {
        header('Location: ' . $returnBase . '?msg=contact_captcha#' . rawurlencode($anchor));
        exit;
    }

    $captchaCheck = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query([
                'secret' => $captchaSecret,
                'response' => $captchaToken,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]),
            'timeout' => 8,
        ],
    ]));
    $captchaResult = is_string($captchaCheck) ? json_decode($captchaCheck, true) : null;
    if (!is_array($captchaResult) || empty($captchaResult['success'])) {
        header('Location: ' . $returnBase . '?msg=contact_captcha#' . rawurlencode($anchor));
        exit;
    }
}

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$subject = trim((string)($_POST['subject'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));
$proposalFields = [
    'company' => 'Empresa',
    'event_date' => 'Data prevista',
    'event_location' => 'Local / cidade',
    'audience_size' => 'Nº de participantes',
    'event_format' => 'Formato pretendido',
];
$proposalDetails = [];
foreach ($proposalFields as $fieldName => $fieldLabel) {
    $fieldValue = trim((string)($_POST[$fieldName] ?? ''));
    if ($fieldValue !== '') {
        $proposalDetails[] = $fieldLabel . ': ' . $fieldValue;
    }
}

if ($email === '' || $message === '') {
    header('Location: ' . $returnBase . '?msg=contact_error#' . rawurlencode($anchor));
    exit;
}

try {
    $db = new PDO('sqlite:__DB_PATH__', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $stmt = $db->prepare('SELECT section_config_json FROM public_pages WHERE slug = :slug LIMIT 1');
    $stmt->execute(['slug' => $pageSlug]);
    $page = $stmt->fetch();
    $config = [];
    if ($page && !empty($page['section_config_json'])) {
        $decoded = json_decode((string)$page['section_config_json'], true);
        if (is_array($decoded)) {
            $config = $decoded;
        }
    }

    $emailTo = trim((string)($config['contact_email_to'] ?? ''));
    if ($pageSlug === 'eventos-corporativos') {
        $db->exec('CREATE TABLE IF NOT EXISTS site_settings (setting_key TEXT PRIMARY KEY, setting_value TEXT NOT NULL, updated_at TEXT DEFAULT CURRENT_TIMESTAMP)');
        $settingStmt = $db->prepare('SELECT setting_value FROM site_settings WHERE setting_key = :key LIMIT 1');
        $settingStmt->execute(['key' => 'corporate_events_contact_email_to']);
        $settingValue = $settingStmt->fetchColumn();
        if (is_string($settingValue) && trim($settingValue) !== '') {
            $emailTo = trim($settingValue);
        }
    }
    if ($emailTo === '') {
        $emailTo = 'info@chorarderir.com';
    }

    $mailSubject = $subject !== '' ? 'Novo contacto: ' . $subject : 'Novo contacto do website';
    $mailBody = "Novo contacto recebido no website.\n\n"
        . "Nome: " . ($name !== '' ? $name : '-') . "\n"
        . "Email: " . $email . "\n"
        . "Telefone: " . ($phone !== '' ? $phone : '-') . "\n"
        . "Assunto: " . ($subject !== '' ? $subject : '-') . "\n"
        . (count($proposalDetails) > 0 ? "\nDetalhes do pedido de proposta:\n" . implode("\n", $proposalDetails) . "\n" : "")
        . "\nMensagem:\n" . $message . "\n";

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/plain; charset=UTF-8',
        'From: noreply@chorarderir.com',
        'Reply-To: ' . $email,
    ];

    @mail($emailTo, $mailSubject, $mailBody, implode("\r\n", $headers));

    header('Location: ' . $returnBase . '?msg=contact_ok#' . rawurlencode($anchor));
    exit;
} catch (Throwable $e) {
    header('Location: ' . $returnBase . '?msg=contact_error#' . rawurlencode($anchor));
    exit;
}
PHP;

        $template = str_replace('__DB_PATH__', addslashes($dbPath), $template);
        return str_replace('__RECAPTCHA_SECRET_KEY__', addslashes(trim($recaptchaSecretKey)), $template);
    }
}
