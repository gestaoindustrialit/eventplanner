<?php
$root = __DIR__;
$schemaFile = $root . '/database/schema.sql';
$dbPath = getenv('SQLITE_PATH') ?: $root . '/database/eventplanner.sqlite';
$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!file_exists($schemaFile)) {
            throw new RuntimeException('Ficheiro schema.sql não encontrado em ' . $schemaFile);
        }

        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir) && !mkdir($dbDir, 0775, true) && !is_dir($dbDir)) {
            throw new RuntimeException('Não foi possível criar a pasta da base de dados: ' . $dbDir);
        }

        $schemaSql = file_get_contents($schemaFile);
        if ($schemaSql === false) {
            throw new RuntimeException('Não foi possível ler o schema.sql.');
        }

        $pdo = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $pdo->exec($schemaSql);
        $message = 'Instalação concluída com sucesso. Base de dados criada em: ' . $dbPath;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalar EventPlanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 760px;">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h3">Instalação do EventPlanner</h1>
            <p class="text-muted mb-4">Se estiveres a ver erro 500, executa esta instalação para criar/resetar a base de dados SQLite.</p>

            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">Erro: <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <ul class="small text-muted">
                <li><strong>Schema:</strong> <code><?= htmlspecialchars($schemaFile) ?></code></li>
                <li><strong>DB destino:</strong> <code><?= htmlspecialchars($dbPath) ?></code></li>
                <li><strong>Nota:</strong> esta ação apaga e recria as tabelas.</li>
            </ul>

            <form method="post">
                <button class="btn btn-primary">Instalar / Recriar base de dados</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
