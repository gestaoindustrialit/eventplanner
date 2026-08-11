<h2 class="mb-3"><?= $partner ? 'Editar' : 'Novo' ?> Parceiro</h2>

<form method="post" action="<?= BASE_URL ?>?controller=partner&action=<?= $partner ? 'update&id=' . (int)$partner['id'] : 'store' ?>">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Empresa</label>
            <input class="form-control" name="company_name" value="<?= htmlspecialchars($partner['company_name'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Ramo / tipo de parceria</label>
            <input class="form-control" name="partnership_type" value="<?= htmlspecialchars($partner['partnership_type'] ?? '') ?>" placeholder="Ex.: Media Partner ou Garment Partner" required>
            <div class="form-text">Este texto aparece por baixo do nome da empresa no site.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Data de início da parceria</label>
            <input type="date" class="form-control" name="partnership_start_date" value="<?= htmlspecialchars($partner['partnership_start_date'] ?? '') ?>" required>
            <div class="form-text">A parceria fica visível até 1 ano após esta data, se não for renovada.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Logótipo (URL)</label>
            <input type="url" class="form-control" name="logo_url" value="<?= htmlspecialchars($partner['logo_url'] ?? '') ?>" placeholder="https://.../logo.svg" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Link da empresa</label>
            <input type="url" class="form-control" name="company_url" value="<?= htmlspecialchars($partner['company_url'] ?? '') ?>" placeholder="https://...">
        </div>
        <div class="col-md-3">
            <label class="form-label">Ordem</label>
            <input type="number" class="form-control" name="sort_order" value="<?= (int)($partner['sort_order'] ?? 0) ?>">
        </div>
    </div>
    <button class="btn btn-dark mt-3">Guardar</button>
</form>
