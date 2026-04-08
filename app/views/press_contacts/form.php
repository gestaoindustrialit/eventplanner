<h2 class="mb-3"><?= $contact ? 'Editar' : 'Novo' ?> Contacto Press</h2>
<form method="post" action="<?= BASE_URL ?>?controller=presscontact&action=<?= $contact ? 'update&id=' . $contact['id'] : 'store' ?>">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Nome</label>
            <input class="form-control" name="name" value="<?= htmlspecialchars($contact['name'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($contact['email'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Localidade</label>
            <input class="form-control" name="locality" value="<?= htmlspecialchars($contact['locality'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Distrito</label>
            <input class="form-control" name="district" value="<?= htmlspecialchars($contact['district'] ?? '') ?>" placeholder="Ex: Lisboa ou Nacional">
        </div>
        <div class="col-md-4">
            <label class="form-label">Website</label>
            <input type="url" class="form-control" name="website" value="<?= htmlspecialchars($contact['website'] ?? '') ?>" placeholder="https://...">
        </div>
    </div>
    <button class="btn btn-dark mt-3">Guardar</button>
</form>
