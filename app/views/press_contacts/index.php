<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Contactos Press</h2>
        <p class="text-muted mb-0">Gestão da base de dados de imprensa para difusão de eventos por distrito ou nacional.</p>
    </div>
    <a class="btn btn-dark" href="<?= BASE_URL ?>?controller=presscontact&action=create">Novo Contacto</a>
</div>
<div class="mb-3">
    <a class="btn btn-outline-dark" href="<?= BASE_URL ?>?controller=presscontact&action=outreach">
        <i class="bi bi-megaphone"></i> Difusão rápida por localidade/distrito
    </a>
    <a class="btn btn-outline-primary" href="<?= BASE_URL ?>?controller=presscontact&action=downloadtemplate">
        <i class="bi bi-download"></i> Download template importação
    </a>
</div>
<form class="row g-2 mb-3" method="post" action="<?= BASE_URL ?>?controller=presscontact&action=uploadtemplate" enctype="multipart/form-data">
    <div class="col-md-5">
        <input type="file" class="form-control" name="contacts_csv" accept=".csv,text/csv" required>
    </div>
    <div class="col-md-auto">
        <button type="submit" class="btn btn-outline-success">
            <i class="bi bi-upload"></i> Upload CSV
        </button>
    </div>
</form>
<input type="text" class="form-control mb-3 table-search" placeholder="Pesquisar por nome, email, localidade ou distrito...">
<div class="table-responsive">
    <table class="table table-hover searchable-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Localidade</th>
                <th>Distrito</th>
                <th>Website</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($contacts as $contact): ?>
            <tr>
                <td><?= (int)$contact['id'] ?></td>
                <td><?= htmlspecialchars($contact['name']) ?></td>
                <td>
                    <?php if (!empty($contact['email'])): ?>
                        <a href="mailto:<?= htmlspecialchars($contact['email']) ?>"><?= htmlspecialchars($contact['email']) ?></a>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($contact['locality']) ?></td>
                <td><?= htmlspecialchars($contact['district']) ?></td>
                <td>
                    <?php if (!empty($contact['website'])): ?>
                        <a href="<?= htmlspecialchars($contact['website']) ?>" target="_blank" rel="noopener noreferrer">Visitar</a>
                    <?php endif; ?>
                </td>
                <td>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>?controller=presscontact&action=edit&id=<?= $contact['id'] ?>">Editar</a>
                    <a class="btn btn-sm btn-outline-danger delete-btn" href="<?= BASE_URL ?>?controller=presscontact&action=delete&id=<?= $contact['id'] ?>">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
