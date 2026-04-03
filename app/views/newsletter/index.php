<h2 class="mb-3">Newsletter</h2>
<p class="text-muted">Lista de contactos com consentimento RGPD recolhidos no website público.</p>

<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>Email</th>
                <th>Nome</th>
                <th>Estado</th>
                <th>Consentimento</th>
                <th>Data</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($subscriptions as $subscription): ?>
                <tr>
                    <td><?= htmlspecialchars($subscription['email']) ?></td>
                    <td><?= htmlspecialchars($subscription['name'] ?: '-') ?></td>
                    <td>
                        <?php if (($subscription['status'] ?? 'active') === 'active'): ?>
                            <span class="badge bg-success">Ativa</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Cancelada</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <small class="d-block"><?= (int)$subscription['gdpr_consent'] === 1 ? 'Sim' : 'Não' ?></small>
                        <small class="text-muted"><?= htmlspecialchars($subscription['consent_text']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($subscription['subscribed_at']) ?></td>
                    <td>
                        <?php if (($subscription['status'] ?? 'active') === 'active'): ?>
                            <form method="post" action="<?= BASE_URL ?>?controller=newsletter&action=unsubscribe" onsubmit="return confirm('Marcar subscrição como cancelada?');">
                                <input type="hidden" name="id" value="<?= (int)$subscription['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger">Cancelar</button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (count($subscriptions) === 0): ?>
                <tr>
                    <td colspan="6" class="text-muted">Ainda não existem subscrições de newsletter.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
