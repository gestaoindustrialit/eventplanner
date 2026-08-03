<?php $selected=json_decode($profile['permissions_json']??'[]',true)?:[]; ?>
<h2 class="mb-3"><?= $profile?'Editar':'Novo' ?> perfil</h2>
<form method="post" action="<?= BASE_URL ?>?controller=user&action=<?= $profile?'update&id='.(int)$profile['id']:'store' ?>">
<div class="card mb-3"><div class="card-body"><div class="row g-3">
<div class="col-md-6"><label class="form-label">Nome</label><input class="form-control" name="name" required value="<?= htmlspecialchars($profile['name']??'') ?>"></div>
<div class="col-md-6"><label class="form-label">Tipo de perfil</label><select class="form-select" name="profile_type"><option value="comedian" <?= ($profile['profile_type']??'')==='comedian'?'selected':'' ?>>Comediante</option><option value="partner" <?= ($profile['profile_type']??'')==='partner'?'selected':'' ?>>Parceiro</option><option value="editor" <?= ($profile['profile_type']??'editor')==='editor'?'selected':'' ?>>Editor</option></select></div>
<div class="col-md-6"><label class="form-label">Email de acesso</label><input class="form-control" type="email" name="email" required value="<?= htmlspecialchars($profile['email']??'') ?>"></div>
<div class="col-md-6"><label class="form-label">Palavra-passe <?= $profile?'(deixe vazio para manter)':'' ?></label><input class="form-control" type="password" name="password" <?= $profile?'':'required' ?> autocomplete="new-password"></div>
</div></div></div>
<div class="card"><div class="card-header"><strong>O que este perfil pode ver e gerir</strong></div><div class="card-body"><div class="row">
<?php foreach(availablePermissions() as $key=>$label): ?><div class="col-md-4 mb-2"><div class="form-check"><input class="form-check-input" type="checkbox" name="permissions[]" value="<?= $key ?>" id="perm-<?= $key ?>" <?= in_array($key,$selected,true)?'checked':'' ?>><label class="form-check-label" for="perm-<?= $key ?>"><?= htmlspecialchars($label) ?></label></div></div><?php endforeach; ?>
</div></div></div><button class="btn btn-dark mt-3">Guardar perfil</button> <a class="btn btn-link mt-3" href="<?= BASE_URL ?>?controller=user">Cancelar</a></form>
