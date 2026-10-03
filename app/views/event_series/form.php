<h2><?= $seriesItem?'Editar':'Nova' ?> série</h2>
<form method="post" action="<?= BASE_URL ?>?controller=eventseries&action=<?= $seriesItem?'update&id='.(int)$seriesItem['id']:'store' ?>" class="row g-3">
 <div class="col-md-6"><label class="form-label">Nome</label><input required class="form-control" name="name" value="<?= htmlspecialchars($seriesItem['name']??'') ?>"></div>
 <div class="col-md-6"><label class="form-label">Slug</label><div class="input-group"><span class="input-group-text">/eventos/</span><input required pattern="[a-z0-9-]+" class="form-control" name="slug" value="<?= htmlspecialchars($seriesItem['slug']??'') ?>"></div></div>
 <div class="col-md-6"><label class="form-label">Local predefinido</label><input class="form-control" name="location" value="<?= htmlspecialchars($seriesItem['location']??'') ?>"></div>
 <div class="col-md-6"><label class="form-label">Imagem/capa (URL)</label><input type="url" class="form-control" name="cover_image_url" value="<?= htmlspecialchars($seriesItem['cover_image_url']??'') ?>"></div>
 <div class="col-12"><label class="form-label">Descrição</label><textarea class="form-control" rows="4" name="description"><?= htmlspecialchars($seriesItem['description']??'') ?></textarea></div>
 <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" id="seriesActive" <?= !isset($seriesItem['is_active'])||(int)$seriesItem['is_active']===1?'checked':'' ?>><label class="form-check-label" for="seriesActive">Série ativa</label></div></div>
 <div class="col-12"><button class="btn btn-dark">Guardar</button></div>
</form>
<?php if ($seriesItem): $today=date('Y-m-d'); ?>
<hr class="my-4"><h3 class="h5">Próximas sessões</h3><?php foreach($events as $event): if($event['date']<$today)continue; ?><p><a href="<?= BASE_URL ?>?controller=event&action=edit&id=<?= (int)$event['id'] ?>"><?= htmlspecialchars($event['date'].' — '.substr($event['time'],0,5).' — '.$event['title']) ?></a></p><?php endforeach; ?>
<h3 class="h5 mt-4">Eventos passados</h3><?php foreach($events as $event): if($event['date']>=$today)continue; ?><p><a href="<?= BASE_URL ?>?controller=event&action=edit&id=<?= (int)$event['id'] ?>"><?= htmlspecialchars($event['date'].' — '.substr($event['time'],0,5).' — '.$event['title']) ?></a></p><?php endforeach; endif; ?>
