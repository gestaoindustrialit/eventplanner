<?php $isEdit = isset($template['id']); ?>
<h2 class="mb-3"><?= $isEdit ? 'Editar template' : 'Novo template' ?></h2>

<form method="post" action="<?= BASE_URL ?>?controller=checklist&action=<?= $isEdit ? 'update&id=' . (int)$template['id'] : 'store' ?>">
    <div class="row g-3">
        <div class="col-md-7">
            <label class="form-label">Nome</label>
            <input class="form-control" name="name" required value="<?= htmlspecialchars($template['name'] ?? '') ?>">
        </div>
        <div class="col-md-5">
            <label class="form-label">Descrição</label>
            <input class="form-control" name="description" value="<?= htmlspecialchars($template['description'] ?? '') ?>">
        </div>
    </div>

    <hr class="my-4">

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Campos do template</h5>
        <button class="btn btn-sm btn-outline-dark" type="button" id="add-template-field-row">Adicionar campo</button>
    </div>

    <div id="template-field-wrapper" class="d-flex flex-column gap-2">
        <?php foreach ($fields as $i => $field): ?>
            <div class="row g-2 align-items-center template-field-row">
                <div class="col-md-6">
                    <input class="form-control" name="field_label[]" placeholder="Ex: Confirmar rider técnico" value="<?= htmlspecialchars($field['label'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="field_type[]">
                        <option value="checkbox" <?= ($field['field_type'] ?? 'checkbox') === 'checkbox' ? 'selected' : '' ?>>Checkbox</option>
                        <option value="text" <?= ($field['field_type'] ?? '') === 'text' ? 'selected' : '' ?>>Texto</option>
                    </select>
                </div>
                <div class="col-md-2 form-check mt-0 ps-4">
                    <input class="form-check-input" type="checkbox" name="field_required[]" value="<?= (int)$i ?>" <?= !empty($field['is_required']) ? 'checked' : '' ?>>
                    <label class="form-check-label">Obrigatório</label>
                </div>
                <div class="col-md-1 text-end">
                    <button class="btn btn-sm btn-outline-danger remove-template-field-row" type="button">×</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <template id="template-field-template">
        <div class="row g-2 align-items-center template-field-row">
            <div class="col-md-6">
                <input class="form-control" name="field_label[]" placeholder="Ex: Confirmar rider técnico">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="field_type[]">
                    <option value="checkbox">Checkbox</option>
                    <option value="text">Texto</option>
                </select>
            </div>
            <div class="col-md-2 form-check mt-0 ps-4">
                <input class="form-check-input" type="checkbox" name="field_required[]" value="__INDEX__">
                <label class="form-check-label">Obrigatório</label>
            </div>
            <div class="col-md-1 text-end">
                <button class="btn btn-sm btn-outline-danger remove-template-field-row" type="button">×</button>
            </div>
        </div>
    </template>

    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary">Guardar</button>
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>?controller=checklist&action=index">Cancelar</a>
    </div>
</form>
