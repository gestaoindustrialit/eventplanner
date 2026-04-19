<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h2 class="mb-1">Difusão rápida para imprensa</h2>
        <p class="text-muted mb-0">Seleciona evento e filtra contactos por distrito ou localidade para envio imediato.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>?controller=presscontact&action=index">Voltar aos contactos</a>
</div>

<form method="get" class="card card-body mb-4">
    <input type="hidden" name="controller" value="presscontact">
    <input type="hidden" name="action" value="outreach">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Evento</label>
            <select name="event_id" class="form-select">
                <option value="0">Selecionar evento...</option>
                <?php foreach ($events as $eventOption): ?>
                    <option value="<?= (int)$eventOption['id'] ?>" <?= (int)$eventId === (int)$eventOption['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($eventOption['date']) ?> · <?= htmlspecialchars($eventOption['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Distrito</label>
            <select name="district" class="form-select" onchange="this.form.submit()">
                <option value="">Todos</option>
                <?php foreach ($districts as $districtOption): ?>
                    <option value="<?= htmlspecialchars($districtOption) ?>" <?= $district === $districtOption ? 'selected' : '' ?>>
                        <?= htmlspecialchars($districtOption) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Localidade</label>
            <select name="locality" class="form-select">
                <option value="">Todas</option>
                <?php foreach ($localities as $localityOption): ?>
                    <option value="<?= htmlspecialchars($localityOption) ?>" <?= $locality === $localityOption ? 'selected' : '' ?>>
                        <?= htmlspecialchars($localityOption) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="mt-3 d-flex gap-2">
        <button class="btn btn-dark" type="submit">Filtrar e preparar envio</button>
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>?controller=presscontact&action=outreach">Limpar filtros</a>
    </div>
</form>

<div class="card mb-3">
    <div class="card-body">
        <h5 class="card-title mb-3">Resumo do envio</h5>
        <p class="mb-2"><strong>Evento:</strong> <?= $event ? htmlspecialchars($event['title']) : 'Não selecionado' ?></p>
        <p class="mb-2"><strong>Filtro:</strong> <?= $district ? htmlspecialchars($district) : 'Todos os distritos' ?><?= $locality ? ' · ' . htmlspecialchars($locality) : '' ?></p>
        <p class="mb-3"><strong>Destinatários selecionados:</strong> <span id="selected-recipients-count"><?= count($emails) ?></span></p>

        <?php if ($event && $emails): ?>
            <a class="btn btn-success" id="open-bcc-email-btn" href="<?= htmlspecialchars($mailtoLink) ?>">
                <i class="bi bi-envelope-paper"></i>
                Abrir email com BCC preenchido
            </a>
        <?php else: ?>
            <div class="alert alert-warning mb-0">
                Seleciona um evento e garante que existem contactos com email para este filtro.
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th style="width: 1%;">
                    <input class="form-check-input" type="checkbox" id="select-all-recipients" checked title="Selecionar todos">
                </th>
                <th>Nome</th>
                <th>Email</th>
                <th>Localidade</th>
                <th>Distrito</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$contacts): ?>
            <tr>
                <td colspan="5" class="text-muted">Sem contactos para os filtros selecionados.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($contacts as $contact): ?>
            <tr>
                <td>
                    <?php $email = trim((string)($contact['email'] ?? '')); ?>
                    <input
                        class="form-check-input outreach-recipient-checkbox"
                        type="checkbox"
                        value="<?= htmlspecialchars($email) ?>"
                        <?= $email !== '' ? 'checked' : 'disabled' ?>
                    >
                </td>
                <td><?= htmlspecialchars($contact['name']) ?></td>
                <td><?= htmlspecialchars($contact['email']) ?></td>
                <td><?= htmlspecialchars($contact['locality']) ?></td>
                <td><?= htmlspecialchars($contact['district']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($event && $emails): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var recipientCheckboxes = Array.prototype.slice.call(document.querySelectorAll('.outreach-recipient-checkbox'));
    var selectAllCheckbox = document.getElementById('select-all-recipients');
    var selectedRecipientsCount = document.getElementById('selected-recipients-count');
    var emailButton = document.getElementById('open-bcc-email-btn');
    if (!selectAllCheckbox || !selectedRecipientsCount || !emailButton || recipientCheckboxes.length === 0) {
        return;
    }

    var baseSubject = <?= json_encode($event ? sprintf('Divulgação: %s', $event['title']) : 'Divulgação de evento') ?>;
    var baseBody = <?= json_encode($event ? $this->eventPressTemplate($event) : "Olá,\n\nPartilhamos o próximo evento para divulgação.\n\nObrigado.") ?>;

    var getActiveCheckboxes = function () {
        return recipientCheckboxes.filter(function (checkbox) {
            return !checkbox.disabled;
        });
    };

    var getSelectedEmails = function () {
        return recipientCheckboxes
            .filter(function (checkbox) {
                return checkbox.checked && !checkbox.disabled && checkbox.value.trim() !== '';
            })
            .map(function (checkbox) {
                return checkbox.value.trim();
            });
    };

    var updateState = function () {
        var selectedEmails = getSelectedEmails();
        selectedRecipientsCount.textContent = String(selectedEmails.length);

        var activeCheckboxes = getActiveCheckboxes();
        var allChecked = activeCheckboxes.length > 0 && activeCheckboxes.every(function (checkbox) {
            return checkbox.checked;
        });
        selectAllCheckbox.checked = allChecked;
        selectAllCheckbox.indeterminate = !allChecked && selectedEmails.length > 0;

        if (selectedEmails.length === 0) {
            emailButton.classList.add('disabled');
            emailButton.setAttribute('aria-disabled', 'true');
            emailButton.setAttribute('tabindex', '-1');
            emailButton.href = '#';
            return;
        }

        emailButton.classList.remove('disabled');
        emailButton.removeAttribute('aria-disabled');
        emailButton.removeAttribute('tabindex');
        emailButton.href = 'mailto:?bcc='
            + encodeURIComponent(selectedEmails.join(';'))
            + '&subject=' + encodeURIComponent(baseSubject)
            + '&body=' + encodeURIComponent(baseBody);
    };

    selectAllCheckbox.addEventListener('change', function () {
        recipientCheckboxes.forEach(function (checkbox) {
            if (!checkbox.disabled) {
                checkbox.checked = selectAllCheckbox.checked;
            }
        });
        updateState();
    });

    recipientCheckboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', updateState);
    });

    updateState();
});
</script>
<?php endif; ?>
