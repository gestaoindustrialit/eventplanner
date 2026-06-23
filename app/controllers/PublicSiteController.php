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
        $siteMetaDescription = $settings->get('site_meta_description', 'Chorar de Rir produz espetáculos de comédia, booking de comediantes e experiências ao vivo para empresas, teatros e eventos privados em Portugal.');
        $siteCanonicalUrl = $settings->get('site_canonical_url', 'https://chorarderir.com/');
        $homeMenuOrder = (int)$settings->get('home_menu_order', '0');
        $agendaMenuOrder = (int)$settings->get('agenda_menu_order', '40');
        $partnersMenuOrder = (int)$settings->get('partners_menu_order', '90');

        $this->render('public_site/index', compact('defaultPath', 'pages', 'homeTagline', 'homeTitle', 'homeDescription', 'homeBackgroundUrl', 'newsletterConsentText', 'recaptchaSiteKey', 'recaptchaSecretKey', 'siteMetaDescription', 'siteCanonicalUrl', 'homeMenuOrder', 'agendaMenuOrder', 'partnersMenuOrder'));
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
            $settings->set('site_meta_description', trim((string)($_POST['site_meta_description'] ?? '')));
            $settings->set('site_canonical_url', trim((string)($_POST['site_canonical_url'] ?? '')));
            $settings->set('home_menu_order', (string)(int)($_POST['home_menu_order'] ?? 0));
            $settings->set('agenda_menu_order', (string)(int)($_POST['agenda_menu_order'] ?? 40));
            $settings->set('partners_menu_order', (string)(int)($_POST['partners_menu_order'] ?? 90));

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
            $seoSettings = [
                'meta_description' => $settings->get('site_meta_description', ''),
                'canonical_url' => $settings->get('site_canonical_url', ''),
                'home_menu_order' => (int)$settings->get('home_menu_order', '0'),
                'agenda_menu_order' => (int)$settings->get('agenda_menu_order', '40'),
                'partners_menu_order' => (int)$settings->get('partners_menu_order', '90'),
            ];

            if (file_put_contents($targetPath . '/index.php', $this->buildPublicIndex($dbPath, $homeCopy, $newsletterConsentText, $recaptchaSiteKey, $seoSettings)) === false) {
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
            if (file_put_contents($targetPath . '/robots.txt', $this->buildRobotsTxt($seoSettings['canonical_url'])) === false) {
                throw new RuntimeException('Falha ao escrever robots.txt no destino.');
            }
            if (file_put_contents($targetPath . '/sitemap.xml', $this->buildSitemapXml($seoSettings['canonical_url'], $dbPath)) === false) {
                throw new RuntimeException('Falha ao escrever sitemap.xml no destino.');
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

    private function buildPublicIndex(string $dbPath, array $homeCopy, string $newsletterConsentText, string $recaptchaSiteKey, array $seoSettings): string
    {
        $homeCopyJson = json_encode($homeCopy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $seoSettingsJson = json_encode($seoSettings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $template = <<<'PHP'
<?php
$events = [];
$pages = [];
$partners = [];
$homeCopy = json_decode('__HOME_COPY_JSON__', true) ?: [];
$seoSettings = json_decode('__SEO_SETTINGS_JSON__', true) ?: [];
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

$siteTitle = 'Chorar de Rir | Comédia, espetáculos e eventos ao vivo';
$siteMetaDescription = trim((string)($seoSettings['meta_description'] ?? ''));
if ($siteMetaDescription === '') {
    $siteMetaDescription = 'Chorar de Rir produz espetáculos de comédia, booking de comediantes e experiências ao vivo para empresas, teatros e eventos privados em Portugal.';
}
$siteCanonicalUrl = rtrim(trim((string)($seoSettings['canonical_url'] ?? '')), '/');
$homeMenuOrder = (int)($seoSettings['home_menu_order'] ?? 0);
$agendaMenuOrder = (int)($seoSettings['agenda_menu_order'] ?? 40);
$partnersMenuOrder = (int)($seoSettings['partners_menu_order'] ?? 90);
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
    $baseSlug = strtolower(trim((string)($event['title'] ?? '')));
    $baseSlug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $baseSlug ?? '');
    $baseSlug = trim((string)$baseSlug, '-');
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
$menuItems = [
    ['label' => 'Início', 'href' => 'index.php#inicio', 'order' => $homeMenuOrder, 'active' => (!$isStandaloneView && !$isEventView)],
];
foreach ($sectionPages as $menuPage) {
    $menuSlug = trim((string)($menuPage['slug'] ?? ''));
    if ($menuSlug === '' || ($menuSlug === 'agenda' && !$hasAgendaEvents)) {
        continue;
    }
    $menuItems[] = [
        'label' => (string)($menuPage['title'] ?? ucfirst(str_replace('-', ' ', $menuSlug))),
        'href' => 'index.php#' . $menuSlug,
        'order' => ($menuSlug === 'agenda') ? $agendaMenuOrder : (int)($menuPage['sort_order'] ?? 50),
        'active' => false,
    ];
}
if ($hasPartners) {
    $menuItems[] = ['label' => 'Parceiros', 'href' => 'index.php#parceiros', 'order' => $partnersMenuOrder, 'active' => false];
}
usort($menuItems, static function (array $a, array $b): int {
    return ((int)$a['order'] <=> (int)$b['order']) ?: strcmp((string)$a['label'], (string)$b['label']);
});
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
                        <a href="<?php echo htmlspecialchars((string)($partner['company_url'] ?? '#')); ?>" target="_blank" rel="noopener noreferrer" class="d-inline-flex align-items-center justify-content-center p-3 w-100" style="min-height: 140px;">
                          <img src="<?php echo htmlspecialchars((string)$partner['logo_url']); ?>" alt="<?php echo htmlspecialchars((string)$partner['company_name']); ?>" style="max-height:96px;max-width:100%;object-fit:contain;opacity:1;">
                        </a>
                        <div class="partner-name fw-semibold mt-2"><?php echo htmlspecialchars((string)$partner['company_name']); ?></div>
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
  <title><?php echo htmlspecialchars($siteTitle); ?></title>
  <meta name="description" content="<?php echo htmlspecialchars($siteMetaDescription); ?>">
  <?php if ($siteCanonicalUrl !== ''): ?><link rel="canonical" href="<?php echo htmlspecialchars($siteCanonicalUrl . '/'); ?>"><?php endif; ?>
  <link rel="icon" type="image/svg+xml" href="chorarderir-logo.svg">
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
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPublico">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="menuPublico">
        <ul class="navbar-nav ms-auto">
          <?php foreach ($menuItems as $menuItem): ?>
            <li class="nav-item"><a class="nav-link <?php echo !empty($menuItem['active']) ? 'active' : ''; ?>" href="<?php echo htmlspecialchars((string)$menuItem['href']); ?>"><?php echo htmlspecialchars((string)$menuItem['label']); ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </nav>

  <main class="site-main">
    <?php if (!$isStandaloneView && !$isEventView): ?>
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
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
            <h2><?php echo htmlspecialchars((string)$selectedEvent['title']); ?></h2>
            <p class="mb-1"><span class="event-meta-label">Data:</span> <?php echo htmlspecialchars((string)$selectedEvent['date']); ?> às <?php echo htmlspecialchars(substr((string)$selectedEvent['time'], 0, 5)); ?></p>
            <p class="mb-3"><span class="event-meta-label">Local:</span> <?php echo htmlspecialchars((string)$selectedEvent['location']); ?></p>
            <?php if (!empty($selectedEvent['notes'])): ?>
              <p class="text-secondary mb-3"><?php echo nl2br(htmlspecialchars((string)$selectedEvent['notes'])); ?></p>
            <?php endif; ?>
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
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
        $template = str_replace('__SEO_SETTINGS_JSON__', addslashes((string)$seoSettingsJson), $template);
        $template = str_replace('__NEWSLETTER_CONSENT__', addslashes($newsletterConsentText), $template);
        return str_replace('__RECAPTCHA_SITE_KEY__', addslashes(trim($recaptchaSiteKey)), $template);
    }

    private function buildRobotsTxt(string $canonicalUrl): string
    {
        $baseUrl = rtrim(trim($canonicalUrl), '/');
        $lines = [
            'User-agent: *',
            'Allow: /',
        ];
        if ($baseUrl !== '') {
            $lines[] = 'Sitemap: ' . $baseUrl . '/sitemap.xml';
        }
        return implode("\n", $lines) . "\n";
    }

    private function buildSitemapXml(string $canonicalUrl, string $dbPath): string
    {
        $baseUrl = rtrim(trim($canonicalUrl), '/');
        if ($baseUrl === '') {
            $baseUrl = 'https://chorarderir.com';
        }

        $urls = [
            ['loc' => $baseUrl . '/', 'priority' => '1.0'],
        ];

        try {
            $db = new PDO('sqlite:' . $dbPath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $pageColumns = array_column($db->query('PRAGMA table_info(public_pages)')->fetchAll(), 'name');
            if (count($pageColumns) > 0) {
                $pageSql = 'SELECT slug, display_mode FROM public_pages';
                if (in_array('is_published', $pageColumns, true)) {
                    $pageSql .= ' WHERE is_published = 1';
                }
                foreach ($db->query($pageSql)->fetchAll() ?: [] as $page) {
                    $slug = trim((string)($page['slug'] ?? ''));
                    if ($slug === '') {
                        continue;
                    }
                    if (($page['display_mode'] ?? 'section') !== 'page') {
                        continue;
                    }
                    $urls[] = [
                        'loc' => $baseUrl . '/?page=' . rawurlencode($slug),
                        'priority' => '0.8',
                    ];
                }
            }
        } catch (Throwable $e) {
            // Mantém pelo menos a página principal no sitemap se a base de dados não estiver acessível.
        }

        $updatedAt = date('c');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            $xml .= '  <url><loc>' . htmlspecialchars($url['loc'], ENT_XML1) . '</loc><lastmod>' . $updatedAt . '</lastmod><priority>' . htmlspecialchars($url['priority'], ENT_XML1) . '</priority></url>' . "\n";
        }
        return $xml . '</urlset>' . "\n";
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
