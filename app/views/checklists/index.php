<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Templates de Checklist</h2>
    <a class="btn btn-primary" href="<?= BASE_URL ?>?controller=checklist&action=create">Novo template</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped searchable-table">
                <thead>
                <tr>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th class="text-end">Ações</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($templates as $template): ?>
                    <tr>
                        <td><?= htmlspecialchars($template['name']) ?></td>
                        <td><?= htmlspecialchars($template['description'] ?? '-') ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>?controller=checklist&action=edit&id=<?= (int)$template['id'] ?>">Editar</a>
                            <a class="btn btn-sm btn-outline-danger delete-btn" href="<?= BASE_URL ?>?controller=checklist&action=delete&id=<?= (int)$template['id'] ?>">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
