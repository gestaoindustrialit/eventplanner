<?php $editing = $seriesItem !== null; ?>
<div class="page-heading d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><a class="small text-decoration-none" href="<?= BASE_URL ?>?controller=eventseries">← Séries</a><h1 class="h2 mt-2 mb-1"><?= $editing ? 'Editar série' : 'Nova série' ?></h1><p class="text-muted mb-0">Os dados das datas, preços e lotações continuam em cada evento.</p></div>
    <?php if ($editing): ?><a class="btn btn-outline-dark" href="https://chorarderir.com/eventos/<?= rawurlencode($seriesItem['slug']) ?>" target="_blank" rel="noopener">Abrir página pública</a><?php endif; ?>
</div>
<form method="post" class="card mb-4" action="<?= BASE_URL ?>?controller=eventseries&action=<?= $editing ? 'update&id=' . (int)$seriesItem['id'] : 'store' ?>">
  <div class="card-body p-3 p-md-4"><div class="row g-3">
    <div class="col-md-7"><label class="form-label">Nome</label><input required class="form-control" name="name" value="<?= htmlspecialchars($seriesItem['name'] ?? '') ?>"></div>
    <div class="col-md-5"><label class="form-label">Slug</label><div class="input-group"><span class="input-group-text">/eventos/</span><input required pattern="[a-z0-9-]+" class="form-control" name="slug" value="<?= htmlspecialchars($seriesItem['slug'] ?? '') ?>"></div></div>
    <div class="col-md-6"><label class="form-label">Local habitual</label><input class="form-control" name="location" value="<?= htmlspecialchars($seriesItem['location'] ?? '') ?>" placeholder="Ex.: Lustre Bar — Braga"></div>
    <div class="col-md-6"><label class="form-label">Imagem de capa (URL)</label><input type="url" class="form-control" name="cover_image_url" value="<?= htmlspecialchars($seriesItem['cover_image_url'] ?? '') ?>"></div>
    <div class="col-12"><label class="form-label">Descrição</label><textarea rows="4" class="form-control" name="description"><?= htmlspecialchars($seriesItem['description'] ?? '') ?></textarea></div>
    <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= !$editing || (int)$seriesItem['is_active'] === 1 ? 'checked' : '' ?>><label class="form-check-label" for="is_active">Série ativa e disponível publicamente</label></div></div>
  </div><button class="btn btn-dark mt-4">Guardar série</button></div>
</form>
<?php if ($editing): ?>
<section class="card"><div class="card-body p-3 p-md-4"><h2 class="h4 mb-3">Sessões associadas</h2>
<?php $today = date('Y-m-d'); foreach (['Próximas sessões' => true, 'Eventos passados' => false] as $heading => $future): ?>
  <h3 class="h6 text-uppercase text-muted mt-4"><?= $heading ?></h3><div class="session-list">
  <?php $count = 0; foreach ($sessions as $session): if (($session['date'] >= $today) !== $future) continue; $count++; ?>
    <a class="session-row" href="<?= BASE_URL ?>?controller=event&action=edit&id=<?= (int)$session['id'] ?>"><span><strong><?= htmlspecialchars(formatPtPtShortDate((string)$session['date'])) ?></strong> — <?= htmlspecialchars(substr($session['time'], 0, 5)) ?></span><span class="badge <?= (int)$session['is_visible'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int)$session['is_visible'] === 1 ? 'Publicada' : 'Oculta' ?></span></a>
  <?php endforeach; if ($count === 0): ?><p class="small text-muted">Sem sessões.</p><?php endif; ?></div>
<?php endforeach; ?></div></section>
<?php endif; ?>
