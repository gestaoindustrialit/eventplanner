<?php $user = currentUser(); ?>
<!doctype html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/app.css">
</head>
<body>
<div class="app-shell d-flex" id="app-shell">
    <?php if (isLoggedIn()): ?>
        <aside class="sidebar text-white p-3 d-none d-lg-flex flex-column">
            <h5 class="mb-4">🎤 <?= APP_NAME ?></h5>
            <div class="sidebar-user mb-3">
                <p class="small mb-1">Olá, <?= htmlspecialchars($user['name']) ?></p>
                <span class="badge bg-light text-dark"><?= htmlspecialchars($user['role']) ?></span>
            </div>
            <nav class="nav flex-column gap-2">
                <?php if (isAdmin()): ?>
                    <a class="nav-link text-white" href="<?= BASE_URL ?>">Dashboard</a>
                    <a class="nav-link text-white" href="<?= BASE_URL ?>?controller=comedian&action=index">Comediantes</a>
                    <a class="nav-link text-white" href="<?= BASE_URL ?>?controller=client&action=index">Clientes</a>
                    <a class="nav-link text-white" href="<?= BASE_URL ?>?controller=event&action=index">Eventos</a>
                    <a class="nav-link text-white" href="<?= BASE_URL ?>?controller=event&action=openSchedule">Alinhamentos</a>
                    <a class="nav-link text-white" href="<?= BASE_URL ?>?controller=reservation&action=index">Reservas</a>
                    <a class="nav-link text-white" href="<?= BASE_URL ?>?controller=publicpage&action=index">Páginas públicas</a>
                    <a class="nav-link text-white" href="<?= BASE_URL ?>?controller=publicsite&action=index">Publicar website</a>
                <?php else: ?>
                    <a class="nav-link text-white" href="<?= BASE_URL ?>?controller=comedianarea&action=index">Os meus eventos</a>
                <?php endif; ?>
                <a class="nav-link nav-link-logout text-warning mt-2" href="<?= BASE_URL ?>?controller=auth&action=logout">Terminar sessão</a>
            </nav>
        </aside>
    <?php endif; ?>
    <main class="content flex-grow-1">
        <?php if (isLoggedIn()): ?>
            <header class="topbar d-flex d-lg-none align-items-center justify-content-between px-3 py-2 sticky-top">
                <div class="fw-semibold">🎤 <?= APP_NAME ?></div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark"><?= htmlspecialchars($user['role']) ?></span>
                    <a class="btn btn-sm btn-outline-light" href="<?= BASE_URL ?>?controller=auth&action=logout">Sair</a>
                </div>
            </header>
        <?php endif; ?>
        <section class="content-body p-3 p-md-4 p-xl-5">
        <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <?php if ($msg = flash('error')): ?><div class="alert alert-danger"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
