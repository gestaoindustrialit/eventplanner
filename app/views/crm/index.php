<?php
$statusLabels = [
    'novo' => 'Novo',
    'por_contactar' => 'Por contactar',
    'contactado' => 'Contactado',
    'aguardar_resposta' => 'A aguardar resposta',
    'em_negociacao' => 'Em negociação',
    'proposta_enviada' => 'Proposta enviada',
    'interessado' => 'Interessado',
    'fechado_ganho' => 'Fechado ganho',
    'fechado_perdido' => 'Fechado perdido',
    'sem_interesse' => 'Sem interesse',
    'arquivado' => 'Arquivado',
];

$typeLabels = [
    'venda' => 'Venda',
    'apoio' => 'Apoio',
    'parceria' => 'Parceria',
    'patrocinador' => 'Patrocinador',
    'media' => 'Media',
    'fornecedor' => 'Fornecedor',
    'outro' => 'Outro',
];

$priorityLabels = [
    'baixa' => 'Baixa',
    'media' => 'Média',
    'alta' => 'Alta',
    'urgente' => 'Urgente',
];

$sortOptions = [
    'entity_name' => 'Nome',
    'last_contact_at' => 'Último contacto',
    'next_follow_up_at' => 'Próxima ação',
    'potential_value' => 'Valor potencial',
    'status' => 'Estado',
];

$queryWithoutPage = $_GET;
unset($queryWithoutPage['page']);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h2 class="mb-1">CRM</h2>
        <p class="text-muted mb-0">Gestão de contactos, oportunidades e follow-ups do projeto.</p>
    </div>
    <a class="btn btn-dark" href="<?= BASE_URL ?>?controller=crm&action=create">Novo contacto</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6 col-xl-2">
        <div class="card h-100 crm-metric-card">
            <div class="card-body">
                <small class="text-muted">Total contactos</small>
                <h3 class="mb-0"><?= $metrics['total'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-2">
        <div class="card h-100 crm-metric-card">
            <div class="card-body">
                <small class="text-muted">Oportunidades em aberto</small>
                <h3 class="mb-0"><?= $metrics['open'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-2">
        <div class="card h-100 crm-metric-card">
            <div class="card-body">
                <small class="text-muted">Fechados ganhos</small>
                <h3 class="mb-0"><?= $metrics['won'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card h-100 crm-metric-card">
            <div class="card-body">
                <small class="text-muted d-block mb-2">Nacional vs Internacional</small>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="badge text-bg-light">Nacional: <?= $metrics['markets']['nacional'] ?? 0 ?></span>
                    <span class="badge text-bg-light">Internacional: <?= $metrics['markets']['internacional'] ?? 0 ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-3">
        <div class="card h-100 crm-metric-card">
            <div class="card-body">
                <small class="text-muted d-block mb-2">Por tipo</small>
                <div class="d-flex gap-2 flex-wrap">
                    <?php foreach ($types as $type): ?>
                        <span class="badge text-bg-light"><?= $typeLabels[$type] ?>: <?= $metrics['types'][$type] ?? 0 ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<form class="card mb-4" method="get" action="<?= BASE_URL ?>">
    <div class="card-body">
        <input type="hidden" name="controller" value="crm">
        <input type="hidden" name="action" value="index">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-lg-3">
                <label class="form-label">Pesquisa rápida</label>
                <input class="form-control" name="search" value="<?= htmlspecialchars($filters['search']) ?>" placeholder="Nome, email, cidade, notas...">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Estado</label>
                <select class="form-select" name="status">
                    <option value="">Todos</option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?= $status ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= $statusLabels[$status] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Tipo</label>
                <select class="form-select" name="contact_type">
                    <option value="">Todos</option>
                    <?php foreach ($types as $type): ?>
                        <option value="<?= $type ?>" <?= $filters['contact_type'] === $type ? 'selected' : '' ?>><?= $typeLabels[$type] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Mercado</label>
                <select class="form-select" name="market">
                    <option value="">Todos</option>
                    <?php foreach ($markets as $market): ?>
                        <option value="<?= $market ?>" <?= $filters['market'] === $market ? 'selected' : '' ?>><?= ucfirst($market) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-lg-1">
                <label class="form-label">Prioridade</label>
                <select class="form-select" name="priority">
                    <option value="">Todas</option>
                    <?php foreach ($priorities as $priority): ?>
                        <option value="<?= $priority ?>" <?= $filters['priority'] === $priority ? 'selected' : '' ?>><?= $priorityLabels[$priority] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">País</label>
                <input class="form-control" name="country" value="<?= htmlspecialchars($filters['country']) ?>">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Último contacto (de)</label>
                <input type="date" class="form-control" name="last_contact_from" value="<?= htmlspecialchars($filters['last_contact_from']) ?>">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Último contacto (até)</label>
                <input type="date" class="form-control" name="last_contact_to" value="<?= htmlspecialchars($filters['last_contact_to']) ?>">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Ordenar por</label>
                <select class="form-select" name="sort_by">
                    <?php foreach ($sortOptions as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $sortBy === $key ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label">Direção</label>
                <select class="form-select" name="sort_dir">
                    <option value="DESC" <?= $sortDir === 'DESC' ? 'selected' : '' ?>>Desc</option>
                    <option value="ASC" <?= $sortDir === 'ASC' ? 'selected' : '' ?>>Asc</option>
                </select>
            </div>
            <div class="col-12 col-lg-2 d-flex gap-2">
                <button class="btn btn-dark flex-grow-1">Aplicar</button>
                <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>?controller=crm&action=index">Limpar</a>
            </div>
        </div>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Entidade</th>
                <th>Contacto</th>
                <th>Estado</th>
                <th>Tipo</th>
                <th>Mercado</th>
                <th>Prioridade</th>
                <th>Último contacto</th>
                <th>Próxima ação</th>
                <th>Valor potencial</th>
                <th class="text-end">Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$contacts): ?>
            <tr><td colspan="10" class="text-center text-muted py-4">Sem contactos para os filtros aplicados.</td></tr>
        <?php endif; ?>
        <?php foreach ($contacts as $contact): ?>
            <tr>
                <td>
                    <a class="fw-semibold text-decoration-none" href="<?= BASE_URL ?>?controller=crm&action=show&id=<?= $contact['id'] ?>"><?= htmlspecialchars($contact['entity_name']) ?></a>
                    <div class="small text-muted"><?= htmlspecialchars($contact['country'] ?: '-') ?><?= $contact['city'] ? ' · ' . htmlspecialchars($contact['city']) : '' ?></div>
                </td>
                <td>
                    <div><?= htmlspecialchars($contact['contact_name'] ?: '-') ?></div>
                    <div class="small text-muted"><?= htmlspecialchars($contact['email'] ?: '-') ?></div>
                </td>
                <td><span class="badge crm-status crm-status-<?= $contact['status'] ?>"><?= $statusLabels[$contact['status']] ?? $contact['status'] ?></span></td>
                <td><span class="badge crm-type crm-type-<?= $contact['contact_type'] ?>"><?= $typeLabels[$contact['contact_type']] ?? $contact['contact_type'] ?></span></td>
                <td><?= ucfirst(htmlspecialchars($contact['market'])) ?></td>
                <td><span class="badge text-bg-light"><?= $priorityLabels[$contact['priority']] ?? ucfirst($contact['priority']) ?></span></td>
                <td><?= htmlspecialchars($contact['last_contact_at'] ?: '-') ?></td>
                <td><?= htmlspecialchars($contact['next_follow_up_at'] ?: '-') ?></td>
                <td><?= $contact['potential_value'] !== null ? '€' . number_format((float)$contact['potential_value'], 2, ',', '.') : '-' ?></td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm" role="group">
                        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>?controller=crm&action=edit&id=<?= $contact['id'] ?>">Editar</a>
                        <a class="btn btn-outline-primary" href="<?= BASE_URL ?>?controller=crm&action=quickAction&id=<?= $contact['id'] ?>&type=mark_contacted">Contactado</a>
                        <a class="btn btn-outline-dark" href="<?= BASE_URL ?>?controller=crm&action=quickAction&id=<?= $contact['id'] ?>&type=duplicate">Duplicar</a>
                        <a class="btn btn-outline-warning" href="<?= BASE_URL ?>?controller=crm&action=archive&id=<?= $contact['id'] ?>">Arquivar</a>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="10" class="bg-light-subtle">
                    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                        <p class="mb-0 small text-muted flex-grow-1"><strong>Observações:</strong> <?= nl2br(htmlspecialchars($contact['observations'] ?: 'Sem observações.')) ?></p>
                        <form class="d-flex gap-2 align-items-center" method="post" action="<?= BASE_URL ?>?controller=crm&action=quickAction&id=<?= $contact['id'] ?>&type=change_status">
                            <select name="status" class="form-select form-select-sm">
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?= $status ?>" <?= $contact['status'] === $status ? 'selected' : '' ?>><?= $statusLabels[$status] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-sm btn-outline-primary">Alterar estado</button>
                        </form>
                        <form class="d-flex gap-2 align-items-center" method="post" action="<?= BASE_URL ?>?controller=crm&action=quickAction&id=<?= $contact['id'] ?>&type=schedule_follow_up">
                            <input type="date" name="next_follow_up_at" class="form-control form-control-sm" value="<?= htmlspecialchars($contact['next_follow_up_at'] ?? '') ?>">
                            <button class="btn btn-sm btn-outline-secondary">Agendar follow-up</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
    <small class="text-muted">A mostrar <?= count($contacts) ?> de <?= $total ?> registos.</small>
    <nav>
        <ul class="pagination pagination-sm mb-0">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php $query = http_build_query(array_merge($queryWithoutPage, ['page' => $i])); ?>
                <li class="page-item <?= $page === $i ? 'active' : '' ?>">
                    <a class="page-link" href="<?= BASE_URL . '?' . $query ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>
