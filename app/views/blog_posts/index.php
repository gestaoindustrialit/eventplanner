<div class="d-flex justify-content-between align-items-start gap-3 mb-3">
    <div>
        <h2 class="mb-1">Posts do blog</h2>
        <p class="text-muted mb-0">Cria artigos SEO para o website público. Depois de publicar posts, volta a publicar o website para atualizar o blog externo.</p>
    </div>
    <a class="btn btn-dark" href="<?= BASE_URL ?>?controller=blogpost&action=create"><i class="bi bi-plus-lg"></i> Novo post</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100"><div class="card-body"><div class="small text-muted">Estrutura</div><strong>Title, description, slug, categoria e artigo completo</strong></div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100"><div class="card-body"><div class="small text-muted">Design público</div><strong>Cards escuros, hero, CTA e JSON-LD Article</strong></div></div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100"><div class="card-body"><div class="small text-muted">SEO</div><strong>Sitemap automático e URLs /blog/slug</strong></div></div>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead><tr><th>Título</th><th>Categoria</th><th>Slug</th><th>Data</th><th>Estado</th><th class="text-end">Ações</th></tr></thead>
        <tbody>
            <?php foreach ($posts as $post): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($post['title']) ?></strong><div class="small text-muted"><?= htmlspecialchars((string)($post['excerpt'] ?? '')) ?></div></td>
                    <td><?= htmlspecialchars((string)($post['category'] ?? '')) ?></td>
                    <td><code><?= htmlspecialchars($post['slug']) ?></code></td>
                    <td><?= htmlspecialchars((string)($post['published_at'] ?? '')) ?></td>
                    <td><?= (int)$post['is_published'] === 1 ? '<span class="badge bg-success">Publicado</span>' : '<span class="badge bg-secondary">Rascunho</span>' ?></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>?controller=blogpost&action=edit&id=<?= (int)$post['id'] ?>">Editar</a> <a class="btn btn-sm btn-outline-danger delete-btn" href="<?= BASE_URL ?>?controller=blogpost&action=delete&id=<?= (int)$post['id'] ?>">Eliminar</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($posts) === 0): ?><tr><td colspan="6" class="text-muted">Ainda não existem posts.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
