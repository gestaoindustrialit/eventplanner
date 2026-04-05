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
                            <td>
                                <strong><?= htmlspecialchars($event['title']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($event['date']) ?> às <?= htmlspecialchars(substr((string)$event['time'], 0, 5)) ?></small>
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

<div class="table-responsive">
    <table class="table table-striped align-middle searchable-table">
        <thead>
            <tr>
                <th>Evento</th>
                <th>Cliente</th>
                <th>Contacto</th>
                <th>Bilhetes</th>
                <th>Estado</th>
                <th>Criada em</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reservations as $reservation): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($reservation['event_title']) ?></strong><br>
                        <small class="text-muted"><?= htmlspecialchars($reservation['event_date']) ?> às <?= htmlspecialchars(substr($reservation['event_time'], 0, 5)) ?></small>
                    </td>
                    <td>
                        <?= htmlspecialchars($reservation['customer_name']) ?><br>
                        <small class="text-muted"><?= htmlspecialchars($reservation['notes'] ?? '') ?></small>
                    </td>
                    <td>
                        <?= htmlspecialchars($reservation['customer_email']) ?><br>
                        <small class="text-muted"><?= htmlspecialchars($reservation['customer_phone'] ?? '-') ?></small>
                    </td>
                    <td><?= (int)$reservation['tickets'] ?></td>
                    <td>
                        <form method="post" action="<?= BASE_URL ?>?controller=reservation&action=updateStatus" class="d-flex gap-2">
                            <input type="hidden" name="id" value="<?= (int)$reservation['id'] ?>">
                            <select class="form-select form-select-sm" name="status">
                                <option value="new" <?= $reservation['status'] === 'new' ? 'selected' : '' ?>>Nova</option>
                                <option value="confirmed" <?= $reservation['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmada</option>
                                <option value="cancelled" <?= $reservation['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelada</option>
                            </select>
                            <button class="btn btn-sm btn-outline-dark">Guardar</button>
                        </form>
                    </td>
                    <td><?= htmlspecialchars($reservation['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
