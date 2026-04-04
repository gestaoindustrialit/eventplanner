<h2 class="mb-3"><?= $comedian ? 'Editar' : 'Novo' ?> Comediante</h2>
<form method="post" enctype="multipart/form-data" action="<?= BASE_URL ?>?controller=comedian&action=<?= $comedian ? 'update&id=' . $comedian['id'] : 'store' ?>">
    <?php $hasUser = !empty($user); ?>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Nome</label><input class="form-control" name="name" value="<?= htmlspecialchars($comedian['name'] ?? '') ?>" required></div>
        <div class="col-md-6"><label class="form-label">Nome artístico</label><input class="form-control" name="stage_name" value="<?= htmlspecialchars($comedian['stage_name'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="<?= htmlspecialchars($comedian['email'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label">Phone</label><input class="form-control" name="phone" value="<?= htmlspecialchars($comedian['phone'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label">Localidade</label><input class="form-control" name="city" value="<?= htmlspecialchars($comedian['city'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label">Instagram</label><input class="form-control" name="instagram" value="<?= htmlspecialchars($comedian['instagram'] ?? '') ?>"></div>
        <div class="col-md-2"><label class="form-label">Valor Bar</label><input class="form-control" type="number" step="1" min="0" max="9999" name="price_bar" value="<?= htmlspecialchars($comedian['price_bar'] ?? '0') ?>"></div>
        <div class="col-md-2"><label class="form-label">Valor Auditório</label><input class="form-control" type="number" step="1" min="0" max="9999" name="price_auditorium" value="<?= htmlspecialchars($comedian['price_auditorium'] ?? '0') ?>"></div>
        <div class="col-12"><label class="form-label">Biografia</label><textarea name="bio" class="form-control" rows="4"><?= htmlspecialchars($comedian['bio'] ?? '') ?></textarea></div>
        <div class="col-md-8">
            <label class="form-label">Ficheiro do comediante</label>
            <input class="form-control" type="file" name="attachment" accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.webp">
            <div class="form-text">Podes anexar press kit, rider técnico ou outros materiais.</div>
        </div>
        <div class="col-md-4">
            <?php if (!empty($comedian['attachment_path'])): ?>
                <label class="form-label d-block">Ficheiro atual</label>
                <a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?= BASE_PATH ?>/public/<?= htmlspecialchars($comedian['attachment_path']) ?>">Abrir ficheiro</a>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" value="1" id="remove_attachment" name="remove_attachment">
                    <label class="form-check-label" for="remove_attachment">Remover ficheiro</label>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-12">
            <hr>
            <h5 class="mb-2">Acesso ao site</h5>
        </div>
        <div class="col-md-4">
            <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" id="user_enabled" name="user_enabled" value="1" <?= $hasUser ? 'checked' : '' ?>>
                <label class="form-check-label" for="user_enabled">Permitir login do comediante</label>
            </div>
        </div>
        <div class="col-md-4"><label class="form-label">Nome de utilizador</label><input class="form-control" name="user_name" value="<?= htmlspecialchars($user['name'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label">Email de acesso</label><input class="form-control" type="email" name="user_email" value="<?= htmlspecialchars($user['email'] ?? '') ?>"></div>
        <div class="col-md-6"><label class="form-label">Palavra-passe <?= $hasUser ? '(deixe vazio para manter)' : '' ?></label><input class="form-control" type="password" name="user_password" autocomplete="new-password"></div>
        <div class="col-md-6">
            <?php if ($hasUser): ?>
                <small class="text-muted d-block mt-4">Este comediante já tem utilizador associado (ID #<?= (int)$user['id'] ?>).</small>
            <?php endif; ?>
        </div>
        <div class="col-12"><label class="form-label">Notas</label><textarea name="notes" class="form-control"><?= htmlspecialchars($comedian['notes'] ?? '') ?></textarea></div>
    </div>
    <button class="btn btn-dark mt-3">Guardar</button>
</form>
