<!doctype html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Alinhamento - <?= htmlspecialchars($event['title']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 32px; color: #222; }
        h1 { margin-bottom: 6px; }
        .meta { color: #555; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ddd; padding: 10px; font-size: 14px; text-align: left; }
        th { background: #f3f3f3; }
        .empty { border: 1px dashed #999; padding: 14px; margin-top: 16px; }
        @media print {
            body { margin: 12mm; }
        }
    </style>
</head>
<body>
<h1><?= htmlspecialchars($event['title']) ?></h1>
<div class="meta">
    <strong>Data:</strong> <?= htmlspecialchars($event['date']) ?> <?= htmlspecialchars(substr($event['time'], 0, 5)) ?> ·
    <strong>Local:</strong> <?= htmlspecialchars($event['location']) ?> ·
    <strong>Cliente:</strong> <?= htmlspecialchars($event['client_name'] ?? '-') ?>
</div>

<?php if (empty($scheduleItems)): ?>
    <div class="empty">Ainda não há linhas de alinhamento para este evento.</div>
<?php else: ?>
    <table>
        <thead>
        <tr>
            <th>Início</th>
            <th>Duração</th>
            <th>Tipo</th>
            <th>Descrição</th>
            <th>Responsável</th>
            <th>Notas</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($scheduleItems as $item): ?>
            <tr>
                <td><?= htmlspecialchars(substr($item['starts_at'], 0, 5)) ?></td>
                <td><?= (int)$item['duration_minutes'] ?> min</td>
                <td><?= htmlspecialchars($item['item_type']) ?></td>
                <td><?= htmlspecialchars($item['title']) ?></td>
                <td><?= htmlspecialchars($item['responsible'] ?? '-') ?></td>
                <td><?= htmlspecialchars($item['notes'] ?? '-') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<script>
    window.addEventListener('load', () => {
        window.print();
    });
</script>
</body>
</html>
