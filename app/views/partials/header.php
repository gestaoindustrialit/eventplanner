<?php
$user = currentUser();
$currentController = strtolower((string)($_GET['controller'] ?? 'dashboard'));
$currentAction = strtolower((string)($_GET['action'] ?? 'index'));

$isActiveLink = static function (string $controller, ?string $action = null) use ($currentController, $currentAction): bool {
    if ($currentController !== strtolower($controller)) {
        return false;
    }

    if ($action === null) {
        return true;
    }

    return $currentAction === strtolower($action);
};
?>
<!doctype html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_PATH ?>/assets/branding/chorarderir-logo.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/app.css">
</head>
<body>
<div class="app-shell d-flex" id="app-shell">
    <?php if (isLoggedIn()): ?>
        <aside class="sidebar text-white p-3 d-none d-lg-flex flex-column">
            <h5 class="mb-4 sidebar-brand"><i class="bi bi-mic-fill"></i> <span><?= APP_NAME ?></span></h5>
            <div class="sidebar-user mb-3">
                <p class="small mb-1">Olá, <?= htmlspecialchars($user['name']) ?></p>
                <span class="badge bg-light text-dark"><?= htmlspecialchars($user['role']) ?></span>
            </div>
            <nav class="nav flex-column gap-1 sidebar-nav">
                <?php if (isAdmin()): ?>
                    <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('dashboard') ? 'active' : '' ?>" href="<?= BASE_URL ?>"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
                    <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('comedian') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=comedian&action=index"><i class="bi bi-mic"></i><span>Comediantes</span></a>
                    <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('client') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=client&action=index"><i class="bi bi-people"></i><span>Clientes</span></a>
                    <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('crm') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=crm&action=index"><i class="bi bi-kanban"></i><span>CRM</span></a>
                    <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('event', 'index') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=event&action=index"><i class="bi bi-calendar-event"></i><span>Eventos</span></a>
                    <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('event', 'openSchedule') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=event&action=openSchedule"><i class="bi bi-list-check"></i><span>Alinhamentos</span></a>
                    <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('checklist') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=checklist&action=index"><i class="bi bi-check2-square"></i><span>Checklists</span></a>
                    <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('reservation') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=reservation&action=index"><i class="bi bi-ticket-detailed"></i><span>Reservas</span></a>
                    <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('reservation', 'eventos') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=reservation&action=eventos"><i class="bi bi-qr-code-scan"></i><span>Admissões</span></a>
                    <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('publicpage') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=publicpage&action=index"><i class="bi bi-layout-text-window-reverse"></i><span>Páginas públicas</span></a>
                    <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('partner') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=partner&action=index"><i class="bi bi-diagram-3"></i><span>Parceiros</span></a>
                    <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('publicsite') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=publicsite&action=index"><i class="bi bi-globe2"></i><span>Publicar website</span></a>
                    <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('newsletter') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=newsletter&action=index"><i class="bi bi-envelope-paper"></i><span>Newsletter</span></a>
                    <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('presscontact') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=presscontact&action=index"><i class="bi bi-newspaper"></i><span>Contactos Press</span></a>
                <?php else: ?>
                    <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('comedianarea') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=comedianarea&action=index"><i class="bi bi-calendar2-week"></i><span>Os meus eventos</span></a>
                <?php endif; ?>
                <a class="nav-link nav-link-logout text-warning mt-2 sidebar-nav-link logout-link" href="<?= BASE_URL ?>?controller=auth&action=logout"><i class="bi bi-box-arrow-right"></i><span>Terminar sessão</span></a>
            </nav>
        </aside>
    <?php endif; ?>
    <main class="content flex-grow-1">
        <?php if (isLoggedIn()): ?>
            <header class="topbar d-flex d-lg-none align-items-center justify-content-between px-3 py-2 sticky-top">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-light" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu">☰</button>
                    <div class="fw-semibold">🎤 <?= APP_NAME ?></div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark"><?= htmlspecialchars($user['role']) ?></span>
                    <a class="btn btn-sm btn-outline-light" href="<?= BASE_URL ?>?controller=auth&action=logout">Sair</a>
                </div>
            </header>
            <div class="offcanvas offcanvas-start text-bg-dark d-lg-none" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" id="mobileMenuLabel">Menu</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <nav class="nav flex-column gap-2">
                        <?php if (isAdmin()): ?>
                            <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('dashboard') ? 'active' : '' ?>" href="<?= BASE_URL ?>"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
                            <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('comedian') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=comedian&action=index"><i class="bi bi-mic"></i><span>Comediantes</span></a>
                            <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('client') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=client&action=index"><i class="bi bi-people"></i><span>Clientes</span></a>
                            <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('crm') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=crm&action=index"><i class="bi bi-kanban"></i><span>CRM</span></a>
                            <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('event', 'index') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=event&action=index"><i class="bi bi-calendar-event"></i><span>Eventos</span></a>
                            <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('event', 'openSchedule') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=event&action=openSchedule"><i class="bi bi-list-check"></i><span>Alinhamentos</span></a>
                            <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('checklist') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=checklist&action=index"><i class="bi bi-check2-square"></i><span>Checklists</span></a>
                            <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('reservation') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=reservation&action=index"><i class="bi bi-ticket-detailed"></i><span>Reservas</span></a>
                            <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('reservation', 'eventos') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=reservation&action=eventos"><i class="bi bi-qr-code-scan"></i><span>Admissões</span></a>
                            <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('publicpage') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=publicpage&action=index"><i class="bi bi-layout-text-window-reverse"></i><span>Páginas públicas</span></a>
                            <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('partner') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=partner&action=index"><i class="bi bi-diagram-3"></i><span>Parceiros</span></a>
                            <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('publicsite') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=publicsite&action=index"><i class="bi bi-globe2"></i><span>Publicar website</span></a>
                            <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('newsletter') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=newsletter&action=index"><i class="bi bi-envelope-paper"></i><span>Newsletter</span></a>
                            <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('presscontact') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=presscontact&action=index"><i class="bi bi-newspaper"></i><span>Contactos Press</span></a>
                        <?php else: ?>
                            <a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('comedianarea') ? 'active' : '' ?>" href="<?= BASE_URL ?>?controller=comedianarea&action=index"><i class="bi bi-calendar2-week"></i><span>Os meus eventos</span></a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        <?php endif; ?>
        <section class="content-body p-3 p-md-4 p-xl-5">
        <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <?php if ($msg = flash('error')): ?><div class="alert alert-danger"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
