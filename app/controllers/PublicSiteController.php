<?php

class PublicSiteController extends BaseController
{
    public function index(): void
    {
        requireAdmin();
        $defaultPath = PUBLIC_SITE_DEFAULT_PATH;
        $pages = (new PublicPage($this->db))->all();
        $this->render('public_site/index', compact('defaultPath', 'pages'));
    }

    public function publish(): void
    {
        requireAdmin();

        $targetPath = trim((string)($_POST['target_path'] ?? PUBLIC_SITE_DEFAULT_PATH));
        if ($targetPath === '') {
            flash('error', 'Indica uma pasta de destino para o site público.');
            $this->redirect(BASE_URL . '?controller=publicsite&action=index');
        }

        if (!is_dir($targetPath) && !mkdir($targetPath, 0775, true) && !is_dir($targetPath)) {
            flash('error', 'Não foi possível criar a pasta de destino: ' . $targetPath);
            $this->redirect(BASE_URL . '?controller=publicsite&action=index');
        }

        $eventModel = new Event($this->db);
        $events = $eventModel->openEvents();
        $pages = (new PublicPage($this->db))->allPublished();

        $dbPath = (new Database())->getSqlitePath();

        file_put_contents($targetPath . '/index.php', $this->buildPublicIndex($events, $pages));
        file_put_contents($targetPath . '/reserve.php', $this->buildReserveHandler($dbPath));

        $logoSource = dirname(__DIR__, 2) . '/assets/branding/chorarderir-logo.svg';
        if (is_file($logoSource)) {
            copy($logoSource, $targetPath . '/chorarderir-logo.svg');
        }

        flash('success', 'Site público publicado em: ' . $targetPath);
        $this->redirect(BASE_URL . '?controller=publicsite&action=index');
    }

    private function buildPublicIndex(array $events, array $pages): string
    {
        $eventsJson = json_encode($events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $pagesJson = json_encode($pages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $template = <<<'PHP'
<?php
$events = json_decode('__EVENTS_JSON__', true) ?: [];
$pages = json_decode('__PAGES_JSON__', true) ?: [];
$msg = $_GET['msg'] ?? '';
$pageSlug = trim((string)($_GET['page'] ?? 'home'));
$activePage = null;

foreach ($pages as $item) {
    if (($item['slug'] ?? '') === $pageSlug) {
        $activePage = $item;
        break;
    }
}

if ($activePage === null && count($pages) > 0 && $pageSlug !== 'home') {
    $activePage = $pages[0];
}

$siteTitle = 'Chorar de Rir';

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
      --brand-bg: #080b10;
      --brand-card: rgba(10, 14, 22, 0.72);
      --brand-border: rgba(255, 255, 255, 0.16);
      --brand-text: #f7f8fb;
      --brand-muted: #cdd4e1;
      --brand-accent: #f6c451;
    }
    body {
      color: var(--brand-text);
      background:
        linear-gradient(130deg, rgba(8,11,16,.78), rgba(8,11,16,.58) 45%, rgba(8,11,16,.86)),
        url('https://images.unsplash.com/photo-1470229538611-16ba8c7ffbd7?auto=format&fit=crop&w=1800&q=80') center/cover fixed;
      min-height: 100vh;
    }
    .navbar {
      backdrop-filter: blur(8px);
      background: rgba(7, 11, 17, 0.7);
      border-bottom: 1px solid rgba(255,255,255,.1);
    }
    .navbar-brand img {
      height: 38px;
      width: auto;
      display: block;
      filter: brightness(0) invert(1);
    }
    .nav-link { color: #e8edf7; }
    .nav-link.active, .nav-link:hover { color: var(--brand-accent) !important; }
    .hero {
      min-height: 62vh;
      display: flex;
      align-items: center;
      padding: 72px 0 48px;
    }
    .glass-card {
      background: var(--brand-card);
      border: 1px solid var(--brand-border);
      border-radius: 20px;
      box-shadow: 0 20px 48px rgba(0,0,0,.36);
      backdrop-filter: blur(8px);
    }
    .section-title { font-weight: 700; letter-spacing: .2px; }
    .event-card input, .event-card textarea {
      background: rgba(8, 11, 16, .6);
      border-color: rgba(255,255,255,.2);
      color: var(--brand-text);
    }
    .event-card input::placeholder, .event-card textarea::placeholder { color: #aab4c4; }
    .btn-brand {
      background: var(--brand-accent);
      color: #1d1405;
      border: none;
      font-weight: 700;
    }
    .btn-brand:hover { background: #ffd97a; color: #1d1405; }
    .content-card { background: rgba(11, 16, 25, 0.78); border: 1px solid rgba(255,255,255,.15); border-radius: 18px; }
    .content-card .lead, .text-light-emphasis, .muted { color: var(--brand-muted) !important; }
    .page-cover { min-height: 320px; border-radius: 14px; background-size: cover; background-position: center; }
    footer { border-top: 1px solid rgba(255,255,255,.15); color: var(--brand-muted); background: rgba(7,11,17,.68); }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
      <a class="navbar-brand" href="index.php">
        <img src="chorarderir-logo.svg" alt="Chorar de Rir">
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPublico">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="menuPublico">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link <?php echo $pageSlug === 'home' ? 'active' : ''; ?>" href="index.php">Início</a></li>
          <?php foreach ($pages as $menuPage): ?>
            <li class="nav-item"><a class="nav-link <?php echo $pageSlug === ($menuPage['slug'] ?? '') ? 'active' : ''; ?>" href="index.php?page=<?php echo urlencode((string)$menuPage['slug']); ?>"><?php echo htmlspecialchars((string)$menuPage['title']); ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </nav>

  <?php if ($pageSlug === 'home'): ?>
    <section class="hero">
      <div class="container">
        <div class="glass-card p-4 p-lg-5 col-12 col-xl-9">
          <p class="text-uppercase small mb-2 fw-semibold text-warning">Produção • Booking • Experiências</p>
          <h1 class="display-5 fw-bold mb-3">Humor e espetáculos com um palco inesquecível.</h1>
          <p class="lead muted mb-0">Layout inspirado no visual "Big Picture": imagem de fundo marcante, tipografia forte e conteúdo em cartões translúcidos para foco total no evento.</p>
        </div>
      </div>
    </section>

    <section class="py-5">
      <div class="container">
        <h2 class="section-title mb-4">Próximos eventos</h2>

        <?php if ($msg === 'ok'): ?>
          <div class="alert alert-success">Reserva enviada com sucesso! Vamos confirmar por email/telefone.</div>
        <?php elseif ($msg === 'error'): ?>
          <div class="alert alert-danger">Não foi possível registar a reserva. Tenta novamente.</div>
        <?php endif; ?>

        <div class="row g-4">
          <?php foreach ($events as $event): ?>
            <div class="col-lg-6">
              <div class="event-card glass-card p-4 h-100">
                <h4><?php echo htmlspecialchars($event['title']); ?></h4>
                <p class="mb-1"><strong>Data:</strong> <?php echo htmlspecialchars($event['date']); ?> às <?php echo htmlspecialchars(substr($event['time'], 0, 5)); ?></p>
                <p class="mb-3"><strong>Local:</strong> <?php echo htmlspecialchars($event['location']); ?></p>

                <form method="post" action="reserve.php" class="row g-2">
                  <input type="hidden" name="event_id" value="<?php echo (int)$event['id']; ?>">
                  <div class="col-12"><input name="customer_name" required class="form-control" placeholder="Nome"></div>
                  <div class="col-md-6"><input type="email" name="customer_email" required class="form-control" placeholder="Email"></div>
                  <div class="col-md-6"><input name="customer_phone" class="form-control" placeholder="Telefone"></div>
                  <div class="col-md-6"><input type="number" min="1" value="1" name="tickets" class="form-control" placeholder="Nº bilhetes"></div>
                  <div class="col-md-6"><button class="btn btn-brand w-100">Reservar</button></div>
                  <div class="col-12"><textarea name="notes" class="form-control" rows="2" placeholder="Notas (opcional)"></textarea></div>
                </form>
              </div>
            </div>
          <?php endforeach; ?>

          <?php if (count($events) === 0): ?>
            <p class="text-light-emphasis">Ainda não existem eventos abertos para reserva.</p>
          <?php endif; ?>
        </div>
      </div>
    </section>
  <?php else: ?>
    <section class="py-5">
      <div class="container">
        <?php if ($activePage): ?>
          <?php if (!empty($activePage['hero_image_url'])): ?>
            <div class="page-cover mb-4" style="background-image:url('<?php echo htmlspecialchars((string)$activePage['hero_image_url']); ?>');"></div>
          <?php endif; ?>

          <div class="content-card p-4 p-lg-5">
            <h1 class="mb-3"><?php echo htmlspecialchars((string)$activePage['title']); ?></h1>
            <?php if (!empty($activePage['excerpt'])): ?>
              <p class="lead text-light-emphasis"><?php echo htmlspecialchars((string)$activePage['excerpt']); ?></p>
            <?php endif; ?>
            <div><?php echo safe_content($activePage['content'] ?? ''); ?></div>
          </div>
        <?php else: ?>
          <div class="alert alert-warning">Página não encontrada.</div>
        <?php endif; ?>
      </div>
    </section>
  <?php endif; ?>

  <footer class="py-4 mt-5">
    <div class="container d-flex justify-content-between">
      <span>© <?php echo date('Y'); ?> Chorar de Rir</span>
      <span>Produção & Booking</span>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
PHP;

        $template = str_replace('__EVENTS_JSON__', addslashes((string)$eventsJson), $template);
        return str_replace('__PAGES_JSON__', addslashes((string)$pagesJson), $template);
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

    $stmt = $db->prepare('INSERT INTO event_reservations (event_id, customer_name, customer_email, customer_phone, tickets, notes, status) VALUES (:event_id, :customer_name, :customer_email, :customer_phone, :tickets, :notes, :status)');

    $stmt->execute([
        'event_id' => (int)($_POST['event_id'] ?? 0),
        'customer_name' => trim((string)($_POST['customer_name'] ?? '')),
        'customer_email' => trim((string)($_POST['customer_email'] ?? '')),
        'customer_phone' => trim((string)($_POST['customer_phone'] ?? '')),
        'tickets' => max(1, (int)($_POST['tickets'] ?? 1)),
        'notes' => trim((string)($_POST['notes'] ?? '')) ?: null,
        'status' => 'new',
    ]);

    header('Location: index.php?msg=ok');
    exit;
} catch (Throwable $e) {
    header('Location: index.php?msg=error');
    exit;
}
PHP;

        return str_replace('__DB_PATH__', addslashes($dbPath), $template);
    }
}
