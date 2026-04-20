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
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-lg-5">
                <label class="form-label small text-muted mb-1" for="press-contact-search">Pesquisa rápida</label>
                <input
                    id="press-contact-search"
                    type="text"
                    class="form-control table-search"
                    placeholder="Nome, email, localidade ou distrito..."
                >
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="form-label small text-muted mb-1" for="press-contact-district">Distrito</label>
                <select id="press-contact-district" class="form-select press-filter-district">
                    <option value="">Todos</option>
                    <?php foreach (array_values(array_unique(array_filter(array_map(static fn ($item) => trim((string)$item['district']), $contacts)))) as $district): ?>
                        <option value="<?= htmlspecialchars($district) ?>"><?= htmlspecialchars($district) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="form-label small text-muted mb-1" for="press-contact-locality">Localidade</label>
                <select id="press-contact-locality" class="form-select press-filter-locality">
                    <option value="">Todas</option>
                    <?php foreach (array_values(array_unique(array_filter(array_map(static fn ($item) => trim((string)$item['locality']), $contacts)))) as $locality): ?>
                        <option value="<?= htmlspecialchars($locality) ?>"><?= htmlspecialchars($locality) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="form-label small text-muted mb-1" for="press-contact-page-size">Por página</label>
                <select id="press-contact-page-size" class="form-select press-page-size">
                    <option value="10">10</option>
                    <option value="20" selected>20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <div class="col-sm-6 col-lg-1 d-grid">
                <button type="button" class="btn btn-outline-secondary press-filters-reset" title="Limpar filtros">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            </div>
        </div>
    </div>
</div>
<div class="table-responsive">
    <table class="table table-hover searchable-table press-contacts-table">
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
                    <div class="d-flex gap-2">
                        <a
                            class="btn btn-sm btn-outline-secondary action-icon-btn"
                            href="<?= BASE_URL ?>?controller=presscontact&action=edit&id=<?= $contact['id'] ?>"
                            title="Editar contacto"
                            aria-label="Editar contacto"
                        >
                            <i class="bi bi-pencil-square"></i>
                        </a>
                        <a
                            class="btn btn-sm btn-outline-danger delete-btn action-icon-btn"
                            href="<?= BASE_URL ?>?controller=presscontact&action=delete&id=<?= $contact['id'] ?>"
                            title="Eliminar contacto"
                            aria-label="Eliminar contacto"
                        >
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if (empty($contacts)): ?>
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body text-center py-5">
            <i class="bi bi-newspaper fs-1 text-danger"></i>
            <h5 class="mt-3 mb-2">Sem contactos de imprensa</h5>
            <p class="text-muted mb-3">Adiciona contactos manualmente ou importa um CSV para começar a difusão.</p>
            <a class="btn btn-primary" href="<?= BASE_URL ?>?controller=presscontact&action=create">
                <i class="bi bi-plus-circle"></i> Adicionar contacto
            </a>
        </div>
    </div>
<?php endif; ?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3 press-pagination">
    <small class="text-muted press-pagination-info"></small>
    <nav aria-label="Paginação contactos imprensa">
        <ul class="pagination pagination-sm mb-0 press-pagination-controls"></ul>
    </nav>
</div>
