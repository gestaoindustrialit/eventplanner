<h2 class="mb-3"><?= htmlspecialchars($event['title']) ?></h2>
<?php if (isAdmin()): ?>
    <div class="mb-3 d-flex gap-2">
        <a class="btn btn-sm btn-outline-dark" href="<?= BASE_URL ?>?controller=event&action=schedule&id=<?= (int)$event['id'] ?>">Gerir alinhamento</a>
        <a class="btn btn-sm btn-dark" target="_blank" href="<?= BASE_URL ?>?controller=event&action=schedulePdf&id=<?= (int)$event['id'] ?>">Exportar PDF</a>
    </div>
<?php endif; ?>
<div class="card mb-3"><div class="card-body">
    <p><strong>Data:</strong> <?= htmlspecialchars($event['date']) ?> <?= htmlspecialchars(substr($event['time'], 0, 5)) ?></p>
    <p><strong>Local:</strong> <?= htmlspecialchars($event['location']) ?></p>
    <p><strong>Cliente:</strong> <?= htmlspecialchars($event['client_name'] ?? '-') ?></p>
    <p><strong>Contacto cliente:</strong> <?= htmlspecialchars($event['contact_person'] ?? '-') ?> | <?= htmlspecialchars($event['client_phone'] ?? '-') ?> | <?= htmlspecialchars($event['client_email'] ?? '-') ?></p>
    <p><strong>Morada cliente:</strong> <?= htmlspecialchars($event['client_address'] ?? '-') ?></p>
    <p><strong>Notas:</strong> <?= nl2br(htmlspecialchars($event['notes'] ?? '')) ?></p>
</div></div>

<h5>Lineup</h5>
<div class="table-responsive">
    <table class="table table-bordered">
        <thead><tr><th>Comediante</th><th>Papel</th><th>Cachet</th><th>Notas</th></tr></thead>
        <tbody>
        <?php foreach ($lineup as $member): ?>
            <tr>
                <td><?= htmlspecialchars($member['stage_name'] ?: $member['name']) ?></td>
                <td><span class="badge bg-<?= $member['role'] === 'headliner' ? 'dark' : ($member['role'] === 'host' ? 'secondary' : 'light text-dark') ?>"><?= htmlspecialchars($member['role']) ?></span></td>
                <td>€<?= number_format((float)$member['cachet'], 2, ',', '.') ?></td>
                <td><?= htmlspecialchars($member['notes']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
