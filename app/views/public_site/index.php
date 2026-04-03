<h2 class="mb-4">Publicar website público</h2>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <p class="text-muted">Esta ação gera o website público (ex.: <code>chorarderir.com</code>) numa pasta fora do EventPlanner, com visual moderno, páginas institucionais, formulário de reservas e newsletter RGPD.</p>

        <form method="post" action="<?= BASE_URL ?>?controller=publicsite&action=publish" class="row g-3">
            <div class="col-12">
                <label class="form-label">Pasta de destino</label>
                <input type="text" name="target_path" class="form-control" value="<?= htmlspecialchars($defaultPath) ?>" required>
                <div class="form-text">Podes indicar uma pasta absoluta, por exemplo <code>/var/www/chorarderir.com</code>.</div>
            </div>

            <div class="col-md-4">
                <label class="form-label">Texto pequeno (home)</label>
                <input type="text" name="home_tagline" class="form-control" value="<?= htmlspecialchars($homeTagline ?? '') ?>" required>
            </div>
            <div class="col-md-8">
                <label class="form-label">Título principal (home)</label>
                <input type="text" name="home_title" class="form-control" value="<?= htmlspecialchars($homeTitle ?? '') ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label">Descrição (home)</label>
                <textarea name="home_description" class="form-control" rows="2" required><?= htmlspecialchars($homeDescription ?? '') ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Texto de consentimento RGPD (newsletter)</label>
                <textarea name="newsletter_consent_text" class="form-control" rows="2" required><?= htmlspecialchars($newsletterConsentText ?? '') ?></textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-primary">Guardar e publicar website</button>
                <p class="text-muted small mt-2 mb-0">Sempre que alterares páginas/textos, publica novamente para atualizar o site externo.</p>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <h5>Páginas prontas para publicação</h5>
        <p class="text-muted mb-3">As páginas marcadas como <strong>Publicado</strong> serão incluídas no menu público.</p>
        <ul class="mb-0">
            <?php foreach ($pages as $page): ?>
                <li>
                    <strong><?= htmlspecialchars($page['title']) ?></strong>
                    (<code><?= htmlspecialchars($page['slug']) ?></code>)
                    - <?= (int)$page['is_published'] === 1 ? 'Publicado' : 'Rascunho' ?>
                </li>
            <?php endforeach; ?>
            <?php if (count($pages) === 0): ?>
                <li class="text-muted">Ainda não existem páginas públicas criadas.</li>
            <?php endif; ?>
        </ul>
        <a class="btn btn-outline-dark btn-sm mt-3" href="<?= BASE_URL ?>?controller=publicpage&action=index">Gerir páginas</a>
    </div>
</div>
