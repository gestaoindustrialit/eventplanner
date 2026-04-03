<h2 class="mb-4">Reservas dos Eventos</h2>

<div class="table-responsive">
    <table class="table table-striped align-middle searchable-table">
        <thead>
            <tr>
                <th>Evento</th>
                <th>Cliente</th>
                <th>Contacto</th>
                <th>Bilhetes</th>
                <th>Estado</th>
                <th>Criada em</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reservations as $reservation): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($reservation['event_title']) ?></strong><br>
                        <small class="text-muted"><?= htmlspecialchars($reservation['event_date']) ?> às <?= htmlspecialchars(substr($reservation['event_time'], 0, 5)) ?></small>
                    </td>
                    <td>
                        <?= htmlspecialchars($reservation['customer_name']) ?><br>
                        <small class="text-muted"><?= htmlspecialchars($reservation['notes'] ?? '') ?></small>
                    </td>
                    <td>
                        <?= htmlspecialchars($reservation['customer_email']) ?><br>
                        <small class="text-muted"><?= htmlspecialchars($reservation['customer_phone'] ?? '-') ?></small>
                    </td>
                    <td><?= (int)$reservation['tickets'] ?></td>
                    <td>
                        <form method="post" action="<?= BASE_URL ?>?controller=reservation&action=updateStatus" class="d-flex gap-2">
                            <input type="hidden" name="id" value="<?= (int)$reservation['id'] ?>">
                            <select class="form-select form-select-sm" name="status">
                                <option value="new" <?= $reservation['status'] === 'new' ? 'selected' : '' ?>>Nova</option>
                                <option value="confirmed" <?= $reservation['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmada</option>
                                <option value="cancelled" <?= $reservation['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelada</option>
                            </select>
                            <button class="btn btn-sm btn-outline-dark">Guardar</button>
                        </form>
                    </td>
                    <td><?= htmlspecialchars($reservation['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
