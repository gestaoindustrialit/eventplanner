<!doctype html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Alinhamento - <?= htmlspecialchars($event['title']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 32px; color: #222; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; }
        h1 { margin-bottom: 6px; margin-top: 0; }
        .logo-top { width: 180px; height: auto; object-fit: contain; }
        .meta { color: #555; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ddd; padding: 10px; font-size: 14px; text-align: left; }
        th { background: #f3f3f3; }
        .time-cell,
        .responsible-cell { font-weight: 700; }
        .duration-cell,
        .description-cell,
        .responsible-cell,
        .notes-cell { font-size: 11px; line-height: 1.25; }
        .empty { border: 1px dashed #999; padding: 14px; margin-top: 16px; }
        .footer {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 12px;
            color: #666;
        }
        .logo-footer { width: 72px; height: auto; object-fit: contain; }
        @media print {
            body { margin: 12mm; }
        }
    </style>
</head>
<body>
<div class="header">
    <div>
        <h1><?= htmlspecialchars($event['title']) ?></h1>
        <div class="meta">
            <strong>Data:</strong> <?= htmlspecialchars($event['date']) ?> <?= htmlspecialchars(substr($event['time'], 0, 5)) ?> ·
            <strong>Local:</strong> <?= htmlspecialchars($event['location']) ?> ·
            <strong>Cliente:</strong> <?= htmlspecialchars($event['client_name'] ?? '-') ?>
        </div>
    </div>
    <img class="logo-top" src="<?= htmlspecialchars(BASE_PATH) ?>/assets/branding/chorarderir-logo.svg" alt="Logótipo Chorar de Rir">
</div>

<?php if (empty($scheduleItems)): ?>
    <div class="empty">Ainda não há linhas de alinhamento para este evento.</div>
<?php else: ?>
    <table>
        <thead>
        <tr>
            <th>Início</th>
            <th>Duração</th>
            <th>Descrição</th>
            <th>Responsável</th>
            <th>Notas</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($scheduleItems as $item): ?>
            <tr>
                <td class="time-cell"><?= htmlspecialchars(substr($item['starts_at'], 0, 5)) ?></td>
                <td class="duration-cell"><?= (int)$item['duration_minutes'] ?> min</td>
                <td class="description-cell"><?= htmlspecialchars($item['title']) ?></td>
                <td class="responsible-cell"><?= htmlspecialchars($item['responsible'] ?? '-') ?></td>
                <td class="notes-cell"><?= htmlspecialchars($item['notes'] ?? '-') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<div class="footer">
    <img class="logo-footer" src="<?= htmlspecialchars(BASE_PATH) ?>/assets/branding/chorarderir-logo.svg" alt="Logótipo Chorar de Rir">
    <span>chorarderir.com - info@chorarderir.com - 927202583</span>
</div>

<script>
    window.addEventListener('load', () => {
        window.print();
    });
</script>
</body>
</html>
