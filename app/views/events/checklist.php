<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Checklist do evento</h2>
        <p class="text-muted mb-0"><?= htmlspecialchars($event['title']) ?> — <?= htmlspecialchars($event['date']) ?></p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>?controller=event&action=show&id=<?= (int)$event['id'] ?>">Voltar ao evento</a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Aplicar template</h5>
        <form method="post" action="<?= BASE_URL ?>?controller=checklist&action=assignTemplate&event_id=<?= (int)$event['id'] ?>" class="row g-2 align-items-end">
            <div class="col-md-8">
                <label class="form-label">Template</label>
                <select class="form-select" name="template_id" required>
                    <option value="">Seleciona um template...</option>
                    <?php foreach ($templates as $template): ?>
                        <option value="<?= (int)$template['id'] ?>"><?= htmlspecialchars($template['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button class="btn btn-dark w-100">Usar neste evento</button>
            </div>
        </form>
    </div>
</div>

<form method="post" action="<?= BASE_URL ?>?controller=checklist&action=saveEventChecklist&event_id=<?= (int)$event['id'] ?>">
    <input type="hidden" name="template_id" value="<?= htmlspecialchars($checklist['template_id'] ?? '') ?>">

    <div class="card">
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-8">
                    <label class="form-label">Nome da checklist</label>
                    <input class="form-control" name="checklist_name" value="<?= htmlspecialchars($checklist['name'] ?? 'Checklist do evento') ?>" required>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-outline-dark w-100" type="button" id="add-event-checklist-item">Adicionar novo campo</button>
                </div>
            </div>

            <div id="event-checklist-wrapper" class="d-flex flex-column gap-2">
                <?php $items = $checklist['items'] ?? []; ?>
                <?php if (!$items): ?>
                    <?php $items = [['label' => '', 'field_type' => 'checkbox', 'is_required' => 0, 'value' => '', 'is_checked' => 0]]; ?>
                <?php endif; ?>

                <?php foreach ($items as $i => $item): ?>
                    <div class="row g-2 align-items-center event-checklist-row">
                        <div class="col-md-4">
                            <input class="form-control" name="item_label[]" placeholder="Nome do campo" value="<?= htmlspecialchars($item['label'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select event-item-type" name="item_type[]">
                                <option value="checkbox" <?= ($item['field_type'] ?? 'checkbox') === 'checkbox' ? 'selected' : '' ?>>Checkbox</option>
                                <option value="text" <?= ($item['field_type'] ?? '') === 'text' ? 'selected' : '' ?>>Texto</option>
                            </select>
                        </div>
                        <div class="col-md-2 form-check mt-0 ps-4">
                            <input class="form-check-input" type="checkbox" name="item_required[]" value="<?= (int)$i ?>" <?= !empty($item['is_required']) ? 'checked' : '' ?>>
                            <label class="form-check-label">Obrigatório</label>
                        </div>
                        <div class="col-md-3 event-value-col">
                            <input class="form-control event-item-value-text <?= ($item['field_type'] ?? 'checkbox') === 'checkbox' ? 'd-none' : '' ?>" name="item_value[]" placeholder="Preenche aqui" value="<?= htmlspecialchars($item['value'] ?? '') ?>">
                            <div class="form-check event-item-value-check <?= ($item['field_type'] ?? 'checkbox') === 'text' ? 'd-none' : '' ?>">
                                <input class="form-check-input" type="checkbox" name="item_checked[]" value="<?= (int)$i ?>" <?= !empty($item['is_checked']) ? 'checked' : '' ?>>
                                <label class="form-check-label">Concluído</label>
                            </div>
                        </div>
                        <div class="col-md-1 text-end">
                            <button class="btn btn-sm btn-outline-danger remove-event-checklist-item" type="button">×</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <template id="event-checklist-template">
                <div class="row g-2 align-items-center event-checklist-row">
                    <div class="col-md-4">
                        <input class="form-control" name="item_label[]" placeholder="Nome do campo">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select event-item-type" name="item_type[]">
                            <option value="checkbox">Checkbox</option>
                            <option value="text">Texto</option>
                        </select>
                    </div>
                    <div class="col-md-2 form-check mt-0 ps-4">
                        <input class="form-check-input" type="checkbox" name="item_required[]" value="__INDEX__">
                        <label class="form-check-label">Obrigatório</label>
                    </div>
                    <div class="col-md-3 event-value-col">
                        <input class="form-control event-item-value-text d-none" name="item_value[]" placeholder="Preenche aqui">
                        <div class="form-check event-item-value-check">
                            <input class="form-check-input" type="checkbox" name="item_checked[]" value="__INDEX__">
                            <label class="form-check-label">Concluído</label>
                        </div>
                    </div>
                    <div class="col-md-1 text-end">
                        <button class="btn btn-sm btn-outline-danger remove-event-checklist-item" type="button">×</button>
                    </div>
                </div>
            </template>

            <div class="mt-3">
                <button class="btn btn-primary">Guardar checklist</button>
            </div>
        </div>
    </div>
</form>
