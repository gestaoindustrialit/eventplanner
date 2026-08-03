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
<?php
$items = [
 'dashboard'=>['Dashboard','speedometer2',BASE_URL], 'comedian'=>['Comediantes','mic',BASE_URL.'?controller=comedian&action=index'],
 'client'=>['Clientes','people',BASE_URL.'?controller=client&action=index'], 'crm'=>['CRM','kanban',BASE_URL.'?controller=crm&action=index'],
 'event'=>['Eventos','calendar-event',BASE_URL.'?controller=event&action=index'], 'checklist'=>['Checklists','check2-square',BASE_URL.'?controller=checklist&action=index'],
 'reservation'=>['Reservas e admissões','ticket-detailed',BASE_URL.'?controller=reservation&action=index'], 'publicpage'=>['Páginas públicas','layout-text-window-reverse',BASE_URL.'?controller=publicpage&action=index'],
 'blogpost'=>['Blog','journal-richtext',BASE_URL.'?controller=blogpost&action=index'], 'partner'=>['Parceiros','diagram-3',BASE_URL.'?controller=partner&action=index'],
 'publicsite'=>['Publicar website','globe2',BASE_URL.'?controller=publicsite&action=index'], 'newsletter'=>['Newsletter','envelope-paper',BASE_URL.'?controller=newsletter&action=index'],
 'presscontact'=>['Contactos Press','newspaper',BASE_URL.'?controller=presscontact&action=index'],
];
$renderMenu = static function () use ($items, $isActiveLink): void { foreach ($items as $permission=>$item) { if (!can($permission)) continue; ?>
<a class="nav-link text-white sidebar-nav-link <?= $isActiveLink($permission)?'active':'' ?>" href="<?= $item[2] ?>"><i class="bi bi-<?= $item[1] ?>"></i><span><?= $item[0] ?></span></a><?php } if (isAdmin()) { ?>
<a class="nav-link text-white sidebar-nav-link <?= $isActiveLink('user')?'active':'' ?>" href="<?= BASE_URL ?>?controller=user"><i class="bi bi-person-gear"></i><span>Perfis e permissões</span></a><?php } ?>
<a class="nav-link nav-link-logout text-warning mt-2 sidebar-nav-link" href="<?= BASE_URL ?>?controller=auth&action=logout"><i class="bi bi-box-arrow-right"></i><span>Terminar sessão</span></a><?php };
?>
<div class="app-shell d-flex" id="app-shell">
<?php if(isLoggedIn()): ?><aside class="sidebar text-white p-3 d-none d-lg-flex flex-column"><h5 class="mb-4 sidebar-brand"><i class="bi bi-mic-fill"></i> <span><?= APP_NAME ?></span></h5><div class="sidebar-user mb-3"><p class="small mb-1">Olá, <?= htmlspecialchars($user['name']) ?></p><span class="badge bg-light text-dark"><?= htmlspecialchars($user['profile_type']??$user['role']) ?></span></div><nav class="nav flex-column gap-1 sidebar-nav"><?php $renderMenu(); ?></nav></aside><?php endif; ?>
<main class="content flex-grow-1">
<?php if(isLoggedIn()): ?><header class="topbar d-flex d-lg-none align-items-center justify-content-between px-3 py-2 sticky-top"><button class="btn btn-sm btn-outline-light" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu">☰</button><strong>🎤 <?= APP_NAME ?></strong></header><div class="offcanvas offcanvas-start text-bg-dark d-lg-none" id="mobileMenu"><div class="offcanvas-header"><h5>Menu</h5><button class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button></div><div class="offcanvas-body"><nav class="nav flex-column gap-2"><?php $renderMenu(); ?></nav></div></div><?php endif; ?>
        <section class="content-body p-3 p-md-4 p-xl-5">
        <?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <?php if ($msg = flash('error')): ?><div class="alert alert-danger"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
