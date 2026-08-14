<h2 class="mb-3"><?= $post ? 'Editar' : 'Novo' ?> post</h2>

<form method="post" action="<?= BASE_URL ?>?controller=blogpost&action=<?= $post ? 'update&id=' . (int)$post['id'] : 'store' ?>">
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label">Título</label>
            <input class="form-control form-control-lg" name="title" value="<?= htmlspecialchars($post['title'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Slug</label>
            <input class="form-control form-control-lg" name="slug" value="<?= htmlspecialchars($post['slug'] ?? '') ?>" placeholder="como-contratar-humorista" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Categoria</label>
            <input class="form-control" name="category" value="<?= htmlspecialchars($post['category'] ?? '') ?>" placeholder="Eventos corporativos">
        </div>
        <div class="col-md-4">
            <label class="form-label">Data de publicação</label>
            <input type="date" class="form-control" name="published_at" value="<?= htmlspecialchars((string)($post['published_at'] ?? date('Y-m-d'))) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Ordem</label>
            <input type="number" class="form-control" name="sort_order" value="<?= (int)($post['sort_order'] ?? 0) ?>">
        </div>
        <div class="col-12">
            <label class="form-label">Resumo / lead</label>
            <textarea class="form-control" name="excerpt" rows="3" maxlength="320" placeholder="Resumo curto usado nos cards, meta description fallback e topo do artigo."><?= htmlspecialchars($post['excerpt'] ?? '') ?></textarea>
        </div>
        <div class="col-12">
            <label class="form-label">Conteúdo do artigo (HTML simples)</label>
            <textarea class="form-control" name="content" rows="14" placeholder="Usa <h2>, <h3>, <p>, <ul>, <strong> e links internos."><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
            <div class="form-text">Dica SEO: usa H2 para secções principais, H3 para detalhes, e liga para serviços, eventos e contactos.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Imagem de capa (URL)</label>
            <input class="form-control" name="hero_image_url" value="<?= htmlspecialchars($post['hero_image_url'] ?? '') ?>" placeholder="https://...">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_published" id="is_published" <?= (!$post || (int)($post['is_published'] ?? 0) === 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_published">Publicado</label>
            </div>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="show_corporate_form" id="show_corporate_form" <?= (int)($post['show_corporate_form'] ?? 0) === 1 ? 'checked' : '' ?>>
                <label class="form-check-label" for="show_corporate_form">Mostrar formulário de eventos corporativos no final</label>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Meta title</label>
            <input class="form-control" name="meta_title" maxlength="70" value="<?= htmlspecialchars($post['meta_title'] ?? '') ?>" placeholder="Opcional; se vazio usa o título do post">
        </div>
        <div class="col-md-6">
            <label class="form-label">Meta description</label>
            <input class="form-control" name="meta_description" maxlength="170" value="<?= htmlspecialchars($post['meta_description'] ?? '') ?>" placeholder="Opcional; se vazio usa o resumo">
        </div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button class="btn btn-dark">Guardar post</button>
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>?controller=blogpost&action=index">Cancelar</a>
    </div>
</form>
