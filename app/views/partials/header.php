<?php $user = currentUser(); ?>
<!doctype html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="d-flex" id="app-shell">
    <?php if (isLoggedIn()): ?>
        <aside class="sidebar bg-dark text-white p-3">
            <h5 class="mb-4">🎤 <?= APP_NAME ?></h5>
            <p class="small mb-1">Olá, <?= htmlspecialchars($user['name']) ?></p>
            <span class="badge bg-secondary mb-3"><?= htmlspecialchars($user['role']) ?></span>
            <nav class="nav flex-column gap-2">
                <?php if (isAdmin()): ?>
                    <a class="nav-link text-white" href="<?= BASE_URL ?>">Dashboard</a>
                    <a class="nav-link text-white" href="<?= BASE_URL ?>?controller=comedian&action=index">Comediantes</a>
                    <a class="nav-link text-white" href="<?= BASE_URL ?>?controller=client&action=index">Clientes</a>
                    <a class="nav-link text-white" href="<?= BASE_URL ?>?controller=event&action=index">Eventos</a>
                    <a class="nav-link text-white" href="<?= BASE_URL ?>?controller=event&action=openSchedule">Alinhamentos</a>
                <?php else: ?>
                    <a class="nav-link text-white" href="<?= BASE_URL ?>?controller=comedianarea&action=index">Os meus eventos</a>
                <?php endif; ?>
                <a class="nav-link text-warning" href="<?= BASE_URL ?>?controller=auth&action=logout">Terminar sessão</a>
            </nav>
        </aside>
    <?php endif; ?>
    <main class="content flex-grow-1 p-4">
        <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <?php if ($msg = flash('error')): ?><div class="alert alert-danger"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
