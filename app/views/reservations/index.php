<h2 class="mb-4">Reservas dos Eventos</h2>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Modelos de e-mail de confirmação</h5>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#emailTemplates" aria-expanded="false" aria-controls="emailTemplates">
            Mostrar/ocultar <i class="bi bi-chevron-down ms-1"></i>
        </button>
    </div>
    <div class="collapse" id="emailTemplates">
    <div class="card-body">
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
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <h5 class="mb-0">Configuração por evento</h5>
        <?php $configurationExpanded = isset($_GET['event_filter']) || isset($_GET['event_page']); ?>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#eventConfiguration" aria-expanded="<?= $configurationExpanded ? 'true' : 'false' ?>" aria-controls="eventConfiguration">
            Mostrar/ocultar <i class="bi bi-chevron-down ms-1"></i>
        </button>
    </div>
    <div class="collapse <?= $configurationExpanded ? 'show' : '' ?>" id="eventConfiguration">
    <div class="card-body">
        <form method="get" action="<?= BASE_URL ?>" class="row g-2 align-items-end mb-3">
            <input type="hidden" name="controller" value="reservation">
            <input type="hidden" name="action" value="index">
            <div class="col-sm-8 col-md-4">
                <label class="form-label" for="event-filter">Mostrar eventos</label>
                <select class="form-select" id="event-filter" name="event_filter">
                    <option value="upcoming" <?= $eventFilter === 'upcoming' ? 'selected' : '' ?>>Futuros</option>
                    <option value="open" <?= $eventFilter === 'open' ? 'selected' : '' ?>>Com reservas abertas</option>
                </select>
            </div>
            <div class="col-sm-4"><button class="btn btn-outline-dark">Filtrar</button></div>
        </form>
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
                    <?php if (empty($eventOverview)): ?>
                        <tr><td colspan="7" class="text-muted py-4 text-center">Não existem eventos para este filtro.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($eventPages > 1): ?>
            <nav class="mt-3" aria-label="Paginação da configuração por evento">
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($page = 1; $page <= $eventPages; $page++): ?>
                        <li class="page-item <?= $page === $eventPage ? 'active' : '' ?>">
                            <a class="page-link" href="<?= BASE_URL ?>?controller=reservation&amp;action=index&amp;event_filter=<?= urlencode($eventFilter) ?>&amp;event_page=<?= $page ?>#eventConfiguration"><?= $page ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="<?= BASE_URL ?>" class="row g-2 align-items-end">
            <input type="hidden" name="controller" value="reservation">
            <input type="hidden" name="action" value="index">
            <div class="col-md-5">
                <label class="form-label" for="reservation-event">Evento</label>
                <select class="form-select" id="reservation-event" name="event_id">
                    <option value="0">Todos os eventos</option>
                    <?php foreach ($reservationEvents as $event): ?>
                        <option value="<?= (int)$event['id'] ?>" <?= (int)$selectedEventId === (int)$event['id'] ? 'selected' : '' ?>><?= htmlspecialchars($event['title']) ?> — <?= htmlspecialchars($event['date']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="admission-filter">Admissão</label>
                <select class="form-select" id="admission-filter" name="admission">
                    <option value="all" <?= $admissionFilter === 'all' ? 'selected' : '' ?>>Todas</option>
                    <option value="pending" <?= $admissionFilter === 'pending' ? 'selected' : '' ?>>Por validar</option>
                    <option value="validated" <?= $admissionFilter === 'validated' ? 'selected' : '' ?>>Validadas</option>
                </select>
            </div>
            <div class="col-md-4 d-flex flex-wrap gap-2">
                <button class="btn btn-dark">Filtrar reservas</button>
                <a class="btn btn-outline-success" href="<?= BASE_URL ?>?controller=reservation&amp;action=export&amp;event_id=<?= (int)$selectedEventId ?>&amp;admission=<?= urlencode($admissionFilter) ?>"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Exportar Excel</a>
            </div>
        </form>
        <form method="post" action="<?= BASE_URL ?>?controller=reservation&amp;action=addToNewsletter" class="mt-3" onsubmit="return confirm('Adicionar à newsletter apenas os contactos deste evento que deram consentimento?');">
            <input type="hidden" name="event_id" value="<?= (int)$selectedEventId ?>">
            <button class="btn btn-outline-primary" <?= $selectedEventId <= 0 ? 'disabled' : '' ?>><i class="bi bi-person-plus me-1"></i>Adicionar contactos do evento à newsletter</button>
            <small class="text-muted ms-2">Segmentação automática pela cidade do evento; só são incluídos contactos com consentimento RGPD.</small>
        </form>
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
            <?php if (empty($reservations)): ?>
                <tr><td colspan="7" class="text-muted py-4 text-center">Não existem reservas para os filtros selecionados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
