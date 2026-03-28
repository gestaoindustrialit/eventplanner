<h2 class="mb-3"><?= $client ? 'Editar' : 'Novo' ?> Cliente</h2>
<form method="post" action="<?= BASE_URL ?>?controller=client&action=<?= $client ? 'update&id=' . $client['id'] : 'store' ?>">
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Nome</label><input class="form-control" name="name" value="<?= htmlspecialchars($client['name'] ?? '') ?>" required></div>
        <div class="col-md-6"><label class="form-label">Pessoa de contacto</label><input class="form-control" name="contact_person" value="<?= htmlspecialchars($client['contact_person'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?= htmlspecialchars($client['phone'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?= htmlspecialchars($client['email'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label">Morada</label><input class="form-control" name="address" value="<?= htmlspecialchars($client['address'] ?? '') ?>"></div>
        <div class="col-12"><label class="form-label">Notas</label><textarea name="notes" class="form-control"><?= htmlspecialchars($client['notes'] ?? '') ?></textarea></div>
    </div>
    <button class="btn btn-dark mt-3">Guardar</button>
</form>
