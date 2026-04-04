<h2 class="mb-3">Os meus eventos</h2>
<div class="table-responsive">
    <table class="table table-striped">
        <thead><tr><th>Data</th><th>Evento</th><th>Local</th><th>Papel</th><th>Cachet</th><th>Detalhes</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($events as $event): ?>
            <tr>
                <td><?= htmlspecialchars($event['date']) ?> <?= htmlspecialchars(substr($event['time'],0,5)) ?></td>
                <td><?= htmlspecialchars($event['title']) ?></td>
                <td><?= htmlspecialchars($event['location']) ?></td>
                <td><span class="badge bg-<?= $event['role'] === 'headliner' ? 'dark' : ($event['role'] === 'host' ? 'secondary' : 'light text-dark') ?>"><?= htmlspecialchars($event['role']) ?></span></td>
                <td>€<?= number_format((float)$event['cachet'],2,',','.') ?></td>
                <td>
                    <?php if (!empty($event['artist_map_link'])): ?>
                        <a target="_blank" href="<?= htmlspecialchars($event['artist_map_link']) ?>">Maps</a><br>
                    <?php endif; ?>
                    <?= !empty($event['artist_details']) ? nl2br(htmlspecialchars($event['artist_details'])) : '-' ?>
                </td>
                <td><a class="btn btn-sm btn-outline-dark" href="<?= BASE_URL ?>?controller=event&action=show&id=<?= $event['id'] ?>">Detalhe</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
