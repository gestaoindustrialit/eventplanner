<div class="page-heading d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><span class="text-uppercase small text-danger fw-bold">Programação recorrente</span><h1 class="h2 mb-1">Séries de eventos</h1><p class="text-muted mb-0">Um endereço permanente, várias sessões independentes.</p></div>
    <a class="btn btn-dark" href="<?= BASE_URL ?>?controller=eventseries&action=create"><i class="bi bi-plus-lg"></i> Nova série</a>
</div>
<div class="responsive-card-grid">
<?php foreach ($series as $item): ?>
    <article class="card h-100 overflow-hidden">
        <?php if ($item['cover_image_url']): ?><img src="<?= htmlspecialchars($item['cover_image_url']) ?>" class="series-cover" alt="Capa de <?= htmlspecialchars($item['name']) ?>"><?php endif; ?>
        <div class="card-body">
            <div class="d-flex justify-content-between gap-2"><h2 class="h5"><?= htmlspecialchars($item['name']) ?></h2><span class="badge <?= (int)$item['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int)$item['is_active'] === 1 ? 'Ativa' : 'Inativa' ?></span></div>
            <p class="small text-muted mb-2">/eventos/<?= htmlspecialchars($item['slug']) ?></p>
            <p class="mb-3"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($item['location'] ?: 'Local definido em cada sessão') ?></p>
            <div class="d-flex align-items-center justify-content-between"><strong><?= (int)$item['event_count'] ?> sessões</strong><a class="btn btn-sm btn-outline-dark" href="<?= BASE_URL ?>?controller=eventseries&action=edit&id=<?= (int)$item['id'] ?>">Gerir</a></div>
        </div>
    </article>
<?php endforeach; ?>
<?php if (!$series): ?><div class="card"><div class="card-body"><h2 class="h5">Ainda não existem séries</h2><p class="text-muted mb-0">Cria a primeira série para agrupar sessões sem alterar as reservas.</p></div></div><?php endif; ?>
</div>
