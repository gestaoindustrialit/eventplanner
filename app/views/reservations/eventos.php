<div class="admissions-page">
    <div class="admissions-hero mb-3">
        <div>
            <span class="admissions-eyebrow"><i class="bi bi-shield-check"></i> Controlo de entrada</span>
            <h1 class="mb-2">Admissões</h1>
            <p class="mb-0">Seleciona o evento e aponta a câmara ao QR code.</p>
        </div>
        <div class="admissions-live"><span></span> Operação ativa</div>
    </div>

    <?php if (empty($eventOverview)): ?>
        <div class="alert alert-info">Não existem eventos abertos com reservas.</div>
    <?php else: ?>
        <div class="admissions-grid">
            <section class="card shadow-sm admissions-scanner-card">
                <div class="card-body">
                    <label for="eventFilter" class="form-label fw-semibold">Evento em validação</label>
                    <select id="eventFilter" class="form-select form-select-lg mb-3">
                        <?php foreach ($eventOverview as $event): ?>
                            <option value="<?= (int)$event['id'] ?>" <?= ((int)$selectedEventId === (int)$event['id']) ? 'selected' : '' ?>><?= htmlspecialchars($event['title']) ?> · <?= htmlspecialchars($event['date']) ?> <?= htmlspecialchars(substr((string)$event['time'], 0, 5)) ?> (<?= (int)$event['active_tickets'] ?>)</option>
                        <?php endforeach; ?>
                    </select>

                    <div class="admissions-camera">
                        <video id="qrVideo" playsinline muted></video>
                        <div id="cameraPlaceholder" class="admissions-camera-placeholder"><i class="bi bi-qr-code-scan"></i><span>Câmara pronta para iniciar</span></div>
                    </div>

                    <div class="d-grid gap-2 d-sm-flex mt-3">
                        <button id="startScan" type="button" class="btn btn-primary btn-lg flex-fill"><i class="bi bi-camera-fill"></i> Iniciar câmara</button>
                        <button id="stopScan" type="button" class="btn btn-outline-secondary btn-lg flex-fill" disabled>Parar</button>
                    </div>

                    <form id="validateTicketForm" class="mt-3">
                        <input id="tokenInput" type="text" name="token" class="form-control form-control-lg" required autocomplete="off" placeholder="Token lido / QR payload">
                        <button class="btn btn-dark btn-lg w-100 mt-2">Validar bilhete</button>
                    </form>
                    <div id="validationFeedback" class="mt-3" aria-live="polite"></div>
                </div>
            </section>

            <section class="card shadow-sm admissions-list-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                        <h2 class="h5 mb-0">Reservas deste evento</h2>
                        <span id="ticketCount" class="badge rounded-pill text-bg-dark"><?= count($ticketsOverview) ?> bilhetes</span>
                    </div>
                    <div id="ticketsOverview" class="admissions-ticket-list">
                        <?php foreach ($ticketsOverview as $ticket): ?>
                            <article class="admissions-ticket <?= (int)$ticket['is_used'] === 1 ? 'is-validated' : '' ?>" data-ticket-id="<?= (int)$ticket['id'] ?>">
                                <div><strong><?= htmlspecialchars($ticket['customer_name']) ?></strong><small>Bilhete #<?= (int)$ticket['ticket_no'] ?></small></div>
                                <div class="text-end">
                                    <span class="badge <?= (int)$ticket['is_used'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int)$ticket['is_used'] === 1 ? 'Validado' : 'A validar' ?></span>
                                    <?php if ((int)$ticket['is_used'] === 1): ?><button type="button" class="btn btn-sm btn-link reset-ticket">Alterar para A validar</button><?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <p id="emptyTickets" class="text-muted text-center py-4 <?= empty($ticketsOverview) ? '' : 'd-none' ?>">Sem bilhetes para apresentar.</p>
                </div>
            </section>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($eventOverview)): ?>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
<script>
(() => {
  const endpoint = <?= json_encode(BASE_URL) ?>;
  const video = document.getElementById('qrVideo');
  const placeholder = document.getElementById('cameraPlaceholder');
  const tokenInput = document.getElementById('tokenInput');
  const startBtn = document.getElementById('startScan');
  const stopBtn = document.getElementById('stopScan');
  const form = document.getElementById('validateTicketForm');
  const eventFilter = document.getElementById('eventFilter');
  const feedback = document.getElementById('validationFeedback');
  const list = document.getElementById('ticketsOverview');
  const canvas = document.createElement('canvas');
  const ctx = canvas.getContext('2d', { willReadFrequently: true });
  let stream = null, rafId = null, submitting = false, lastPayload = '';

  const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));

  function showFeedback(type, message) {
    feedback.innerHTML = `<div class="alert alert-${type} mb-0">${message}</div>`;
  }

  async function refreshTickets() {
    const response = await fetch(`${endpoint}?controller=reservation&action=admissionsData&event_id=${encodeURIComponent(eventFilter.value)}`, {headers: {'Accept':'application/json'}});
    const data = await response.json();
    if (!data.ok) return;
    list.innerHTML = data.tickets.map(ticket => {
      const used = Number(ticket.is_used) === 1;
      return `<article class="admissions-ticket ${used ? 'is-validated' : ''}" data-ticket-id="${Number(ticket.id)}">
        <div><strong>${escapeHtml(ticket.customer_name)}</strong><small>Bilhete #${Number(ticket.ticket_no)}</small></div>
        <div class="text-end"><span class="badge ${used ? 'text-bg-success' : 'text-bg-secondary'}">${used ? 'Validado' : 'A validar'}</span>
        ${used ? '<button type="button" class="btn btn-sm btn-link reset-ticket">Alterar para A validar</button>' : ''}</div></article>`;
    }).join('');
    document.getElementById('ticketCount').textContent = `${data.tickets.length} bilhetes`;
    document.getElementById('emptyTickets').classList.toggle('d-none', data.tickets.length > 0);
  }

  async function validateTicket(event) {
    event.preventDefault();
    if (submitting) return;
    submitting = true;
    try {
      const body = new URLSearchParams({token: tokenInput.value.trim(), event_id: eventFilter.value});
      const response = await fetch(`${endpoint}?controller=reservation&action=validateTicket`, {method:'POST', headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}, body});
      const result = await response.json();
      if (result.ok) {
        showFeedback('success', `Bilhete validado: <strong>${escapeHtml(result.ticket.customer_name)}</strong>, #${Number(result.ticket.ticket_no)}.`);
        tokenInput.value = '';
      } else {
        const messages = {already_used:'Este QR code já foi validado. Altera-o para “A validar” antes de o voltar a digitalizar.', cancelled:'Esta reserva está cancelada.', wrong_event:'Este bilhete pertence a outro evento.', empty:'Introduz um token.', not_found:'QR code ou token inválido.'};
        showFeedback('danger', messages[result.reason] || 'Não foi possível validar este bilhete.');
      }
      await refreshTickets();
    } catch (error) {
      showFeedback('danger', 'Falha de ligação. Tenta novamente.');
    } finally {
      submitting = false;
      lastPayload = '';
    }
  }

  async function start() {
    stop();
    if (!navigator.mediaDevices?.getUserMedia) return showFeedback('warning', 'A câmara não é suportada neste dispositivo.');
    try {
      stream = await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'environment'}},audio:false});
      video.srcObject = stream;
      await video.play();
      placeholder.classList.add('d-none'); startBtn.disabled = true; stopBtn.disabled = false;
      scanLoop();
    } catch (error) { showFeedback('warning', 'Não foi possível abrir a câmara. Verifica as permissões do navegador.'); }
  }

  function stop() {
    if (rafId) cancelAnimationFrame(rafId);
    rafId = null;
    if (stream) stream.getTracks().forEach(track => track.stop());
    stream = null; video.srcObject = null;
    placeholder.classList.remove('d-none'); startBtn.disabled = false; stopBtn.disabled = true;
  }

  function scanLoop() {
    const tick = () => {
      if (video.readyState >= 2 && !submitting) {
        canvas.width = video.videoWidth; canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0); const image = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const qr = window.jsQR?.(image.data, image.width, image.height);
        if (qr?.data && qr.data !== lastPayload) { lastPayload = qr.data; tokenInput.value = qr.data; form.requestSubmit(); }
      }
      rafId = requestAnimationFrame(tick);
    }; tick();
  }

  form.addEventListener('submit', validateTicket);
  startBtn.addEventListener('click', start);
  stopBtn.addEventListener('click', stop);
  eventFilter.addEventListener('change', () => { history.replaceState(null, '', `${endpoint}?controller=reservation&action=eventos&event_id=${encodeURIComponent(eventFilter.value)}`); feedback.innerHTML = ''; refreshTickets(); });
  list.addEventListener('click', async event => {
    const button = event.target.closest('.reset-ticket'); if (!button) return;
    button.disabled = true;
    const body = new URLSearchParams({ticket_id: button.closest('[data-ticket-id]').dataset.ticketId});
    const response = await fetch(`${endpoint}?controller=reservation&action=markTicketPending`, {method:'POST', headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}, body});
    const result = await response.json();
    showFeedback(result.ok ? 'info' : 'danger', result.ok ? 'Bilhete alterado para “A validar”. Já pode ser novamente digitalizado.' : 'Não foi possível alterar o bilhete.');
    await refreshTickets();
  });
  window.addEventListener('pagehide', stop);
})();
</script>
<?php endif; ?>
