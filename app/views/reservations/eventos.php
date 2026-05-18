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

<div id="scanStatus" class="small text-muted mt-2">Scanner parado.</div>

<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
(() => {
  const video = document.getElementById('qrVideo');
  const tokenInput = document.getElementById('tokenInput');
  const startBtn = document.getElementById('startScan');
  const stopBtn = document.getElementById('stopScan');
  const statusEl = document.getElementById('scanStatus');
  const canvas = document.createElement('canvas');
  const ctx = canvas.getContext('2d', { willReadFrequently: true });

  let stream = null;
  let rafId = null;
  let detector = null;

  function setStatus(message, isError = false) {
    statusEl.textContent = message;
    statusEl.classList.toggle('text-danger', isError);
    statusEl.classList.toggle('text-muted', !isError);
  }

  async function start(event) {
    event.preventDefault();

    if (!window.isSecureContext) {
      setStatus('A câmara requer HTTPS (ou localhost).', true);
      return;
    }

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      setStatus('Câmara não suportada neste dispositivo.', true);
      return;
    }

    try {
      stop();
      setStatus('A pedir acesso à câmara...');
      stream = await navigator.mediaDevices.getUserMedia({
        video: {
          facingMode: { ideal: 'environment' },
          width: { ideal: 1280 },
          height: { ideal: 720 }
        },
        audio: false
      });

      video.srcObject = stream;
      await video.play();
      setStatus('A ler QR... aponta para o código.');

      detector = ('BarcodeDetector' in window)
        ? new BarcodeDetector({ formats: ['qr_code'] })
        : null;

      scanLoop();
    } catch (error) {
      setStatus('Não foi possível abrir a câmara. Verifica permissões no browser.', true);
    }
  }

  function stop(updateStatus = true) {
    if (rafId) {
      cancelAnimationFrame(rafId);
      rafId = null;
    }
    if (stream) {
      stream.getTracks().forEach((track) => track.stop());
      stream = null;
    }
    video.srcObject = null;
    detector = null;
    if (updateStatus) setStatus('Scanner parado.');
  }

  function onDetect(rawValue) {
    if (!rawValue) return;
    tokenInput.value = rawValue;
    stop(false);
    setStatus('QR lido com sucesso. Clica em "Validar".');
  }

  async function detectWithBarcodeDetector() {
    if (!detector) return null;
    const codes = await detector.detect(video);
    return codes[0]?.rawValue ?? null;
  }

  function detectWithJsQr() {
    if (!ctx || video.videoWidth === 0 || video.videoHeight === 0 || typeof jsQR !== 'function') {
      return null;
    }

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const result = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: 'dontInvert' });
    return result?.data ?? null;
  }

  function scanLoop() {
    const tick = async () => {
      if (!stream || !video || video.readyState < 2) {
        rafId = requestAnimationFrame(tick);
        return;
      }

      try {
        const detected = await detectWithBarcodeDetector() || detectWithJsQr();
        if (detected) {
          onDetect(detected);
          return;
        }
      } catch (error) {
        // segue ciclo com fallback
      }

      rafId = requestAnimationFrame(tick);
    };

    tick();
  }

  startBtn.addEventListener('click', start);
  stopBtn.addEventListener('click', (event) => {
    event.preventDefault();
    stop();
  });
})();
</script>
