<?php $user = currentUser(); ?>
<!doctype html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>Admissões · <?= APP_NAME ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_PATH ?>/assets/branding/chorarderir-logo.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/app.css">
</head>
<body class="admissions-portal-body">
<header class="admissions-portal-bar">
    <a class="admissions-portal-brand" href="<?= BASE_PATH ?>/eventos/" aria-label="Página de admissões"><i class="bi bi-mic-fill"></i> <?= APP_NAME ?></a>
    <div class="admissions-portal-account">
        <span><i class="bi bi-person-circle"></i> <?= htmlspecialchars((string)($user['name'] ?? '')) ?></span>
        <a class="btn btn-sm btn-outline-light" href="<?= BASE_URL ?>?controller=auth&amp;action=logout&amp;portal=admissions"><i class="bi bi-box-arrow-right"></i> Terminar sessão</a>
    </div>
</header>
<main class="admissions-portal-main">
<?php if ($msg = flash('success')): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($msg = flash('error')): ?><div class="alert alert-danger"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
