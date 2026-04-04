<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Comediantes</h2>
    <a class="btn btn-dark" href="<?= BASE_URL ?>?controller=comedian&action=create">Novo Comediante</a>
</div>
<input type="text" class="form-control mb-3 table-search" placeholder="Pesquisar comediante...">
<div class="table-responsive">
    <table class="table table-hover searchable-table">
        <thead><tr><th>Nome artístico</th><th>Email</th><th>Localidade</th><th>Valor Bar</th><th>Valor Auditório</th><th>User Login</th><th>Ações</th></tr></thead>
        <tbody>
        <?php foreach ($comedians as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['stage_name']) ?></td>
                <td><?= htmlspecialchars($c['email']) ?></td>
                <td><?= htmlspecialchars($c['city'] ?? '-') ?></td>
                <td>€<?= number_format((float)($c['price_bar'] ?? 0), 2, ',', '.') ?></td>
                <td>€<?= number_format((float)($c['price_auditorium'] ?? 0), 2, ',', '.') ?></td>
                <td><?= htmlspecialchars($c['user_name'] ?? '-') ?></td>
                <td>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>?controller=comedian&action=edit&id=<?= $c['id'] ?>" title="Editar" aria-label="Editar">
                        <i class="bi bi-pencil-square"></i>
                    </a>
                    <a class="btn btn-sm btn-outline-danger delete-btn" href="<?= BASE_URL ?>?controller=comedian&action=delete&id=<?= $c['id'] ?>" title="Eliminar" aria-label="Eliminar">
                        <i class="bi bi-trash"></i>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
