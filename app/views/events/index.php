<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Eventos</h2>
    <a class="btn btn-dark" href="<?= BASE_URL ?>?controller=event&action=create">Novo Evento</a>
</div>
<input type="text" class="form-control mb-3 table-search" placeholder="Pesquisar evento...">
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
                    <a class="btn btn-sm btn-outline-danger delete-btn" href="<?= BASE_URL ?>?controller=event&action=delete&id=<?= $event['id'] ?>">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
