<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Páginas públicas</h2>
    <a class="btn btn-dark" href="<?= BASE_URL ?>?controller=publicpage&action=create">Nova página</a>
</div>

<p class="text-muted">Cria e gere conteúdos do website público (menu, secções institucionais, landing pages). As páginas criadas aparecem no menu do site externo depois de voltares a publicar.</p>

<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Título</th>
                <th>Slug</th>
                <th>Modo</th>
                <th>Tipo/Design</th>
                <th>Ordem</th>
                <th>Estado</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pages as $page): ?>
                <tr>
                    <td><?= htmlspecialchars($page['title']) ?></td>
                    <td><code><?= htmlspecialchars($page['slug']) ?></code></td>
                    <td>
                        <?php if (($page['display_mode'] ?? 'section') === 'page'): ?>
                            <span class="badge text-bg-info">Página</span>
                        <?php else: ?>
                            <span class="badge text-bg-primary">Setor home</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge text-bg-light border"><?= htmlspecialchars((string)($page['section_type'] ?? 'default')) ?></span>
                        <span class="badge text-bg-light border"><?= htmlspecialchars((string)($page['section_style'] ?? 'card')) ?></span>
                    </td>
                    <td><?= (int)$page['sort_order'] ?></td>
                    <td>
                        <?php if ((int)$page['is_published'] === 1): ?>
                            <span class="badge bg-success">Publicado</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Rascunho</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>?controller=publicpage&action=edit&id=<?= (int)$page['id'] ?>">Editar</a>
                        <a class="btn btn-sm btn-outline-danger delete-btn" href="<?= BASE_URL ?>?controller=publicpage&action=delete&id=<?= (int)$page['id'] ?>">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (count($pages) === 0): ?>
                <tr>
                    <td colspan="7" class="text-muted">Ainda não existem páginas públicas.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
