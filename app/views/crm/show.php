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
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h2 class="mb-1"><?= htmlspecialchars($contact['entity_name']) ?></h2>
        <p class="mb-0 text-muted"><?= htmlspecialchars($contact['contact_name'] ?: 'Sem pessoa de contacto definida') ?></p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>?controller=crm&action=index">Voltar</a>
        <a class="btn btn-dark" href="<?= BASE_URL ?>?controller=crm&action=edit&id=<?= $contact['id'] ?>">Editar</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-8">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="mb-3">Resumo</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="small text-muted">Estado</div>
                        <span class="badge crm-status crm-status-<?= $contact['status'] ?>"><?= $statusLabels[$contact['status']] ?? $contact['status'] ?></span>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Tipo</div>
                        <span class="badge crm-type crm-type-<?= $contact['contact_type'] ?>"><?= $typeLabels[$contact['contact_type']] ?? $contact['contact_type'] ?></span>
                    </div>
                    <div class="col-md-4"><div class="small text-muted">Mercado</div><div><?= ucfirst(htmlspecialchars($contact['market'])) ?></div></div>
                    <div class="col-md-4"><div class="small text-muted">Prioridade</div><div><?= ucfirst(htmlspecialchars($contact['priority'])) ?></div></div>
                    <div class="col-md-4"><div class="small text-muted">Valor potencial</div><div><?= $contact['potential_value'] !== null ? '€' . number_format((float)$contact['potential_value'], 2, ',', '.') : '-' ?></div></div>
                    <div class="col-md-6"><div class="small text-muted">Último contacto</div><div><?= htmlspecialchars($contact['last_contact_at'] ?: '-') ?></div></div>
                    <div class="col-md-6"><div class="small text-muted">Próxima ação</div><div><?= htmlspecialchars($contact['next_follow_up_at'] ?: '-') ?></div></div>
                </div>
                <hr>
                <h6>Observações</h6>
                <p class="crm-notes"><?= nl2br(htmlspecialchars($contact['observations'] ?: 'Sem observações.')) ?></p>
                <h6>Notas internas</h6>
                <p class="crm-notes"><?= nl2br(htmlspecialchars($contact['internal_notes'] ?: 'Sem notas internas.')) ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="mb-3">Contactos</h5>
                <div class="mb-2"><span class="small text-muted d-block">Email</span><?= htmlspecialchars($contact['email'] ?: '-') ?></div>
                <div class="mb-2"><span class="small text-muted d-block">Telefone</span><?= htmlspecialchars($contact['phone'] ?: '-') ?></div>
                <div class="mb-2"><span class="small text-muted d-block">Website</span><?= htmlspecialchars($contact['website'] ?: '-') ?></div>
                <div class="mb-0"><span class="small text-muted d-block">Instagram / redes</span><?= htmlspecialchars($contact['social_profile'] ?: '-') ?></div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h5 class="mb-3">Localização e tracking</h5>
                <div class="mb-2"><span class="small text-muted d-block">País</span><?= htmlspecialchars($contact['country'] ?: '-') ?></div>
                <div class="mb-2"><span class="small text-muted d-block">Cidade</span><?= htmlspecialchars($contact['city'] ?: '-') ?></div>
                <div class="mb-2"><span class="small text-muted d-block">Origem lead</span><?= htmlspecialchars($contact['lead_source'] ?: '-') ?></div>
                <div class="mb-2"><span class="small text-muted d-block">Primeiro contacto</span><?= htmlspecialchars($contact['first_contact_at'] ?: '-') ?></div>
                <div class="mb-0"><span class="small text-muted d-block">Registo</span><?= htmlspecialchars($contact['created_at']) ?></div>
            </div>
        </div>
    </div>
</div>
