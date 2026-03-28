<h2 class="mb-4">Dashboard Admin</h2>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card"><div class="card-body"><h6>Total eventos</h6><h3><?= $stats['totalEvents'] ?></h3></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><h6>Próximos eventos</h6><h3><?= $stats['upcomingEvents'] ?></h3></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><h6>Total cachets</h6><h3>€<?= number_format($stats['totalCachet'], 2, ',', '.') ?></h3></div></div></div>
</div>

<form class="row g-2 mb-3" method="get" action="<?= BASE_URL ?>">
    <input type="hidden" name="controller" value="dashboard">
    <input type="hidden" name="action" value="index">
    <div class="col-md-3"><input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom ?? '') ?>" class="form-control"></div>
    <div class="col-md-3"><input type="date" name="date_to" value="<?= htmlspecialchars($dateTo ?? '') ?>" class="form-control"></div>
    <div class="col-md-2"><button class="btn btn-outline-dark w-100">Filtrar</button></div>
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
