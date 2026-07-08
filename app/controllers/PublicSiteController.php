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
        $homeBackgroundUrl = $settings->get('home_background_url', 'https://images.unsplash.com/photo-1527224857830-43a7acc85260?auto=format&fit=crop&w=1800&q=80');
        $newsletterConsentText = $settings->get('newsletter_consent_text', 'Autorizo o tratamento dos meus dados para receber comunicações de eventos e novidades, de acordo com o RGPD.');
        $recaptchaSiteKey = $settings->get('recaptcha_site_key', '6LcrsLOsAAAAAB9NZ-X2s7ugJ7LsNAamg4VXW0wt');
        $recaptchaSecretKey = $settings->get('recaptcha_secret_key', '6LcrsLOsAAAAAJniWgy3I-C6PPXk_yTlfFc2U-Hi');

        $this->render('public_site/index', compact('defaultPath', 'pages', 'homeTagline', 'homeTitle', 'homeDescription', 'homeBackgroundUrl', 'newsletterConsentText', 'recaptchaSiteKey', 'recaptchaSecretKey'));
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

            if (file_put_contents($targetPath . '/index.php', $this->buildPublicIndex($dbPath, $homeCopy, $newsletterConsentText, $recaptchaSiteKey)) === false) {
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
            if (file_put_contents($targetPath . '/sitemap.php', $this->buildSitemapGenerator($dbPath)) === false) {
                throw new RuntimeException('Falha ao escrever sitemap.php no destino.');
            }
            if (file_put_contents($targetPath . '/robots.txt', $this->buildRobotsTxt()) === false) {
                throw new RuntimeException('Falha ao escrever robots.txt no destino.');
            }
            if (file_put_contents($targetPath . '/.htaccess', $this->buildHtaccess()) === false) {
                throw new RuntimeException('Falha ao escrever .htaccess no destino.');
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

    private function buildPublicIndex(string $dbPath, array $homeCopy, string $newsletterConsentText, string $recaptchaSiteKey): string
    {
        $homeCopyJson = json_encode($homeCopy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $template = <<<'PHP'
<?php
$events = [];
$pages = [];
$partners = [];
$homeCopy = json_decode('__HOME_COPY_JSON__', true) ?: [];
$msg = $_GET['msg'] ?? '';
$pageSlug = trim((string)($_GET['page'] ?? ''));
$eventSlug = trim((string)($_GET['evento'] ?? ''));
$recaptchaSiteKey = trim((string)'__RECAPTCHA_SITE_KEY__');
$hasRecaptcha = $recaptchaSiteKey !== '';

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
}

$siteTitle = 'Chorar de Rir';
$defaultHomeCopy = [
    'tagline' => 'Produção • Booking • Experiências',
    'title' => 'Humor e espetáculos com um palco inesquecível.',
    'description' => 'Eventos únicos, noites memoráveis e talento nacional num ambiente vibrante.',
    'background_url' => 'https://images.unsplash.com/photo-1527224857830-43a7acc85260?auto=format&fit=crop&w=1800&q=80',
];
$homeCopy = [
    'tagline' => trim((string)($homeCopy['tagline'] ?? '')) !== '' ? (string)$homeCopy['tagline'] : $defaultHomeCopy['tagline'],
    'title' => trim((string)($homeCopy['title'] ?? '')) !== '' ? (string)$homeCopy['title'] : $defaultHomeCopy['title'],
    'description' => trim((string)($homeCopy['description'] ?? '')) !== '' ? (string)$homeCopy['description'] : $defaultHomeCopy['description'],
    'background_url' => trim((string)($homeCopy['background_url'] ?? '')) !== '' ? (string)$homeCopy['background_url'] : $defaultHomeCopy['background_url'],
];
$heroBackgroundUrl = trim((string)($homeCopy['background_url'] ?? ''));
if ($heroBackgroundUrl === '') {
    $heroBackgroundUrl = $defaultHomeCopy['background_url'];
}

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
        return 'index.php#agenda';
    }
    return 'index.php?evento=' . rawurlencode((string)$eventSlugById[$id]);
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
    'eventos-corporativos' => ['title' => 'Eventos Corporativos de Humor', 'type' => 'service', 'description' => 'Humor para empresas, convenções, festas de equipa e ativações internas com linguagem adaptada à marca.'],
    'team-building-com-humor' => ['title' => 'Team Building com Humor', 'type' => 'service', 'description' => 'Dinâmicas de team building com humor para aproximar equipas, aumentar energia e criar memórias positivas.'],
    'booking-de-humoristas' => ['title' => 'Booking de Humoristas', 'type' => 'service', 'description' => 'Contratar humorista ou contratar stand up comedy com acompanhamento profissional, briefing e gestão logística.'],
    'producao-de-eventos' => ['title' => 'Produção de Eventos de Humor', 'type' => 'service', 'description' => 'Produção integral de espetáculos de humor: conceito, agenda, artistas, bilheteira, comunicação e experiência de público.'],
];
$localPages = [];
foreach (['Portugal','Aveiro','Porto','Lisboa','Braga','Coimbra','Faro','Suíça','França','Luxemburgo'] as $place) {
    $slug = 'stand-up-comedy-' . seo_slug($place);
    $localPages[$slug] = ['title' => 'Stand Up Comedy e Eventos de Humor em ' . $place, 'type' => 'local', 'place' => $place, 'description' => 'Organização de stand up comedy, eventos de humor, humor ao vivo e booking de humoristas para ' . $place . '.'];
}
$blogPages = [
    'blog' => ['title' => 'Blog de Stand Up Comedy e Eventos de Humor', 'type' => 'blog', 'description' => 'Guias, ideias e estratégias para eventos de humor, comédia ao vivo, humor para empresas e produção de espetáculos.'],
    'blog/como-contratar-humorista' => ['title' => 'Como contratar um humorista para o teu evento', 'type' => 'article', 'description' => 'Checklist prático para contratar humorista, definir briefing, duração, logística, orçamento e promoção do evento.'],
    'blog/team-building-com-humor' => ['title' => 'Team building com humor: como funciona', 'type' => 'article', 'description' => 'Ideias para usar comédia ao vivo e dinâmicas de humor para envolver equipas em eventos corporativos.'],
];
$virtualPages = $servicePages + $localPages + $blogPages;
$activeVirtualSlug = $currentPath !== '' ? $currentPath : $pageSlug;
$activeVirtualPage = $virtualPages[$activeVirtualSlug] ?? null;
if ($activeVirtualPage !== null) { $isStandaloneView = false; $isEventView = false; }

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
                      <div class="col-6 col-md-3 text-center">
                        <a href="<?php echo htmlspecialchars((string)($partner['company_url'] ?? '#')); ?>" target="_blank" rel="noopener noreferrer" class="d-inline-flex align-items-center justify-content-center p-3 w-100" style="min-height: 110px;">
                          <img src="<?php echo htmlspecialchars((string)$partner['logo_url']); ?>" alt="<?php echo htmlspecialchars((string)$partner['company_name']); ?>" style="max-height:64px;max-width:100%;object-fit:contain;filter:grayscale(100%);opacity:.92;">
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
        $schemaType = $activeVirtualPage['type'] === 'article' ? 'Article' : 'WebPage';
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
    if ($activeStandalonePage) { $breadcrumbs[] = ['@type'=>'ListItem','position'=>2,'name'=>$activeStandalonePage['title'],'item'=>$canonicalUrl]; }
    if ($isEventView && $selectedEvent) { $breadcrumbs[] = ['@type'=>'ListItem','position'=>2,'name'=>'Eventos','item'=>absolute_url('/#agenda')]; $breadcrumbs[] = ['@type'=>'ListItem','position'=>3,'name'=>$selectedEvent['title'],'item'=>$canonicalUrl]; }
    $jsonLd[] = ['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>$breadcrumbs];
    if ($activeVirtualPage) {
      $jsonLd[] = ['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>[
        ['@type'=>'Question','name'=>'Como pedir orçamento para eventos de humor?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Envia cidade, data, público, objetivo e formato pretendido através dos contactos.']],
        ['@type'=>'Question','name'=>'A Chorar de Rir trabalha fora de Portugal?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Sim. O website está preparado para Portugal, Suíça, França e Luxemburgo.']]
      ]];
      if ($activeVirtualPage['type'] === 'article') { $jsonLd[] = ['@context'=>'https://schema.org','@type'=>'Article','headline'=>$activeVirtualPage['title'],'description'=>$activeVirtualPage['description'],'author'=>['@type'=>'Organization','name'=>'Chorar de Rir'],'publisher'=>['@type'=>'Organization','name'=>'Chorar de Rir','logo'=>['@type'=>'ImageObject','url'=>absolute_url('/chorarderir-logo.svg')]],'mainEntityOfPage'=>$canonicalUrl]; }
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
    .navbar-brand img { height: 16px; max-height: 16px; width: auto; display: block; filter: brightness(0) invert(1); }
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
      min-height: min(84vh, 760px);
      display: flex;
      align-items: center;
      position: relative;
      overflow: hidden;
      padding: calc(var(--nav-height) + 2rem) 0 4rem;
      color: var(--text-primary);
      background-position: center;
      background-size: cover;
      background-repeat: no-repeat;
    }
    .hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(115deg, rgba(11,11,13,.84) 0%, rgba(26,26,29,.62) 42%, rgba(225,6,0,.45) 100%);
      transform: translateY(var(--hero-offset, 0px));
      will-change: transform;
      opacity: .98;
    }
    .hero::after {
      content: '';
      position: absolute;
      inset: 0;
      background: radial-gradient(circle at 24% 40%, rgba(255,255,255,.08), transparent 34%),
                  radial-gradient(circle at 68% 62%, rgba(225,6,0,.25), transparent 45%);
      pointer-events: none;
    }
    .hero > .container { position: relative; z-index: 2; }
    .hero-panel {
      max-width: 780px;
      background: rgba(11, 11, 13, 0.58);
      border: 1px solid rgba(255,255,255,.12);
      border-radius: 1.15rem;
      box-shadow: 0 28px 72px rgba(0, 0, 0, .55);
      backdrop-filter: blur(10px);
    }
    .hero .btn-brand { padding: .8rem 1.5rem; }
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
    @media (max-width: 767.98px) {
      .navbar-brand img { height: 14px; max-height: 14px; }
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
      <a class="navbar-brand" href="index.php#inicio">
        <img src="chorarderir-logo.svg" alt="Chorar de Rir">
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPublico" aria-controls="menuPublico" aria-expanded="false" aria-label="Abrir menu de navegação">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="menuPublico">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link <?php echo (!$isStandaloneView && !$isEventView) ? 'active' : ''; ?>" href="index.php#inicio">Início</a></li>
          <?php foreach ($sectionPages as $page): ?>
            <?php $menuSlug = trim((string)($page['slug'] ?? '')); ?>
            <?php if ($menuSlug === '' || ($menuSlug === 'agenda' && !$hasAgendaEvents)) { continue; } ?>
            <li class="nav-item"><a class="nav-link" href="index.php#<?php echo htmlspecialchars($menuSlug); ?>"><?php echo htmlspecialchars((string)($page['title'] ?? ucfirst(str_replace('-', ' ', $menuSlug)))); ?></a></li>
          <?php endforeach; ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Serviços</a>
            <ul class="dropdown-menu dropdown-menu-dark">
              <?php foreach ($servicePages as $slug => $service): ?><li><a class="dropdown-item" href="/<?php echo htmlspecialchars($slug); ?>"><?php echo htmlspecialchars($service['title']); ?></a></li><?php endforeach; ?>
            </ul>
          </li>
          <li class="nav-item"><a class="nav-link" href="/blog">Blog</a></li>
          <?php if ($hasPartners): ?>
            <li class="nav-item"><a class="nav-link" href="index.php#parceiros">Parceiros</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

  <main class="site-main">
    <?php if ($activeVirtualPage !== null): ?>
      <section class="section-block">
        <div class="container">
          <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="/">Início</a></li><li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($activeVirtualPage['title']); ?></li></ol></nav>
          <article class="surface-card p-4 p-lg-5 fade-in show">
            <h1 class="section-heading mb-3"><?php echo htmlspecialchars($activeVirtualPage['title']); ?></h1>
            <p class="lead text-secondary"><?php echo htmlspecialchars($activeVirtualPage['description']); ?></p>
            <?php if ($activeVirtualPage['type'] === 'service'): ?>
              <h2>Serviço de humor ao vivo sem duplicação</h2><p class="page-content">Planeamos stand up comedy, eventos de humor, espetáculo de humor, comédia ao vivo e humor para empresas com curadoria de artistas, briefing, produção técnica e acompanhamento até ao fim do evento.</p>
              <h2>O que está incluído</h2><div class="row g-3"><div class="col-md-4"><h3>Curadoria</h3><p class="text-secondary">Seleção de humoristas adequada ao público.</p></div><div class="col-md-4"><h3>Produção</h3><p class="text-secondary">Coordenação de palco, horários e comunicação.</p></div><div class="col-md-4"><h3>Conversão</h3><p class="text-secondary">Ligações para agenda, contactos e reservas.</p></div></div>
            <?php elseif ($activeVirtualPage['type'] === 'local'): ?>
              <h2>Eventos de stand up comedy em <?php echo htmlspecialchars($activeVirtualPage['place']); ?></h2><p class="page-content">Criamos propostas locais para espetáculos de stand up, eventos corporativos de humor, team building com humor e booking de humoristas em <?php echo htmlspecialchars($activeVirtualPage['place']); ?>, mantendo conteúdos únicos por mercado.</p>
              <h2>Formatos recomendados</h2><h3>Empresas</h3><p class="text-secondary">Humor para empresas com tom alinhado à cultura interna.</p><h3>Salas e teatros</h3><p class="text-secondary">Comédia ao vivo com comunicação otimizada para pesquisa local.</p>
            <?php elseif ($activeVirtualPage['type'] === 'blog'): ?>
              <h2>Categorias</h2><p><a href="/blog/como-contratar-humorista">Contratar humorista</a> · <a href="/blog/team-building-com-humor">Team building com humor</a></p>
              <h2>Artigos recentes</h2><ul><li><a href="/blog/como-contratar-humorista">Como contratar um humorista para o teu evento</a></li><li><a href="/blog/team-building-com-humor">Team building com humor: como funciona</a></li></ul>
            <?php else: ?>
              <h2>Guia prático</h2><p class="page-content"><?php echo htmlspecialchars($activeVirtualPage['description']); ?> A Chorar de Rir ajuda a definir objetivo, público, formato, duração, necessidades técnicas e ligações internas para agenda e contactos.</p>
            <?php endif; ?>
            <h2>Perguntas frequentes</h2><div class="accordion" id="faqSeo"><div class="accordion-item bg-dark text-white"><h3 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">Como pedir orçamento?</button></h3><div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqSeo"><div class="accordion-body">Usa a página de contactos e indica cidade, data, público e objetivo.</div></div></div></div>
            <p class="mt-4"><a class="btn btn-brand" href="/#contactos">Pedir proposta</a> <a class="btn btn-outline-brand" href="/#agenda">Ver eventos</a></p>
          </article>
        </div>
      </section>
    <?php elseif (!$isStandaloneView && !$isEventView): ?>
      <section id="inicio" class="hero" style="background-image: url('<?php echo htmlspecialchars((string)$heroBackgroundUrl); ?>');">
        <div class="container">
          <div class="hero-panel p-4 p-lg-5 col-12 fade-in show">
            <span class="hero-comedy-icon"><i class="bi bi-mic-fill"></i></span>
            <p class="hero-tagline text-uppercase small mb-2 fw-semibold"><?php echo htmlspecialchars((string)($homeCopy['tagline'] ?? '')); ?></p>
            <h1 class="display-4 fw-bold mb-3"><?php echo htmlspecialchars((string)($homeCopy['title'] ?? '')); ?></h1>
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
                  <form method="post" action="contact.php" class="row g-3 justify-content-center">
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
              <form method="post" action="reserve.php" class="row g-2" id="reserveModalForm">
                <input type="hidden" name="event_id" id="reserveEventId">
                <div class="col-12"><input name="customer_name" required class="form-control" placeholder="Nome"></div>
                <div class="col-md-6"><input type="email" name="customer_email" required class="form-control" placeholder="Email"></div>
                <div class="col-md-6"><input name="customer_phone" class="form-control" placeholder="Telefone"></div>
                <div class="col-md-6"><input type="number" min="1" value="1" name="tickets" id="reserveTickets" class="form-control" placeholder="Nº bilhetes"></div>
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
            <a class="btn btn-sm btn-outline-light" href="index.php#agenda">← Voltar à agenda</a>
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
              <button
                type="button"
                class="btn btn-brand"
                data-bs-toggle="modal"
                data-bs-target="#reserveModal"
                data-event-id="<?php echo (int)$selectedEvent['id']; ?>"
                data-event-title="<?php echo htmlspecialchars((string)$selectedEvent['title'], ENT_QUOTES); ?>"
                data-event-date="<?php echo htmlspecialchars((string)$selectedEvent['date'], ENT_QUOTES); ?>"
                data-event-time="<?php echo htmlspecialchars(substr((string)$selectedEvent['time'], 0, 5), ENT_QUOTES); ?>"
                data-event-location="<?php echo htmlspecialchars((string)$selectedEvent['location'], ENT_QUOTES); ?>"
                data-max-tickets="<?php echo $selectedAvailable !== null ? (int)$selectedAvailable : 0; ?>"
              >
                Reservar lugar
              </button>
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
              <form method="post" action="reserve.php" class="row g-2" id="reserveModalForm">
                <input type="hidden" name="event_id" id="reserveEventId">
                <div class="col-12"><input name="customer_name" required class="form-control" placeholder="Nome"></div>
                <div class="col-md-6"><input type="email" name="customer_email" required class="form-control" placeholder="Email"></div>
                <div class="col-md-6"><input name="customer_phone" class="form-control" placeholder="Telefone"></div>
                <div class="col-md-6"><input type="number" min="1" value="1" name="tickets" id="reserveTickets" class="form-control" placeholder="Nº bilhetes"></div>
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
        <a href="#servicos">Serviços</a>
        <a href="#contactos">Contactos</a>
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
      if (!isStandaloneView) {
        document.documentElement.style.setProperty('--hero-offset', `${window.scrollY * 0.18}px`);
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

        $template = str_replace('__DB_PATH__', addslashes($dbPath), $template);
        $template = str_replace('__HOME_COPY_JSON__', addslashes((string)$homeCopyJson), $template);
        $template = str_replace('__NEWSLETTER_CONSENT__', addslashes($newsletterConsentText), $template);
        return str_replace('__RECAPTCHA_SITE_KEY__', addslashes(trim($recaptchaSiteKey)), $template);
    }

    private function buildIndexHtmlRedirect(): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chorar de Rir</title>
  <link rel="icon" type="image/svg+xml" href="chorarderir-logo.svg">
  <meta http-equiv="refresh" content="0; url=index.php">
  <script>
    (function () {
      var query = window.location.search || '';
      var hash = window.location.hash || '';
      window.location.replace('index.php' + query + hash);
    })();
  </script>
</head>
<body>
  <p>A redirecionar para <a href="index.php">index.php</a>…</p>
</body>
</html>
HTML;
    }

    private function buildHtaccess(): string
    {
        return <<<'HTACCESS'
RewriteEngine On
RewriteRule ^sitemap\.xml$ sitemap.php [L]
RewriteRule ^eventos/([^/]+)/?$ index.php?evento=$1 [L,QSA]
RewriteRule ^(stand-up-comedy|eventos-de-humor|eventos-corporativos|team-building-com-humor|booking-de-humoristas|producao-de-eventos|stand-up-comedy-portugal|stand-up-comedy-aveiro|stand-up-comedy-porto|stand-up-comedy-lisboa|stand-up-comedy-braga|stand-up-comedy-coimbra|stand-up-comedy-faro|stand-up-comedy-suica|stand-up-comedy-franca|stand-up-comedy-luxemburgo|blog|blog/como-contratar-humorista|blog/team-building-com-humor)/?$ index.php?page=$1 [L,QSA]
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

    private function buildSitemapGenerator(string $dbPath): string
    {
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
$static = ['stand-up-comedy','eventos-de-humor','eventos-corporativos','team-building-com-humor','booking-de-humoristas','producao-de-eventos','stand-up-comedy-portugal','stand-up-comedy-aveiro','stand-up-comedy-porto','stand-up-comedy-lisboa','stand-up-comedy-braga','stand-up-comedy-coimbra','stand-up-comedy-faro','stand-up-comedy-suica','stand-up-comedy-franca','stand-up-comedy-luxemburgo','blog','blog/como-contratar-humorista','blog/team-building-com-humor'];
foreach ($static as $slug) { $urls[] = ['loc' => $baseUrl . '/' . $slug, 'priority' => str_starts_with($slug, 'blog/') ? '0.7' : '0.8']; }
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
        return str_replace('__DB_PATH__', addslashes($dbPath), $template);
    }

    private function buildReserveHandler(string $dbPath, string $recaptchaSecretKey): string
    {
        $template = <<<'PHP'
<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$captchaSecret = trim((string)'__RECAPTCHA_SECRET_KEY__');
if ($captchaSecret !== '') {
    $captchaToken = trim((string)($_POST['g-recaptcha-response'] ?? ''));
    if ($captchaToken === '') {
        header('Location: index.php?msg=captcha#eventos');
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
        header('Location: index.php?msg=captcha#eventos');
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
        header('Location: index.php?msg=closed#eventos');
        exit;
    }

    $capacity = (int)($event['reservation_capacity'] ?? 0);
    $activeTickets = (int)($event['active_tickets'] ?? 0);
    if ($capacity > 0 && ($activeTickets + $tickets) > $capacity) {
        header('Location: index.php?msg=soldout#eventos');
        exit;
    }

    $stmt = $db->prepare('INSERT INTO event_reservations (event_id, customer_name, customer_email, customer_phone, tickets, notes, status) VALUES (:event_id, :customer_name, :customer_email, :customer_phone, :tickets, :notes, :status)');

    $stmt->execute([
        'event_id' => $eventId,
        'customer_name' => trim((string)($_POST['customer_name'] ?? '')),
        'customer_email' => trim((string)($_POST['customer_email'] ?? '')),
        'customer_phone' => trim((string)($_POST['customer_phone'] ?? '')),
        'tickets' => $tickets,
        'notes' => trim((string)($_POST['notes'] ?? '')) ?: null,
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
        $qrPayload = str_starts_with($validationBaseUrl, 'http')
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
            $ticketHtml .= '<div style="border:1px solid #dbe4f0;border-radius:12px;padding:16px;margin:12px 0;background:#f8fafc">'
                . '<p style="margin:0 0 8px;font-weight:700">Bilhete #' . (int)$ticket['ticket_no'] . '</p>'
                . '<p style="margin:0 0 8px">Evento: <strong>' . $eventTitle . '</strong><br>Data: ' . $eventDate . ' às ' . $eventTime . '</p>'
                . '<p style="margin:0 0 8px;font-size:12px;color:#475569">Token: ' . htmlspecialchars((string)$ticket['token']) . '</p>'
                . '<img src="' . htmlspecialchars($qrUrl) . '" alt="QR Bilhete #' . (int)$ticket['ticket_no'] . '" width="180" height="180">'
                . '</div>';
        }
        $htmlBody = '<div style="font-family:Arial,sans-serif;max-width:680px;margin:0 auto;padding:24px;color:#0f172a">'
            . '<div style="margin-bottom:16px"><img src="' . htmlspecialchars($logoUrl) . '" alt="Chorar de Rir" style="max-height:32px"></div>'
            . '<p>Olá ' . $customerNameSafe . ',</p>'
            . '<p>' . $intro . '</p>'
            . $ticketHtml
            . '<p style="margin-top:16px;color:#64748b;font-size:12px">Cada QR code só pode ser validado uma vez.</p>'
            . '</div>';

        @mail($customerEmail, $subject, $htmlBody, implode("\r\n", $headers));
    }

    header('Location: index.php?msg=ok#eventos');
    exit;
} catch (Throwable $e) {
    header('Location: index.php?msg=error#eventos');
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
    header('Location: index.php');
    exit;
}

$captchaSecret = trim((string)'__RECAPTCHA_SECRET_KEY__');
if ($captchaSecret !== '') {
    $captchaToken = trim((string)($_POST['g-recaptcha-response'] ?? ''));
    if ($captchaToken === '') {
        header('Location: index.php?msg=captcha#newsletter');
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
        header('Location: index.php?msg=captcha#newsletter');
        exit;
    }
}

$email = trim((string)($_POST['email'] ?? ''));
$name = trim((string)($_POST['name'] ?? ''));
$consent = (int)($_POST['gdpr_consent'] ?? 0);

if ($email === '' || $consent !== 1) {
    header('Location: index.php?msg=consent#newsletter');
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

    header('Location: index.php?msg=subscribed#newsletter');
    exit;
} catch (Throwable $e) {
    $code = (string)$e->getCode();
    if ($code === '23000') {
        header('Location: index.php?msg=duplicate#newsletter');
        exit;
    }

    header('Location: index.php?msg=error#newsletter');
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
    header('Location: index.php');
    exit;
}

$pageSlug = trim((string)($_POST['page_slug'] ?? 'contactos'));
$anchor = $pageSlug !== '' ? $pageSlug : 'contactos';
$captchaSecret = trim((string)'__RECAPTCHA_SECRET_KEY__');
if ($captchaSecret !== '') {
    $captchaToken = trim((string)($_POST['g-recaptcha-response'] ?? ''));
    if ($captchaToken === '') {
        header('Location: index.php?msg=contact_captcha#' . rawurlencode($anchor));
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
        header('Location: index.php?msg=contact_captcha#' . rawurlencode($anchor));
        exit;
    }
}

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$subject = trim((string)($_POST['subject'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

if ($email === '' || $message === '') {
    header('Location: index.php?msg=contact_error#' . rawurlencode($anchor));
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
    if ($emailTo === '') {
        $emailTo = 'booking@chorarderir.com';
    }

    $mailSubject = $subject !== '' ? 'Novo contacto: ' . $subject : 'Novo contacto do website';
    $mailBody = "Novo contacto recebido no website.\n\n"
        . "Nome: " . ($name !== '' ? $name : '-') . "\n"
        . "Email: " . $email . "\n"
        . "Telefone: " . ($phone !== '' ? $phone : '-') . "\n"
        . "Assunto: " . ($subject !== '' ? $subject : '-') . "\n\n"
        . "Mensagem:\n" . $message . "\n";

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/plain; charset=UTF-8',
        'From: noreply@chorarderir.com',
        'Reply-To: ' . $email,
    ];

    @mail($emailTo, $mailSubject, $mailBody, implode("\r\n", $headers));

    header('Location: index.php?msg=contact_ok#' . rawurlencode($anchor));
    exit;
} catch (Throwable $e) {
    header('Location: index.php?msg=contact_error#' . rawurlencode($anchor));
    exit;
}
PHP;

        $template = str_replace('__DB_PATH__', addslashes($dbPath), $template);
        return str_replace('__RECAPTCHA_SECRET_KEY__', addslashes(trim($recaptchaSecretKey)), $template);
    }
}
