<?php

class PublicSiteController extends BaseController
{
    public function index(): void
    {
        requireAdmin();
        $defaultPath = PUBLIC_SITE_DEFAULT_PATH;
        $this->render('public_site/index', compact('defaultPath'));
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

        $dbPath = (new Database())->getSqlitePath();

        file_put_contents($targetPath . '/index.php', $this->buildPublicIndex($events));
        file_put_contents($targetPath . '/reserve.php', $this->buildReserveHandler($dbPath));

        flash('success', 'Site público publicado em: ' . $targetPath);
        $this->redirect(BASE_URL . '?controller=publicsite&action=index');
    }

    private function buildPublicIndex(array $events): string
    {
        $eventsJson = json_encode($events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $template = <<<'PHP'
<?php
$events = json_decode('__EVENTS_JSON__', true) ?: [];
$msg = $_GET['msg'] ?? '';
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chora de Rir - Próximos Eventos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-4">
    <h1 class="mb-3">🎭 Chora de Rir</h1>
    <p class="text-muted">Reserva já o teu lugar nos próximos eventos.</p>

    <?php if ($msg === 'ok'): ?>
      <div class="alert alert-success">Reserva enviada com sucesso! Vamos confirmar por email/telefone.</div>
    <?php elseif ($msg === 'error'): ?>
      <div class="alert alert-danger">Não foi possível registar a reserva. Tenta novamente.</div>
    <?php endif; ?>

    <div class="row g-4">
      <?php foreach ($events as $event): ?>
        <div class="col-lg-6">
          <div class="card h-100 shadow-sm">
            <div class="card-body">
              <h4 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h4>
              <p class="mb-1"><strong>Data:</strong> <?php echo htmlspecialchars($event['date']); ?> às <?php echo htmlspecialchars(substr($event['time'], 0, 5)); ?></p>
              <p class="mb-3"><strong>Local:</strong> <?php echo htmlspecialchars($event['location']); ?></p>

              <form method="post" action="reserve.php" class="row g-2">
                <input type="hidden" name="event_id" value="<?php echo (int)$event['id']; ?>">
                <div class="col-12"><input name="customer_name" required class="form-control" placeholder="Nome"></div>
                <div class="col-md-6"><input type="email" name="customer_email" required class="form-control" placeholder="Email"></div>
                <div class="col-md-6"><input name="customer_phone" class="form-control" placeholder="Telefone"></div>
                <div class="col-md-6"><input type="number" min="1" value="1" name="tickets" class="form-control" placeholder="Nº bilhetes"></div>
                <div class="col-md-6"><button class="btn btn-primary w-100">Reservar</button></div>
                <div class="col-12"><textarea name="notes" class="form-control" rows="2" placeholder="Notas (opcional)"></textarea></div>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>

      <?php if (count($events) === 0): ?>
        <p class="text-muted">Ainda não existem eventos abertos para reserva.</p>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
PHP;

        return str_replace('__EVENTS_JSON__', addslashes((string)$eventsJson), $template);
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

