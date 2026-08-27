<div class="admissions-hero mb-4">
    <div>
        <span class="admissions-eyebrow"><i class="bi bi-shield-check"></i> Controlo de entrada</span>
        <h1 class="mb-2">Admissões do evento</h1>
        <p class="mb-0">Digitaliza o QR code ou introduz o token. O evento de hoje é selecionado automaticamente.</p>
    </div>
    <div class="admissions-live"><span></span> Operação ativa</div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?= BASE_URL ?>" class="row g-2 align-items-end mb-3">
            <input type="hidden" name="controller" value="reservation">
            <input type="hidden" name="action" value="eventos">
            <div class="col-md-9">
                <label class="form-label">Evento</label>
                <select id="eventFilter" name="event_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Todos os eventos</option>
                    <?php foreach ($eventOverview as $event): ?>
                        <option value="<?= (int)$event['id'] ?>" <?= ((int)$selectedEventId === (int)$event['id']) ? 'selected' : '' ?>><?= htmlspecialchars($event['title']) ?> · <?= htmlspecialchars($event['date']) ?> <?= htmlspecialchars(substr((string)$event['time'], 0, 5)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <a href="<?= BASE_URL ?>?controller=reservation&action=eventos" class="btn btn-outline-secondary w-100">Limpar filtro</a>
            </div>
        </form>

        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <button id="startScan" class="btn btn-primary btn-lg w-100"><i class="bi bi-camera-fill"></i> Iniciar câmara</button>
            </div>
            <div class="col-md-6">
                <button id="stopScan" class="btn btn-outline-secondary w-100" type="button">Parar</button>
            </div>
        </div>

        <div class="mt-3 border rounded p-2 bg-light" style="max-width:420px;">
            <video id="qrVideo" playsinline muted style="width:100%;border-radius:8px;"></video>
        </div>

        <form id="validateTicketForm" method="post" action="<?= BASE_URL ?>?controller=reservation&action=validateTicket" class="row g-2 mt-3">
            <input type="hidden" name="redirect" value="eventos">
            <?php if ((int)$selectedEventId > 0): ?>
                <input type="hidden" name="event_id" value="<?= (int)$selectedEventId ?>">
            <?php endif; ?>
            <div class="col-md-9">
                <input id="tokenInput" type="text" name="token" class="form-control" required placeholder="Token lido / QR payload">
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100">Validar</button>
            </div>
        </form>

        <?php if (!empty($validationResult)): ?>
            <?php if (!empty($validationResult['ok'])): ?>
                <?php $ticket = $validationResult['ticket']; ?>
                <div class="alert alert-success mt-3 mb-0">
                    Bilhete válido ✅ | Evento: <strong><?= htmlspecialchars($ticket['event_title']) ?></strong> |
                    Cliente: <?= htmlspecialchars($ticket['customer_name']) ?> |
                    Bilhete #<?= (int)$ticket['ticket_no'] ?> validado.
                </div>
            <?php else: ?>
                <?php $reason = (string)($validationResult['reason'] ?? ''); ?>
                <div class="alert alert-danger mt-3 mb-0">
                    <?php if ($reason === 'already_used'): ?>
                        QR/token já utilizado anteriormente para este bilhete.
                    <?php elseif ($reason === 'cancelled'): ?>
                        Este bilhete pertence a uma reserva cancelada.
                    <?php else: ?>
                        QR/token inválido.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <h5 class="mb-3">Bilhetes do evento<?= (int)$selectedEventId > 0 ? ' selecionado' : 's' ?></h5>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>Evento</th>
                        <th>Cliente</th>
                        <th>Bilhete</th>
                        <th>Estado</th>
                        <th>Validado em</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ticketsOverview)): ?>
                        <tr><td colspan="5" class="text-muted">Sem bilhetes para apresentar.</td></tr>
                    <?php else: ?>
                        <?php foreach ($ticketsOverview as $ticket): ?>
                            <tr>
                                <td><?= htmlspecialchars($ticket['event_title']) ?></td>
                                <td><?= htmlspecialchars($ticket['customer_name']) ?></td>
                                <td>#<?= (int)$ticket['ticket_no'] ?></td>
                                <td>
                                    <?php if ((int)$ticket['is_used'] === 1): ?>
                                        <span class="badge text-bg-success">Validado</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">Por validar</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= !empty($ticket['used_at']) ? htmlspecialchars((string)$ticket['used_at']) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
<script>
(() => {
  const video = document.getElementById('qrVideo');
  const tokenInput = document.getElementById('tokenInput');
  const startBtn = document.getElementById('startScan');
  const stopBtn = document.getElementById('stopScan');
  const validateForm = document.getElementById('validateTicketForm');
  const canvas = document.createElement('canvas');
  const ctx = canvas.getContext('2d', { willReadFrequently: true });
  let stream = null;
  let rafId = null;
  let lastScannedPayload = '';
  let submitting = false;

  async function start(event) {
    event.preventDefault();
    stop();
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      alert('Câmara não suportada neste dispositivo.');
      return;
    }

    try {
      stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: { ideal: 'environment' } },
        audio: false,
      });
      video.srcObject = stream;
      await video.play();
      scanLoop();
    } catch (err) {
      alert('Não foi possível abrir a câmara. Verifica permissões do Safari.');
    }
  }

  function stop() {
    if (rafId) cancelAnimationFrame(rafId);
    rafId = null;
    if (stream) {
      stream.getTracks().forEach((t) => t.stop());
      stream = null;
    }
    video.srcObject = null;
  }

  function scanLoop() {
    const tick = () => {
      if (!video || video.readyState < 2) {
        rafId = requestAnimationFrame(tick);
        return;
      }

      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
      const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
      const qrCode = window.jsQR ? window.jsQR(imageData.data, imageData.width, imageData.height) : null;

      if (qrCode && qrCode.data) {
        const payload = String(qrCode.data).trim();
        if (payload && payload !== lastScannedPayload && !submitting) {
          lastScannedPayload = payload;
          tokenInput.value = payload;
          submitting = true;
          validateForm.requestSubmit();
          return;
        }
      }

      rafId = requestAnimationFrame(tick);
    };

    tick();
  }

  startBtn.addEventListener('click', start);
  stopBtn.addEventListener('click', stop);
})();
</script>
