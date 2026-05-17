<h2 class="mb-3">Validação de QR por Evento</h2>
<p class="text-muted">URL direta: <code>/eventos</code>. Seleciona o evento e valida o QR com a câmara do telemóvel.</p>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Evento</label>
                <select id="eventFilter" class="form-select">
                    <option value="">Todos os eventos</option>
                    <?php foreach ($eventOverview as $event): ?>
                        <option value="<?= (int)$event['id'] ?>"><?= htmlspecialchars($event['title']) ?> · <?= htmlspecialchars($event['date']) ?> <?= htmlspecialchars(substr((string)$event['time'], 0, 5)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button id="startScan" class="btn btn-dark w-100">Iniciar câmara</button>
            </div>
            <div class="col-md-3">
                <button id="stopScan" class="btn btn-outline-secondary w-100" type="button">Parar</button>
            </div>
        </div>

        <div class="mt-3 border rounded p-2 bg-light" style="max-width:420px;">
            <video id="qrVideo" playsinline muted style="width:100%;border-radius:8px;"></video>
        </div>

        <form method="post" action="<?= BASE_URL ?>?controller=reservation&action=validateTicket" class="row g-2 mt-3">
            <input type="hidden" name="redirect" value="eventos">
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
                <div class="alert alert-danger mt-3 mb-0">QR/token inválido ou já utilizado.</div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
(() => {
  const video = document.getElementById('qrVideo');
  const tokenInput = document.getElementById('tokenInput');
  const startBtn = document.getElementById('startScan');
  const stopBtn = document.getElementById('stopScan');
  let stream = null;
  let rafId = null;

  async function start() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      alert('Câmara não suportada neste dispositivo.');
      return;
    }
    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
    video.srcObject = stream;
    await video.play();
    scanLoop();
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

  async function scanLoop() {
    if (!('BarcodeDetector' in window)) {
      return;
    }
    const detector = new BarcodeDetector({ formats: ['qr_code'] });
    const tick = async () => {
      if (!video || video.readyState < 2) {
        rafId = requestAnimationFrame(tick);
        return;
      }
      try {
        const codes = await detector.detect(video);
        if (codes.length > 0 && codes[0].rawValue) {
          tokenInput.value = codes[0].rawValue;
          stop();
        }
      } catch (e) {}
      rafId = requestAnimationFrame(tick);
    };
    tick();
  }

  startBtn.addEventListener('click', start);
  stopBtn.addEventListener('click', stop);
})();
</script>
