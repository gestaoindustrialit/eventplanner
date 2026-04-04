<h2 class="mb-3"><?= $event ? 'Editar' : 'Novo' ?> Evento</h2>
<form method="post" action="<?= BASE_URL ?>?controller=event&action=<?= $event ? 'update&id=' . $event['id'] : 'store' ?>">
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Título</label><input required class="form-control" name="title" value="<?= htmlspecialchars($event['title'] ?? '') ?>"></div>
        <div class="col-md-2"><label class="form-label">Data</label><input required type="date" class="form-control" name="date" value="<?= htmlspecialchars($event['date'] ?? '') ?>"></div>
        <div class="col-md-2"><label class="form-label">Hora</label><input required type="time" class="form-control" name="time" value="<?= htmlspecialchars(substr(($event['time'] ?? '20:00'),0,5)) ?>"></div>
        <div class="col-md-2"><label class="form-label">Cachet total</label><input type="number" step="0.01" class="form-control" name="cachet_total" value="<?= htmlspecialchars($event['cachet_total'] ?? '0') ?>"></div>
        <div class="col-md-6"><label class="form-label">Local</label><input class="form-control" name="location" value="<?= htmlspecialchars($event['location'] ?? '') ?>"></div>
        <div class="col-md-6"><label class="form-label">Cliente</label>
            <select class="form-select" name="client_id" required>
                <option value="">-- selecionar --</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?= $client['id'] ?>" <?= (($event['client_id'] ?? null) == $client['id']) ? 'selected' : '' ?>><?= htmlspecialchars($client['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6"><label class="form-label">Link Google Maps (para artistas)</label><input class="form-control" name="artist_map_link" value="<?= htmlspecialchars($event['artist_map_link'] ?? '') ?>" placeholder="https://maps.google.com/..."></div>
        <div class="col-md-6"><label class="form-label">Detalhes para artistas</label><input class="form-control" name="artist_details" value="<?= htmlspecialchars($event['artist_details'] ?? '') ?>" placeholder="Chegada, estacionamento, contacto técnico..."></div>
        <div class="col-12"><label class="form-label">Notas</label><textarea class="form-control" name="notes"><?= htmlspecialchars($event['notes'] ?? '') ?></textarea></div>
    </div>

    <hr class="my-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>Lineup de comediantes</h5>
        <button type="button" class="btn btn-outline-dark btn-sm" id="add-lineup-row">Adicionar comediante</button>
    </div>

    <div id="lineup-wrapper">
        <?php $rows = !empty($lineup) ? $lineup : [['comedian_id'=>'','role'=>'opener','cachet'=>'','notes'=>'']]; ?>
        <?php foreach ($rows as $row): ?>
            <div class="row g-2 mb-2 lineup-row">
                <div class="col-md-4"><select class="form-select" name="lineup_comedian_id[]">
                    <option value="">-- comediante --</option>
                    <?php foreach ($comedians as $comedian): ?>
                        <option value="<?= $comedian['id'] ?>" <?= (($row['comedian_id'] ?? null) == $comedian['id']) ? 'selected' : '' ?>><?= htmlspecialchars($comedian['stage_name'] ?: $comedian['name']) ?></option>
                    <?php endforeach; ?>
                </select></div>
                <div class="col-md-2"><select class="form-select" name="lineup_role[]">
                    <?php foreach (['host','opener','headliner'] as $r): ?>
                        <option value="<?= $r ?>" <?= (($row['role'] ?? '') === $r) ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                    <?php endforeach; ?>
                </select></div>
                <div class="col-md-2"><input class="form-control" type="number" step="0.01" name="lineup_cachet[]" value="<?= htmlspecialchars($row['cachet'] ?? '') ?>" placeholder="Cachet"></div>
                <div class="col-md-3"><input class="form-control" name="lineup_notes[]" value="<?= htmlspecialchars($row['notes'] ?? '') ?>" placeholder="Notas"></div>
                <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100 remove-lineup-row">X</button></div>
            </div>
        <?php endforeach; ?>
    </div>

    <template id="lineup-template">
        <div class="row g-2 mb-2 lineup-row">
            <div class="col-md-4"><select class="form-select" name="lineup_comedian_id[]">
                <option value="">-- comediante --</option>
                <?php foreach ($comedians as $comedian): ?>
                    <option value="<?= $comedian['id'] ?>"><?= htmlspecialchars($comedian['stage_name'] ?: $comedian['name']) ?></option>
                <?php endforeach; ?>
            </select></div>
            <div class="col-md-2"><select class="form-select" name="lineup_role[]"><option value="host">Host</option><option value="opener" selected>Opener</option><option value="headliner">Headliner</option></select></div>
            <div class="col-md-2"><input class="form-control" type="number" step="0.01" name="lineup_cachet[]" placeholder="Cachet"></div>
            <div class="col-md-3"><input class="form-control" name="lineup_notes[]" placeholder="Notas"></div>
            <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100 remove-lineup-row">X</button></div>
        </div>
    </template>

    <button class="btn btn-dark mt-3">Guardar</button>
</form>
