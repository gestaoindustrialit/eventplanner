<h2 class="mb-3">Novo template</h2>

<form method="post" action="<?= BASE_URL ?>?controller=checklist&action=store">
    <div class="row g-3">
        <div class="col-md-7">
            <label class="form-label">Nome</label>
            <input class="form-control" name="name" required>
        </div>
        <div class="col-md-5">
            <label class="form-label">Descrição</label>
            <input class="form-control" name="description">
        </div>
    </div>

    <hr class="my-4">

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Campos do template</h5>
        <button class="btn btn-sm btn-outline-dark" type="button" id="add-template-field-row">Adicionar campo</button>
    </div>

    <div id="template-field-wrapper" class="d-flex flex-column gap-2">
        <div class="row g-2 align-items-center template-field-row">
            <div class="col-md-6">
                <input class="form-control" name="field_label[]" placeholder="Ex: Confirmar rider técnico">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="field_type[]">
                    <option value="checkbox" selected>Checkbox</option>
                    <option value="text">Texto</option>
                </select>
            </div>
            <div class="col-md-2 form-check mt-0 ps-4">
                <input class="form-check-input" type="checkbox" name="field_required[]" value="0">
                <label class="form-check-label">Obrigatório</label>
            </div>
            <div class="col-md-1 text-end">
                <button class="btn btn-sm btn-outline-danger remove-template-field-row" type="button">×</button>
            </div>
        </div>
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
