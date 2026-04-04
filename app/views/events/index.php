<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h2 class="mb-1">Eventos</h2>
        <p class="text-muted mb-0">Gestão de agenda e operações de cada espetáculo.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-dark" href="<?= BASE_URL ?>?controller=event&action=openSchedule">Alinhamentos</a>
        <a class="btn btn-dark" href="<?= BASE_URL ?>?controller=event&action=create">Novo Evento</a>
    </div>
</div>
<div class="input-group mb-3">
    <span class="input-group-text bg-white">🔎</span>
    <input type="text" class="form-control table-search" placeholder="Pesquisar evento por título, local ou cliente...">
</div>
<div class="table-responsive">
    <table class="table table-striped searchable-table">
        <thead><tr><th>Título</th><th>Data</th><th>Local</th><th>Cliente</th><th>Cachet</th><th>Ações</th></tr></thead>
        <tbody>
        <?php foreach ($events as $event): ?>
            <tr>
                <td><?= htmlspecialchars($event['title']) ?></td>
                <td><?= htmlspecialchars($event['date']) ?> <?= htmlspecialchars(substr($event['time'], 0, 5)) ?></td>
                <td><?= htmlspecialchars($event['location']) ?></td>
                <td><?= htmlspecialchars($event['client_name'] ?? '-') ?></td>
                <td>€<?= number_format((float)$event['cachet_total'], 2, ',', '.') ?></td>
                <td>
                    <a class="btn btn-sm btn-outline-dark" href="<?= BASE_URL ?>?controller=event&action=show&id=<?= $event['id'] ?>">Ver</a>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>?controller=event&action=edit&id=<?= $event['id'] ?>">Editar</a>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary duplicate-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#duplicateEventModal"
                        data-event-id="<?= $event['id'] ?>"
                        data-event-title="<?= htmlspecialchars($event['title']) ?>"
                        data-event-date="<?= htmlspecialchars($event['date']) ?>"
                        data-duplicate-url="<?= BASE_URL ?>?controller=event&action=duplicate&id=<?= $event['id'] ?>"
                    >Duplicar</button>
                    <a class="btn btn-sm btn-outline-danger delete-btn" href="<?= BASE_URL ?>?controller=event&action=delete&id=<?= $event['id'] ?>">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="duplicateEventModal" tabindex="-1" aria-labelledby="duplicateEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content" id="duplicate-event-form">
            <div class="modal-header">
                <h5 class="modal-title" id="duplicateEventModalLabel">Duplicar evento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Selecione a nova data para o evento duplicado.</p>
                <p class="small text-muted" id="duplicate-event-summary"></p>
                <label for="duplicate-event-date" class="form-label">Nova data</label>
                <input type="date" class="form-control" id="duplicate-event-date" name="date" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Duplicar evento</button>
            </div>
        </form>
    </div>
</div>
