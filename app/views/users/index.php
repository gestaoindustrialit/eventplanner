<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h2 class="mb-1">Perfis e permissões</h2><p class="text-muted mb-0">Crie acessos e escolha exatamente os módulos que cada pessoa pode ver e gerir.</p></div>
    <a class="btn btn-primary" href="<?= BASE_URL ?>?controller=user&action=create"><i class="bi bi-person-plus"></i> Novo perfil</a>
</div>
<div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Nome</th><th>Tipo</th><th>Email</th><th>Permissões</th><th></th></tr></thead><tbody>
<?php foreach ($users as $item): $permissions=json_decode($item['permissions_json']??'[]',true)?:[]; ?>
<tr><td><strong><?= htmlspecialchars($item['name']) ?></strong></td><td><span class="badge bg-secondary"><?= htmlspecialchars($item['role']==='admin'?'Administrador':ucfirst($item['profile_type']??'comedian')) ?></span></td><td><?= htmlspecialchars($item['email']) ?></td><td><?php if($item['role']==='admin'): ?>Acesso total<?php else: ?><?= count($permissions) ?> módulo(s)<?php endif; ?></td><td class="text-end"><?php if($item['role']!=='admin'): ?><a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>?controller=user&action=edit&id=<?= (int)$item['id'] ?>">Editar</a> <a class="btn btn-sm btn-outline-danger" onclick="return confirm('Eliminar este perfil?')" href="<?= BASE_URL ?>?controller=user&action=delete&id=<?= (int)$item['id'] ?>">Eliminar</a><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>
