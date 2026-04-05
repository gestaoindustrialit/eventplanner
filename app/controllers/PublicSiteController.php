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
        $newsletterConsentText = $settings->get('newsletter_consent_text', 'Autorizo o tratamento dos meus dados para receber comunicações de eventos e novidades, de acordo com o RGPD.');

        $this->render('public_site/index', compact('defaultPath', 'pages', 'homeTagline', 'homeTitle', 'homeDescription', 'newsletterConsentText'));
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
            $settings->set('newsletter_consent_text', trim((string)($_POST['newsletter_consent_text'] ?? '')));

            $dbPath = (new Database())->getSqlitePath();
            $homeCopy = [
                'tagline' => $settings->get('home_tagline', ''),
                'title' => $settings->get('home_title', ''),
                'description' => $settings->get('home_description', ''),
            ];
            $newsletterConsentText = $settings->get('newsletter_consent_text', '');

            if (file_put_contents($targetPath . '/index.php', $this->buildPublicIndex($dbPath, $homeCopy, $newsletterConsentText)) === false) {
                throw new RuntimeException('Falha ao escrever index.php no destino.');
            }
            if (file_put_contents($targetPath . '/reserve.php', $this->buildReserveHandler($dbPath)) === false) {
                throw new RuntimeException('Falha ao escrever reserve.php no destino.');
            }
            if (file_put_contents($targetPath . '/subscribe.php', $this->buildSubscribeHandler($dbPath)) === false) {
                throw new RuntimeException('Falha ao escrever subscribe.php no destino.');
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

    private function buildPublicIndex(string $dbPath, array $homeCopy, string $newsletterConsentText): string
    {
        $homeCopyJson = json_encode($homeCopy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $template = <<<'PHP'
<?php
$events = [];
$pages = [];
$homeCopy = json_decode('__HOME_COPY_JSON__', true) ?: [];
$msg = $_GET['msg'] ?? '';

try {
    $db = new PDO('sqlite:__DB_PATH__', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $eventColumns = array_column($db->query('PRAGMA table_info(events)')->fetchAll(), 'name');
    $hasReservationsOpen = in_array('reservations_open', $eventColumns, true);
    $hasReservationCapacity = in_array('reservation_capacity', $eventColumns, true);

    $eventSql = "SELECT e.*, c.name as client_name, COALESCE(SUM(CASE WHEN r.status != 'cancelled' THEN r.tickets ELSE 0 END), 0) AS active_tickets
                 FROM events e
                 LEFT JOIN clients c ON c.id = e.client_id
                 LEFT JOIN event_reservations r ON r.event_id = e.id
                 WHERE e.date >= date('now')";
    if ($hasReservationsOpen) {
        $eventSql .= " AND e.reservations_open = 1";
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

    if (!$hasReservationsOpen) {
        foreach ($events as &$legacyEvent) {
            $legacyEvent['reservations_open'] = 1;
            if (!$hasReservationCapacity) {
                $legacyEvent['reservation_capacity'] = 0;
            }
        }
        unset($legacyEvent);
    }
} catch (Throwable $e) {
    $events = [];
    $pages = [];
}

$siteTitle = 'Chorar de Rir';
$defaultHomeCopy = [
    'tagline' => 'Produção • Booking • Experiências',
    'title' => 'Humor e espetáculos com um palco inesquecível.',
    'description' => 'Eventos únicos, noites memoráveis e talento nacional num ambiente vibrante.',
];
$homeCopy = [
    'tagline' => trim((string)($homeCopy['tagline'] ?? '')) !== '' ? (string)$homeCopy['tagline'] : $defaultHomeCopy['tagline'],
    'title' => trim((string)($homeCopy['title'] ?? '')) !== '' ? (string)$homeCopy['title'] : $defaultHomeCopy['title'],
    'description' => trim((string)($homeCopy['description'] ?? '')) !== '' ? (string)$homeCopy['description'] : $defaultHomeCopy['description'],
];

function safe_content(?string $html): string {
    return strip_tags((string)$html, '<h1><h2><h3><h4><p><ul><ol><li><strong><em><a><blockquote><br><hr>');
}
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($siteTitle); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root {
      --brand-dark: #0f172a;
      --brand-base: #ffffff;
      --brand-surface: #f8fafc;
      --brand-border: #dbe4f0;
      --brand-text: #0f172a;
      --brand-muted: #5b6472;
      --nav-height: 74px;
    }
    body {
      color: var(--brand-text);
      background: var(--brand-surface);
      min-height: 100vh;
      margin: 0;
      display: flex;
      flex-direction: column;
      scroll-behavior: smooth;
    }
    .site-main { flex: 1 0 auto; }
    .navbar {
      min-height: var(--nav-height);
      backdrop-filter: blur(10px);
      background: rgba(15, 23, 42, 0.88);
      border-bottom: 1px solid rgba(255,255,255,.18);
      transition: box-shadow .35s ease;
    }
    .navbar.scrolled { box-shadow: 0 14px 40px rgba(2, 6, 23, .22); }
    .navbar-brand { display: inline-flex; align-items: center; line-height: 1; padding-top: .2rem; padding-bottom: .2rem; }
    .navbar-brand img { height: 16px; max-height: 16px; width: auto; display: block; filter: brightness(0) invert(1); }
    .nav-link { color: #e8edf7; position: relative; transition: color .25s ease; }
    .nav-link::after {
      content: '';
      position: absolute;
      left: .5rem;
      right: .5rem;
      bottom: .2rem;
      height: 2px;
      border-radius: 999px;
      background: #93c5fd;
      transform: scaleX(0);
      transition: transform .25s ease;
    }
    .nav-link.active, .nav-link:hover { color: #fff !important; }
    .nav-link.active::after, .nav-link:hover::after { transform: scaleX(1); }
    .hero {
      min-height: min(84vh, 760px);
      display: flex;
      align-items: center;
      position: relative;
      overflow: hidden;
      padding: calc(var(--nav-height) + 2rem) 0 4rem;
      color: #fff;
    }
    .hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background:
        linear-gradient(120deg, rgba(15, 23, 42, 0.84) 15%, rgba(15, 23, 42, 0.55) 55%, rgba(29, 78, 216, 0.25) 100%),
        url('https://images.unsplash.com/photo-1470229538611-16ba8c7ffbd7?auto=format&fit=crop&w=1800&q=80') center / cover fixed;
      transform: translateY(var(--hero-offset, 0px));
      will-change: transform;
    }
    .hero > .container { position: relative; z-index: 2; }
    .hero-panel {
      max-width: 780px;
      background: rgba(15, 23, 42, 0.7);
      border: 1px solid rgba(255,255,255,.22);
      border-radius: 1rem;
      box-shadow: 0 24px 64px rgba(2, 6, 23, .38);
      backdrop-filter: blur(8px);
    }
    .section-block { padding: clamp(3rem, 8vw, 5rem) 0; scroll-margin-top: calc(var(--nav-height) + 1rem); }
    .section-heading { font-weight: 700; margin-bottom: 1.5rem; color: #0f172a; }
    .surface-card {
      border-radius: 1rem;
      border: 1px solid var(--brand-border);
      background: var(--brand-base);
      box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
    }
    .event-card { padding: 1.25rem; height: 100%; }
    .event-card input, .event-card textarea { border-color: #ccd8e8; }
    .event-card input:focus, .event-card textarea:focus { border-color: #93c5fd; box-shadow: 0 0 0 .2rem rgba(59,130,246,.18); }
    .btn-brand {
      background: linear-gradient(135deg, #2563eb, #1e40af);
      color: #fff;
      border: none;
      font-weight: 600;
      transition: transform .2s ease, box-shadow .2s ease;
    }
    .btn-brand:hover { color: #fff; transform: translateY(-1px); box-shadow: 0 10px 18px rgba(37,99,235,.28); }
    .page-cover { min-height: 220px; border-radius: .95rem; background-size: cover; background-position: center; margin-bottom: 1.2rem; }
    .page-content { line-height: 1.7; color: #334155; }
    .newsletter-panel { background: linear-gradient(135deg, #eff6ff, #dbeafe); border: 1px solid #bfdbfe; }
    .fade-in { opacity: 0; transform: translateY(18px); transition: opacity .5s ease, transform .5s ease; }
    .fade-in.show { opacity: 1; transform: translateY(0); }
    footer { flex-shrink: 0; border-top: 1px solid var(--brand-border); background: #fff; color: var(--brand-muted); }
    @media (max-width: 767.98px) {
      .navbar-brand img { height: 14px; max-height: 14px; }
      .navbar { padding-top: .45rem; padding-bottom: .45rem; }
      .navbar .navbar-toggler { padding: .3rem .45rem; }
      .navbar .navbar-collapse {
        margin-top: .6rem;
        background: rgba(7, 11, 17, 0.94);
        border: 1px solid rgba(255,255,255,.14);
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
      <a class="navbar-brand" href="#inicio">
        <img src="chorarderir-logo.svg" alt="Chorar de Rir">
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPublico">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="menuPublico">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link active" href="#inicio">Início</a></li>
          <li class="nav-item"><a class="nav-link" href="#eventos">Eventos</a></li>
          <li class="nav-item"><a class="nav-link" href="#newsletter">Newsletter</a></li>
          <?php foreach ($pages as $menuPage): ?>
            <?php $slug = trim((string)($menuPage['slug'] ?? '')); ?>
            <?php if ($slug !== ''): ?>
              <li class="nav-item"><a class="nav-link" href="#<?php echo htmlspecialchars($slug); ?>"><?php echo htmlspecialchars((string)$menuPage['title']); ?></a></li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </nav>

  <main class="site-main">
    <section id="inicio" class="hero">
      <div class="container">
        <div class="hero-panel p-4 p-lg-5 col-12 fade-in show">
          <p class="text-uppercase small mb-2 fw-semibold text-info-emphasis"><?php echo htmlspecialchars((string)($homeCopy['tagline'] ?? '')); ?></p>
          <h1 class="display-5 fw-bold mb-3"><?php echo htmlspecialchars((string)($homeCopy['title'] ?? '')); ?></h1>
          <p class="lead mb-0 text-light"><?php echo htmlspecialchars((string)($homeCopy['description'] ?? '')); ?></p>
        </div>
      </div>
    </section>

    <section id="eventos" class="section-block">
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
        <?php endif; ?>

        <h2 class="section-heading">Próximos eventos</h2>
        <div class="row g-4 mb-5">
          <?php foreach ($events as $event): ?>
            <div class="col-lg-6">
              <div class="event-card surface-card fade-in">
                <h4><?php echo htmlspecialchars($event['title']); ?></h4>
                <p class="mb-1"><strong>Data:</strong> <?php echo htmlspecialchars($event['date']); ?> às <?php echo htmlspecialchars(substr($event['time'], 0, 5)); ?></p>
                <p class="mb-3"><strong>Local:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                <?php
                  $capacity = (int)($event['reservation_capacity'] ?? 0);
                  $activeTickets = (int)($event['active_tickets'] ?? 0);
                  $available = $capacity > 0 ? max(0, $capacity - $activeTickets) : null;
                ?>
                <?php if ($available !== null): ?>
                  <p class="small text-secondary mb-3"><strong>Lugares disponíveis:</strong> <?php echo $available; ?> / <?php echo $capacity; ?></p>
                <?php endif; ?>

                <?php if ((int)($event['reservations_open'] ?? 0) !== 1): ?>
                  <div class="alert alert-secondary py-2 mb-0">Reservas fechadas para este evento.</div>
                <?php elseif ($available !== null && $available <= 0): ?>
                  <div class="alert alert-warning py-2 mb-0">Esgotado. Não existem mais lugares disponíveis.</div>
                <?php else: ?>
                  <form method="post" action="reserve.php" class="row g-2">
                    <input type="hidden" name="event_id" value="<?php echo (int)$event['id']; ?>">
                    <div class="col-12"><input name="customer_name" required class="form-control" placeholder="Nome"></div>
                    <div class="col-md-6"><input type="email" name="customer_email" required class="form-control" placeholder="Email"></div>
                    <div class="col-md-6"><input name="customer_phone" class="form-control" placeholder="Telefone"></div>
                    <div class="col-md-6"><input type="number" min="1" <?php echo $available !== null ? 'max="' . $available . '"' : ''; ?> value="1" name="tickets" class="form-control" placeholder="Nº bilhetes"></div>
                    <div class="col-md-6"><button class="btn btn-brand w-100">Reservar</button></div>
                    <div class="col-12"><textarea name="notes" class="form-control" rows="2" placeholder="Notas (opcional)"></textarea></div>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section id="newsletter" class="section-block pt-0">
      <div class="container">
        <div class="surface-card newsletter-panel p-4 p-lg-5 fade-in">
          <h3 class="h5 mb-2">Newsletter</h3>
          <form method="post" action="subscribe.php" class="row g-2 align-items-center newsletter-form">
            <div class="col-lg-4"><input type="email" name="email" required class="form-control" placeholder="Email"></div>
            <div class="col-lg-4"><input type="text" name="name" class="form-control" placeholder="Nome (opcional)"></div>
            <div class="col-lg-2"><button class="btn btn-brand w-100">Subscrever</button></div>
            <div class="col-lg-2">
              <label class="form-check-label d-flex gap-2 align-items-start small text-secondary">
                <input class="form-check-input mt-1" type="checkbox" name="gdpr_consent" value="1" required>
                <span>RGPD</span>
              </label>
            </div>
            <div class="col-12">
              <p class="small text-secondary mb-0"><?php echo htmlspecialchars('__NEWSLETTER_CONSENT__'); ?></p>
            </div>
          </form>
        </div>
      </div>
    </section>

    <?php foreach ($pages as $page): ?>
      <?php $sectionId = trim((string)($page['slug'] ?? '')); ?>
      <?php if ($sectionId === '') { continue; } ?>
      <section id="<?php echo htmlspecialchars($sectionId); ?>" class="section-block<?php echo !empty($page['hero_image_url']) ? ' pt-0' : ''; ?>">
        <div class="container">
          <div class="surface-card p-4 p-lg-5 fade-in">
            <?php if (!empty($page['hero_image_url'])): ?>
              <div class="page-cover" style="background-image:url('<?php echo htmlspecialchars((string)$page['hero_image_url']); ?>');"></div>
            <?php endif; ?>
            <h2 class="section-heading mb-3"><?php echo htmlspecialchars((string)$page['title']); ?></h2>
            <?php if (!empty($page['excerpt'])): ?>
              <p class="lead text-secondary"><?php echo htmlspecialchars((string)$page['excerpt']); ?></p>
            <?php endif; ?>
            <div class="page-content"><?php echo safe_content($page['content'] ?? ''); ?></div>
          </div>
        </div>
      </section>
    <?php endforeach; ?>

    <?php if (count($pages) === 0): ?>
      <section class="section-block pt-0">
        <div class="container">
          <div class="alert alert-info">Cria páginas públicas para adicionar novos setores na home.</div>
        </div>
      </section>
    <?php endif; ?>
  </main>

  <footer class="py-4">
    <div class="container d-flex flex-column flex-md-row gap-2 justify-content-between">
      <span>© <?php echo date('Y'); ?> Chorar de Rir</span>
      <span>Produção & Booking</span>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const navbar = document.querySelector('.navbar');
    const fadeItems = document.querySelectorAll('.fade-in');
    const navLinks = document.querySelectorAll('.navbar .nav-link');
    const menuCollapse = document.getElementById('menuPublico');
    const bsCollapse = menuCollapse ? bootstrap.Collapse.getOrCreateInstance(menuCollapse, {toggle: false}) : null;

    window.addEventListener('scroll', () => {
      if (navbar) {
        navbar.classList.toggle('scrolled', window.scrollY > 10);
      }
      document.documentElement.style.setProperty('--hero-offset', `${window.scrollY * 0.18}px`);
    });

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('show');
        }
      });
    }, { threshold: 0.16 });
    fadeItems.forEach((item) => observer.observe(item));

    const sectionObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const id = entry.target.getAttribute('id');
        navLinks.forEach((link) => {
          link.classList.toggle('active', link.getAttribute('href') === `#${id}`);
        });
      });
    }, { rootMargin: '-45% 0px -48% 0px', threshold: 0.01 });

    document.querySelectorAll('section[id]').forEach((section) => sectionObserver.observe(section));

    navLinks.forEach((link) => {
      link.addEventListener('click', () => {
        if (window.innerWidth < 992 && bsCollapse && menuCollapse.classList.contains('show')) {
          bsCollapse.hide();
        }
      });
    });

    if (window.location.search.includes('msg=')) {
      history.replaceState(null, '', window.location.pathname + window.location.hash);
    }
  </script>
</body>
</html>
PHP;

        $template = str_replace('__DB_PATH__', addslashes($dbPath), $template);
        $template = str_replace('__HOME_COPY_JSON__', addslashes((string)$homeCopyJson), $template);
        return str_replace('__NEWSLETTER_CONSENT__', addslashes($newsletterConsentText), $template);
    }

    private function buildReserveHandler(string $dbPath): string
    {
        $template = <<<'PHP'
<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
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

    $db->exec(
        'CREATE TABLE IF NOT EXISTS site_settings (
            setting_key TEXT PRIMARY KEY,
            setting_value TEXT NOT NULL,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $settingStmt = $db->prepare('SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN (:template_a, :template_b, :template_selected)');
    $settingStmt->execute([
        'template_a' => 'reservation_email_template_a',
        'template_b' => 'reservation_email_template_b',
        'template_selected' => 'reservation_email_template_selected',
    ]);
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

    if ($customerEmail !== '') {
        $subject = 'Confirmação de reserva - ' . (string)($event['title'] ?? 'Evento');
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/plain; charset=UTF-8',
            'From: noreply@chorarderir.com',
            'Reply-To: noreply@chorarderir.com',
        ];
        @mail($customerEmail, $subject, $messageBody, implode("\r\n", $headers));
    }

    header('Location: index.php?msg=ok#eventos');
    exit;
} catch (Throwable $e) {
    header('Location: index.php?msg=error#eventos');
    exit;
}
PHP;

        return str_replace('__DB_PATH__', addslashes($dbPath), $template);
    }

    private function buildSubscribeHandler(string $dbPath): string
    {
        $template = <<<'PHP'
<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
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
        return str_replace('__NEWSLETTER_CONSENT__', addslashes(trim((string)($_POST['newsletter_consent_text'] ?? ''))), $template);
    }
}
