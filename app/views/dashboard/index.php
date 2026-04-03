<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h2 class="mb-1">Dashboard Admin</h2>
        <p class="text-muted mb-0">Visão geral de eventos, cachets e calendário.</p>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="text-muted">Total eventos</h6>
                <h3 class="mb-0"><?= $stats['totalEvents'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="text-muted">Próximos eventos</h6>
                <h3 class="mb-0"><?= $stats['upcomingEvents'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="text-muted">Total cachets</h6>
                <h3 class="mb-0">€<?= number_format($stats['totalCachet'], 2, ',', '.') ?></h3>
            </div>
        </div>
    </div>
</div>

<form class="row g-2 mb-3 align-items-end" method="get" action="<?= BASE_URL ?>">
    <input type="hidden" name="controller" value="dashboard">
    <input type="hidden" name="action" value="index">
    <div class="col-12 col-md-4">
        <label class="form-label mb-1">Data início</label>
        <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom ?? '') ?>" class="form-control">
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label mb-1">Data fim</label>
        <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo ?? '') ?>" class="form-control">
    </div>
    <div class="col-12 col-md-3">
        <button class="btn btn-outline-dark w-100">Filtrar</button>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-striped searchable-table">
        <thead><tr><th>Título</th><th>Data</th><th>Hora</th><th>Local</th><th>Cliente</th><th>Cachet Total</th></tr></thead>
        <tbody>
        <?php foreach ($events as $event): ?>
            <tr>
                <td><?= htmlspecialchars($event['title']) ?></td>
                <td><?= htmlspecialchars($event['date']) ?></td>
                <td><?= htmlspecialchars(substr($event['time'], 0, 5)) ?></td>
                <td><?= htmlspecialchars($event['location']) ?></td>
                <td><?= htmlspecialchars($event['client_name'] ?? '-') ?></td>
                <td>€<?= number_format((float)$event['cachet_total'], 2, ',', '.') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
