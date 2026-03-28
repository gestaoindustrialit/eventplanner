<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Clientes</h2>
    <a class="btn btn-dark" href="<?= BASE_URL ?>?controller=client&action=create">Novo Cliente</a>
</div>
<input type="text" class="form-control mb-3 table-search" placeholder="Pesquisar cliente...">
<div class="table-responsive">
    <table class="table table-hover searchable-table">
        <thead><tr><th>Nome</th><th>Contacto</th><th>Email</th><th>Phone</th><th>Morada</th><th>Ações</th></tr></thead>
        <tbody>
        <?php foreach ($clients as $client): ?>
            <tr>
                <td><?= htmlspecialchars($client['name']) ?></td>
                <td><?= htmlspecialchars($client['contact_person']) ?></td>
                <td><?= htmlspecialchars($client['email']) ?></td>
                <td><?= htmlspecialchars($client['phone']) ?></td>
                <td><?= htmlspecialchars($client['address']) ?></td>
                <td>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>?controller=client&action=edit&id=<?= $client['id'] ?>">Editar</a>
                    <a class="btn btn-sm btn-outline-danger delete-btn" href="<?= BASE_URL ?>?controller=client&action=delete&id=<?= $client['id'] ?>">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
