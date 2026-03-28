<h2 class="mb-3"><?= $comedian ? 'Editar' : 'Novo' ?> Comediante</h2>
<form method="post" action="<?= BASE_URL ?>?controller=comedian&action=<?= $comedian ? 'update&id=' . $comedian['id'] : 'store' ?>">
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Nome</label><input class="form-control" name="name" value="<?= htmlspecialchars($comedian['name'] ?? '') ?>" required></div>
        <div class="col-md-6"><label class="form-label">Nome artístico</label><input class="form-control" name="stage_name" value="<?= htmlspecialchars($comedian['stage_name'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="<?= htmlspecialchars($comedian['email'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?= htmlspecialchars($comedian['phone'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label">Instagram</label><input class="form-control" name="instagram" value="<?= htmlspecialchars($comedian['instagram'] ?? '') ?>"></div>
        <div class="col-md-6"><label class="form-label">Associar utilizador</label>
            <select name="user_id" class="form-select">
                <option value="">-- sem associação --</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= (($comedian['user_id'] ?? null) == $u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['email']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12"><label class="form-label">Notas</label><textarea name="notes" class="form-control"><?= htmlspecialchars($comedian['notes'] ?? '') ?></textarea></div>
    </div>
    <button class="btn btn-dark mt-3">Guardar</button>
</form>
