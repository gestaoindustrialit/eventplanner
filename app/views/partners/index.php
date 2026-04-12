<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Parceiros</h2>
        <p class="text-muted mb-0">Gestão de logótipos e links para o carrossel público. Cada parceria expira após 1 ano da data de início.</p>
    </div>
    <a class="btn btn-dark" href="<?= BASE_URL ?>?controller=partner&action=create">Novo Parceiro</a>
</div>

<div class="table-responsive">
    <table class="table table-hover">
        <thead>
        <tr>
            <th>ID</th>
            <th>Empresa</th>
            <th>Início</th>
            <th>Expira em</th>
            <th>Estado</th>
            <th>Link</th>
            <th>Ordem</th>
            <th>Ações</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($partners as $partner): ?>
            <?php
                $startDate = $partner['partnership_start_date'] ?? '';
                $expiresAt = $startDate !== '' ? date('Y-m-d', strtotime($startDate . ' +1 year')) : '';
                $isActive = $startDate !== '' && strtotime($expiresAt) > strtotime(date('Y-m-d'));
            ?>
            <tr>
                <td><?= (int)$partner['id'] ?></td>
                <td>
                    <div class="fw-semibold"><?= htmlspecialchars($partner['company_name']) ?></div>
                    <?php if (!empty($partner['logo_url'])): ?>
                        <img src="<?= htmlspecialchars($partner['logo_url']) ?>" alt="<?= htmlspecialchars($partner['company_name']) ?>" style="height: 34px; width: auto;">
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($startDate) ?></td>
                <td><?= htmlspecialchars($expiresAt) ?></td>
                <td>
                    <span class="badge <?= $isActive ? 'bg-success' : 'bg-secondary' ?>">
                        <?= $isActive ? 'Ativo no site' : 'Oculto (expirado)' ?>
                    </span>
                </td>
                <td>
                    <?php if (!empty($partner['company_url'])): ?>
                        <a href="<?= htmlspecialchars($partner['company_url']) ?>" target="_blank" rel="noopener noreferrer">Visitar</a>
                    <?php endif; ?>
                </td>
                <td><?= (int)$partner['sort_order'] ?></td>
                <td>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>?controller=partner&action=edit&id=<?= (int)$partner['id'] ?>">Editar</a>
                    <a class="btn btn-sm btn-outline-danger delete-btn" href="<?= BASE_URL ?>?controller=partner&action=delete&id=<?= (int)$partner['id'] ?>">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (count($partners) === 0): ?>
            <tr><td colspan="8" class="text-muted">Ainda não existem parceiros.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
