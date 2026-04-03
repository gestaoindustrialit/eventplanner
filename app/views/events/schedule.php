<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Alinhamento: <?= htmlspecialchars($event['title']) ?></h2>
        <small class="text-muted"><?= htmlspecialchars($event['date']) ?> às <?= htmlspecialchars(substr($event['time'], 0, 5)) ?> · <?= htmlspecialchars($event['location']) ?></small>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-dark" href="<?= BASE_URL ?>?controller=event&action=openSchedule">Eventos abertos</a>
        <a class="btn btn-dark" target="_blank" href="<?= BASE_URL ?>?controller=event&action=schedulePdf&id=<?= (int)$event['id'] ?>">Exportar para PDF</a>
    </div>
</div>

<form method="post" action="<?= BASE_URL ?>?controller=event&action=saveSchedule&id=<?= (int)$event['id'] ?>">
    <div id="schedule-wrapper">
        <?php if (empty($scheduleItems)): ?>
            <div class="row g-2 align-items-end mb-3 schedule-row">
                <div class="col-md-2">
                    <label class="form-label">Início</label>
                    <input type="time" class="form-control" name="schedule_starts_at[]" value="<?= htmlspecialchars(substr($event['time'], 0, 5)) ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Duração (min)</label>
                    <input type="number" min="1" class="form-control" name="schedule_duration[]" value="15" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipo</label>
                    <select class="form-select" name="schedule_type[]">
                        <option value="artist">Artista</option>
                        <option value="break">Pausa</option>
                        <option value="technical">Técnico</option>
                        <option value="doors">Portas</option>
                        <option value="other">Outro</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Descrição</label>
                    <input type="text" class="form-control" name="schedule_title[]" placeholder="Ex: Set principal" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Responsável</label>
                    <input type="text" class="form-control" name="schedule_responsible[]" placeholder="Nome">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-outline-danger remove-schedule-row">-</button>
                </div>
                <div class="col-12">
                    <input type="text" class="form-control" name="schedule_notes[]" placeholder="Notas úteis para equipa / cliente">
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($scheduleItems as $item): ?>
                <div class="row g-2 align-items-end mb-3 schedule-row">
                    <div class="col-md-2">
                        <label class="form-label">Início</label>
                        <input type="time" class="form-control" name="schedule_starts_at[]" value="<?= htmlspecialchars(substr($item['starts_at'], 0, 5)) ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Duração (min)</label>
                        <input type="number" min="1" class="form-control" name="schedule_duration[]" value="<?= (int)$item['duration_minutes'] ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" name="schedule_type[]">
                            <?php foreach (['artist' => 'Artista', 'break' => 'Pausa', 'technical' => 'Técnico', 'doors' => 'Portas', 'other' => 'Outro'] as $value => $label): ?>
                                <option value="<?= $value ?>" <?= $item['item_type'] === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Descrição</label>
                        <input type="text" class="form-control" name="schedule_title[]" value="<?= htmlspecialchars($item['title']) ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Responsável</label>
                        <input type="text" class="form-control" name="schedule_responsible[]" value="<?= htmlspecialchars($item['responsible'] ?? '') ?>">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-outline-danger remove-schedule-row">-</button>
                    </div>
                    <div class="col-12">
                        <input type="text" class="form-control" name="schedule_notes[]" value="<?= htmlspecialchars($item['notes'] ?? '') ?>" placeholder="Notas úteis para equipa / cliente">
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <template id="schedule-template">
        <div class="row g-2 align-items-end mb-3 schedule-row">
            <div class="col-md-2">
                <label class="form-label">Início</label>
                <input type="time" class="form-control" name="schedule_starts_at[]" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Duração (min)</label>
                <input type="number" min="1" class="form-control" name="schedule_duration[]" value="15" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Tipo</label>
                <select class="form-select" name="schedule_type[]">
                    <option value="artist">Artista</option>
                    <option value="break">Pausa</option>
                    <option value="technical">Técnico</option>
                    <option value="doors">Portas</option>
                    <option value="other">Outro</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Descrição</label>
                <input type="text" class="form-control" name="schedule_title[]" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Responsável</label>
                <input type="text" class="form-control" name="schedule_responsible[]">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-danger remove-schedule-row">-</button>
            </div>
            <div class="col-12">
                <input type="text" class="form-control" name="schedule_notes[]" placeholder="Notas úteis para equipa / cliente">
            </div>
        </div>
    </template>

    <div class="d-flex justify-content-between mt-3">
        <button type="button" id="add-schedule-row" class="btn btn-outline-secondary">+ Adicionar linha</button>
        <button type="submit" class="btn btn-dark">Guardar alinhamento</button>
    </div>
</form>
