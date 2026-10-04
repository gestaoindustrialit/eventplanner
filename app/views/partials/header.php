<?php
$user = currentUser();
$currentController = strtolower((string)($_GET['controller'] ?? 'dashboard'));
$currentAction = strtolower((string)($_GET['action'] ?? 'index'));
$showAdmissionsLink = isAdmin()
    || (isLoggedIn() && (new User($db))->hasAdmissionAccess((int)($user['id'] ?? 0)));

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
    <?php if ($currentController === 'reservation' && $currentAction === 'eventos'): ?><meta name="robots" content="noindex,nofollow,noarchive"><?php endif; ?>
    <title><?= APP_NAME ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_PATH ?>/assets/branding/chorarderir-logo.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/app.css">
</head>
<body class="<?= isLoggedIn() ? 'is-authenticated' : 'is-guest' ?>">
<?php
$items = [
 'dashboard'=>['Dashboard','speedometer2',BASE_URL], 'comedian'=>['Comediantes','mic',BASE_URL.'?controller=comedian&action=index'],
 'client'=>['Clientes','people',BASE_URL.'?controller=client&action=index'], 'crm'=>['CRM','kanban',BASE_URL.'?controller=crm&action=index'],
 'event'=>['Eventos','calendar-event',BASE_URL.'?controller=event&action=index'], 'eventseries'=>['Séries','calendar2-range',BASE_URL.'?controller=eventseries&action=index'], 'checklist'=>['Checklists','check2-square',BASE_URL.'?controller=checklist&action=index'],
 'reservation'=>['Admissões','ticket-detailed',BASE_PATH.'/eventos/'], 'publicpage'=>['Páginas públicas','layout-text-window-reverse',BASE_URL.'?controller=publicpage&action=index'],
 'blogpost'=>['Blog','journal-richtext',BASE_URL.'?controller=blogpost&action=index'], 'partner'=>['Parceiros','diagram-3',BASE_URL.'?controller=partner&action=index'],
 'publicsite'=>['Publicar website','globe2',BASE_URL.'?controller=publicsite&action=index'], 'newsletter'=>['Newsletter','envelope-paper',BASE_URL.'?controller=newsletter&action=index'],
 'presscontact'=>['Contactos Press','newspaper',BASE_URL.'?controller=presscontact&action=index'],
];
$renderMenu = static function () use ($items, $isActiveLink, $showAdmissionsLink): void { foreach ($items as $permission=>$item) { if ($permission === 'reservation' ? !$showAdmissionsLink : !can($permission)) continue; ?>
<a class="nav-link text-white sidebar-nav-link <?= $isActiveLink($permission)?'active':'' ?>" href="<?= $item[2] ?>"><i class="bi bi-<?= $item[1] ?>"></i><span><?= $item[0] ?></span></a><?php } if (isAdmin()) { ?>
<a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('user')?'active':'' ?>" href="<?= BASE_URL ?>?controller=user"><i class="bi bi-person-gear"></i><span>Perfis e permissões</span></a><?php } ?>
<a class="nav-link nav-link-logout text-warning mt-2 sidebar-nav-link" href="<?= BASE_URL ?>?controller=auth&action=logout"><i class="bi bi-box-arrow-right"></i><span>Terminar sessão</span></a><?php };
?>
<div class="app-shell d-flex" id="app-shell">
<?php if(isLoggedIn()): ?><aside class="sidebar text-white d-none d-lg-flex flex-column" aria-label="Navegação principal">
    <div class="sidebar-header">
        <a class="sidebar-brand text-white text-decoration-none" href="<?= BASE_URL ?>" aria-label="Ir para o dashboard"><span class="sidebar-brand-mark"><i class="bi bi-mic-fill"></i></span><span class="sidebar-label"><?= APP_NAME ?></span></a>
        <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Recolher menu" aria-expanded="true"><i class="bi bi-layout-sidebar-inset"></i></button>
    </div>
    <div class="sidebar-user"><span class="sidebar-avatar"><?= htmlspecialchars(strtoupper(substr((string)($user['name'] ?? 'U'), 0, 1))) ?></span><div class="sidebar-user-copy"><p class="mb-0 text-truncate"><?= htmlspecialchars($user['name']) ?></p><small><?= htmlspecialchars($user['profile_type']??$user['role']) ?></small></div></div>
    <nav class="nav flex-column gap-1 sidebar-nav"><?php $renderMenu(); ?></nav>
</aside><?php endif; ?>
<main class="content flex-grow-1">
<?php if(isLoggedIn()): ?><header class="topbar d-flex d-lg-none align-items-center justify-content-between sticky-top"><button class="topbar-menu-btn" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu" aria-label="Abrir menu"><i class="bi bi-list"></i></button><a class="topbar-brand" href="<?= BASE_URL ?>"><span class="sidebar-brand-mark"><i class="bi bi-mic-fill"></i></span><span><?= APP_NAME ?></span></a><span class="mobile-avatar" aria-hidden="true"><?= htmlspecialchars(strtoupper(substr((string)($user['name'] ?? 'U'), 0, 1))) ?></span></header>
<div class="offcanvas offcanvas-start mobile-menu text-bg-dark d-lg-none" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel"><div class="offcanvas-header"><div><span class="text-uppercase small text-white-50">Navegação</span><h5 class="mb-0" id="mobileMenuLabel"><?= APP_NAME ?></h5></div><button class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Fechar"></button></div><div class="offcanvas-user"><span class="sidebar-avatar"><?= htmlspecialchars(strtoupper(substr((string)($user['name'] ?? 'U'), 0, 1))) ?></span><div><strong><?= htmlspecialchars($user['name']) ?></strong><small><?= htmlspecialchars($user['profile_type']??$user['role']) ?></small></div></div><div class="offcanvas-body"><nav class="nav flex-column gap-1"><?php $renderMenu(); ?></nav></div></div>
<nav class="mobile-bottom-nav d-lg-none" aria-label="Acesso rápido">
    <a href="<?= BASE_URL ?>" class="<?= $isActiveLink('dashboard') ? 'active' : '' ?>"><i class="bi bi-grid-1x2"></i><span>Início</span></a>
    <?php if (can('event')): ?><a href="<?= BASE_URL ?>?controller=event&action=index" class="<?= $isActiveLink('event') ? 'active' : '' ?>"><i class="bi bi-calendar-event"></i><span>Eventos</span></a><?php endif; ?>
    <?php if ($showAdmissionsLink): ?><a href="<?= BASE_PATH ?>/eventos/" class="<?= $isActiveLink('reservation', 'eventos') ? 'active' : '' ?>"><i class="bi bi-qr-code-scan"></i><span>Entradas</span></a><?php endif; ?>
    <button type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu"><i class="bi bi-three-dots"></i><span>Mais</span></button>
</nav><?php endif; ?>
        <section class="content-body p-3 p-md-4 p-xl-5">
        <?php if ($msg = flash('success')): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button></div><?php endif; ?>
        <?php if ($msg = flash('error')): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button></div><?php endif; ?>
