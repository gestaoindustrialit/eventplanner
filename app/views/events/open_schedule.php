<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Eventos abertos para alinhamento</h2>
    <a class="btn btn-outline-dark" href="<?= BASE_URL ?>?controller=event&action=index">Voltar aos eventos</a>
</div>

<?php if (empty($events)): ?>
    <div class="alert alert-info">Não existem eventos abertos (data igual ou superior a hoje).</div>
<?php else: ?>
    <p class="text-muted">Selecione um evento para gerir alinhamento de artistas, pausas e momentos técnicos.</p>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Evento</th>
                <th>Data</th>
                <th>Local</th>
                <th>Cliente</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($events as $event): ?>
                <tr>
                    <td><?= htmlspecialchars($event['title']) ?></td>
                    <td><?= htmlspecialchars($event['date']) ?> <?= htmlspecialchars(substr($event['time'], 0, 5)) ?></td>
                    <td><?= htmlspecialchars($event['location']) ?></td>
                    <td><?= htmlspecialchars($event['client_name'] ?? '-') ?></td>
                    <td>
                        <a class="btn btn-sm btn-dark" href="<?= BASE_URL ?>?controller=event&action=schedule&id=<?= (int)$event['id'] ?>">Gerir alinhamento</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
