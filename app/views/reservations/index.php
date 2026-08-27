<h2 class="mb-4">Reservas dos Eventos</h2>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5 class="mb-3">Modelos de e-mail de confirmação</h5>
        <p class="text-muted">Após a submissão da reserva no site público, o sistema envia o e-mail com o modelo selecionado.</p>
        <form method="post" action="<?= BASE_URL ?>?controller=reservation&action=updateEmailTemplates">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Modelo A</label>
                    <textarea name="reservation_email_template_a" class="form-control" rows="8" required><?= htmlspecialchars($emailTemplateA ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Modelo B</label>
                    <textarea name="reservation_email_template_b" class="form-control" rows="8" required><?= htmlspecialchars($emailTemplateB ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Modelo ativo</label>
                    <select class="form-select" name="reservation_email_template_selected">
                        <option value="a" <?= ($selectedEmailTemplate ?? 'a') === 'a' ? 'selected' : '' ?>>Modelo A</option>
                        <option value="b" <?= ($selectedEmailTemplate ?? 'a') === 'b' ? 'selected' : '' ?>>Modelo B</option>
                    </select>
                    <div class="form-text">Variáveis disponíveis: {customer_name}, {event_title}, {event_date}, {event_time}, {tickets}, {customer_email}, {customer_phone}.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">URL base para validação do QR</label>
                    <input type="text" class="form-control" name="reservation_validation_base_url" value="<?= htmlspecialchars($validationBaseUrl ?? '') ?>" placeholder="https://admin.exemplo.com/index.php?controller=reservation&action=validateTicket&token=">
                    <div class="form-text">O token será anexado no final desta URL para gerar cada QR code.</div>
                </div>
                <div class="col-12">
                    <button class="btn btn-dark">Guardar modelos</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h5 class="mb-3">Configuração por evento</h5>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Evento</th>
                        <th>Link direto</th>
                        <th>Estado reservas</th>
                        <th>Lotação</th>
                        <th>Reservas ativas</th>
                        <th>Lugares disponíveis</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($eventOverview as $event): ?>
                        <?php
                            $capacity = (int)($event['reservation_capacity'] ?? 0);
                            $activeTickets = (int)($event['active_tickets'] ?? 0);
                            $available = $capacity > 0 ? max(0, $capacity - $activeTickets) : null;
                        ?>
                        <tr>
                            <?php $directReservationUrl = 'index.php?reserve_event=' . (int)$event['id'] . '#eventos'; ?>
                            <td>
                                <strong><?= htmlspecialchars($event['title']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($event['date']) ?> às <?= htmlspecialchars(substr((string)$event['time'], 0, 5)) ?></small>
                            </td>
                            <td style="min-width: 280px;">
                                <div class="input-group input-group-sm">
                                    <input type="text" readonly class="form-control" value="<?= htmlspecialchars($directReservationUrl) ?>">
                                    <a class="btn btn-outline-dark" href="<?= htmlspecialchars($directReservationUrl) ?>" target="_blank" rel="noopener">Abrir</a>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= (int)$event['reservations_open'] === 1 ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= (int)$event['reservations_open'] === 1 ? 'Abertas' : 'Fechadas' ?>
                                </span>
                            </td>
                            <td><?= $capacity > 0 ? $capacity : 'Ilimitada' ?></td>
                            <td>
                                <?= $activeTickets ?>
                                <small class="text-muted d-block">Confirmadas: <?= (int)$event['confirmed_tickets'] ?> · Novas: <?= (int)$event['new_tickets'] ?></small>
                            </td>
                            <td><?= $available === null ? 'Sem limite' : $available ?></td>
                            <td>
                                <form method="post" action="<?= BASE_URL ?>?controller=reservation&action=updateEventAvailability" class="d-flex flex-wrap gap-2 justify-content-end">
                                    <input type="hidden" name="event_id" value="<?= (int)$event['id'] ?>">
                                    <div class="form-check form-switch mt-1">
                                        <input class="form-check-input" type="checkbox" role="switch" name="reservations_open" value="1" <?= (int)$event['reservations_open'] === 1 ? 'checked' : '' ?>>
                                        <label class="form-check-label small">Abrir no site</label>
                                    </div>
                                    <input type="number" min="0" name="reservation_capacity" class="form-control form-control-sm" style="max-width: 130px;" value="<?= $capacity ?>" title="0 = ilimitado">
                                    <button class="btn btn-sm btn-outline-dark">Guardar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4" id="validacao-qr">
    <div class="card-body">
        <h5 class="mb-3">Validação de QR code</h5>
        <form method="post" action="<?= BASE_URL ?>?controller=reservation&action=validateTicket" class="row g-2 align-items-end">
            <div class="col-md-9">
                <label class="form-label">Token do bilhete / conteúdo do QR</label>
                <input type="text" name="token" class="form-control" required placeholder="Cole aqui o token lido no scanner">
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100">Validar agora</button>
            </div>
        </form>
        <?php if (!empty($validationResult)): ?>
            <?php if (!empty($validationResult['ok'])): ?>
                <?php $ticket = $validationResult['ticket']; ?>
                <div class="alert alert-success mt-3 mb-0">
                    Bilhete válido ✅ | Evento: <strong><?= htmlspecialchars($ticket['event_title']) ?></strong> (<?= htmlspecialchars($ticket['event_date']) ?> <?= htmlspecialchars(substr((string)$ticket['event_time'], 0, 5)) ?>) |
                    Cliente: <?= htmlspecialchars($ticket['customer_name']) ?> |
                    Bilhete #<?= (int)$ticket['ticket_no'] ?> marcado como utilizado em <?= htmlspecialchars((string)$ticket['used_at']) ?>.
                </div>
            <?php else: ?>
                <?php $ticket = $validationResult['ticket'] ?? null; ?>
                <div class="alert alert-danger mt-3 mb-0">
                    <?php if (($validationResult['reason'] ?? '') === 'already_used' && $ticket): ?>
                        Este bilhete já foi utilizado em <?= htmlspecialchars((string)$ticket['used_at']) ?>.
                    <?php elseif (($validationResult['reason'] ?? '') === 'cancelled' && $ticket): ?>
                        Este bilhete pertence a uma reserva cancelada.
                    <?php elseif (($validationResult['reason'] ?? '') === 'empty'): ?>
                        Introduz um token de bilhete.
                    <?php else: ?>
                        Bilhete não encontrado.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped align-middle searchable-table">
        <thead>
            <tr>
                <th>Evento</th>
                <th>Cliente</th>
                <th>Contacto</th>
                <th>Bilhetes</th>
                <th>Estado</th>
                <th>Ações</th>
                <th>Criada em</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reservations as $reservation): ?>
                <tr>
                    <td>
                        <div class="d-flex gap-2 align-items-start">
                            <?php if (!empty($reservation['event_poster_url'])): ?>
                                <img
                                    src="<?= htmlspecialchars($reservation['event_poster_url']) ?>"
                                    alt="Cartaz do evento <?= htmlspecialchars($reservation['event_title']) ?>"
                                    class="rounded border"
                                    style="width: 64px; height: 84px; object-fit: cover;"
                                >
                            <?php endif; ?>
                            <div>
                                <strong><?= htmlspecialchars($reservation['event_title']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($reservation['event_date']) ?> às <?= htmlspecialchars(substr($reservation['event_time'], 0, 5)) ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <input type="text" name="customer_name" class="form-control form-control-sm mb-1" value="<?= htmlspecialchars($reservation['customer_name']) ?>" form="reservation-<?= (int)$reservation['id'] ?>">
                        <small class="text-muted"><?= htmlspecialchars($reservation['notes'] ?? '') ?></small>
                    </td>
                    <td>
                        <input type="email" name="customer_email" class="form-control form-control-sm mb-1" value="<?= htmlspecialchars($reservation['customer_email']) ?>" form="reservation-<?= (int)$reservation['id'] ?>">
                        <input type="text" name="customer_phone" class="form-control form-control-sm" value="<?= htmlspecialchars($reservation['customer_phone'] ?? '') ?>" form="reservation-<?= (int)$reservation['id'] ?>" placeholder="Telefone">
                    </td>
                    <td>
                        <input type="number" min="1" class="form-control form-control-sm mb-1" name="tickets" value="<?= (int)$reservation['tickets'] ?>" form="reservation-<?= (int)$reservation['id'] ?>">
                        <small class="text-muted">Gerados: <?= (int)$reservation['generated_tickets'] ?> · Validados: <?= (int)$reservation['used_tickets'] ?></small>
                        <?php if (($reservation['admission_status'] ?? 'pending') === 'validated'): ?><span class="badge text-bg-success d-block mt-1">Admissão validada</span><?php endif; ?>
                    </td>
                    <td>
                        <form id="reservation-<?= (int)$reservation['id'] ?>" method="post" action="<?= BASE_URL ?>?controller=reservation&action=update" class="d-flex gap-2">
                            <input type="hidden" name="id" value="<?= (int)$reservation['id'] ?>">
                            <select class="form-select form-select-sm" name="status">
                                <option value="new" <?= $reservation['status'] === 'new' ? 'selected' : '' ?>>Nova</option>
                                <option value="confirmed" <?= $reservation['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmada</option>
                                <option value="cancelled" <?= $reservation['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelada</option>
                            </select>
                        </form>
                    </td>
                    <td>
                        <textarea class="form-control form-control-sm mb-2" name="notes" rows="2" form="reservation-<?= (int)$reservation['id'] ?>" placeholder="Notas internas"><?= htmlspecialchars($reservation['notes'] ?? '') ?></textarea>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-dark" form="reservation-<?= (int)$reservation['id'] ?>">Guardar</button>
                            <form method="post" action="<?= BASE_URL ?>?controller=reservation&action=delete" onsubmit="return confirm('Eliminar esta reserva e os bilhetes associados?');">
                                <input type="hidden" name="id" value="<?= (int)$reservation['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                            </form>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($reservation['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
