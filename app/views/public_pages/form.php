<h2 class="mb-3"><?= $page ? 'Editar' : 'Nova' ?> página pública</h2>

<form method="post" action="<?= BASE_URL ?>?controller=publicpage&action=<?= $page ? 'update&id=' . (int)$page['id'] : 'store' ?>">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Título</label>
            <input class="form-control" name="title" value="<?= htmlspecialchars($page['title'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Slug (URL)</label>
            <input class="form-control" name="slug" value="<?= htmlspecialchars($page['slug'] ?? '') ?>" placeholder="ex.: sobre-nos" required>
        </div>
        <div class="col-12">
            <label class="form-label">Resumo</label>
            <textarea class="form-control" name="excerpt" rows="2" placeholder="Texto curto para destaque no topo"><?= htmlspecialchars($page['excerpt'] ?? '') ?></textarea>
        </div>
        <div class="col-12">
            <label class="form-label">Conteúdo principal (aceita HTML simples)</label>
            <textarea class="form-control" name="content" rows="10" placeholder="Podes usar <h3>, <p>, <ul>, <strong>, <a>, etc."><?= htmlspecialchars($page['content'] ?? '') ?></textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Imagem de capa (URL)</label>
            <input class="form-control" name="hero_image_url" value="<?= htmlspecialchars($page['hero_image_url'] ?? '') ?>" placeholder="https://...">
        </div>
        <div class="col-md-3">
            <label class="form-label">Ordem</label>
            <input type="number" class="form-control" name="sort_order" value="<?= (int)($page['sort_order'] ?? 0) ?>">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_published" id="is_published" <?= isset($page['is_published']) && (int)$page['is_published'] === 1 ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_published">Publicado</label>
            </div>
        </div>
    </div>

    <button class="btn btn-dark mt-3">Guardar página</button>
</form>
