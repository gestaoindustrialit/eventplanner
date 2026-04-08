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
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0"><?= $contact ? 'Editar contacto CRM' : 'Novo contacto CRM' ?></h2>
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>?controller=crm&action=index">Voltar</a>
</div>

<form method="post" class="card" action="<?= BASE_URL ?>?controller=crm&action=<?= $contact ? 'update&id=' . $contact['id'] : 'store' ?>">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Entidade / Empresa</label>
                <input class="form-control" name="entity_name" value="<?= htmlspecialchars($contact['entity_name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Pessoa de contacto</label>
                <input class="form-control" name="contact_name" value="<?= htmlspecialchars($contact['contact_name'] ?? '') ?>">
            </div>

            <div class="col-md-4"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?= htmlspecialchars($contact['email'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label">Telefone</label><input class="form-control" name="phone" value="<?= htmlspecialchars($contact['phone'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label">Website</label><input class="form-control" name="website" value="<?= htmlspecialchars($contact['website'] ?? '') ?>" placeholder="https://"></div>

            <div class="col-md-4"><label class="form-label">Instagram / Redes</label><input class="form-control" name="social_profile" value="<?= htmlspecialchars($contact['social_profile'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label">País</label><input class="form-control" name="country" value="<?= htmlspecialchars($contact['country'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label">Cidade</label><input class="form-control" name="city" value="<?= htmlspecialchars($contact['city'] ?? '') ?>"></div>

            <div class="col-md-3">
                <label class="form-label">Mercado</label>
                <select name="market" class="form-select" required>
                    <?php foreach ($markets as $market): ?>
                        <option value="<?= $market ?>" <?= ($contact['market'] ?? 'nacional') === $market ? 'selected' : '' ?>><?= ucfirst($market) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tipo</label>
                <select name="contact_type" class="form-select" required>
                    <?php foreach ($types as $type): ?>
                        <option value="<?= $type ?>" <?= ($contact['contact_type'] ?? 'outro') === $type ? 'selected' : '' ?>><?= $typeLabels[$type] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Estado</label>
                <select name="status" class="form-select" required>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?= $status ?>" <?= ($contact['status'] ?? 'novo') === $status ? 'selected' : '' ?>><?= $statusLabels[$status] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Prioridade</label>
                <select name="priority" class="form-select" required>
                    <?php foreach ($priorities as $priority): ?>
                        <option value="<?= $priority ?>" <?= ($contact['priority'] ?? 'media') === $priority ? 'selected' : '' ?>><?= $priorityLabels[$priority] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4"><label class="form-label">Origem do lead</label><input class="form-control" name="lead_source" value="<?= htmlspecialchars($contact['lead_source'] ?? '') ?>"></div>
            <div class="col-md-2"><label class="form-label">1º contacto</label><input type="date" class="form-control" name="first_contact_at" value="<?= htmlspecialchars($contact['first_contact_at'] ?? '') ?>"></div>
            <div class="col-md-2"><label class="form-label">Último contacto</label><input type="date" class="form-control" name="last_contact_at" value="<?= htmlspecialchars($contact['last_contact_at'] ?? '') ?>"></div>
            <div class="col-md-2"><label class="form-label">Próxima ação</label><input type="date" class="form-control" name="next_follow_up_at" value="<?= htmlspecialchars($contact['next_follow_up_at'] ?? '') ?>"></div>
            <div class="col-md-2"><label class="form-label">Valor potencial (€)</label><input type="number" min="0" step="0.01" class="form-control" name="potential_value" value="<?= htmlspecialchars($contact['potential_value'] ?? '') ?>"></div>

            <div class="col-12"><label class="form-label">Observações</label><textarea class="form-control" name="observations" rows="4" placeholder="Informação relevante para contexto comercial e follow-up."><?= htmlspecialchars($contact['observations'] ?? '') ?></textarea></div>
            <div class="col-12"><label class="form-label">Notas internas</label><textarea class="form-control" name="internal_notes" rows="4" placeholder="Notas privadas da equipa."><?= htmlspecialchars($contact['internal_notes'] ?? '') ?></textarea></div>
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-end gap-2">
        <?php if ($contact): ?>
            <a class="btn btn-outline-danger delete-btn" href="<?= BASE_URL ?>?controller=crm&action=delete&id=<?= $contact['id'] ?>">Eliminar</a>
        <?php endif; ?>
        <button class="btn btn-dark">Guardar</button>
    </div>
</form>
