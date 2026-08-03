<?php

function isLoggedIn(): bool
{
    return isset($_SESSION['user']);
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function isAdmin(): bool
{
    return isLoggedIn() && ($_SESSION['user']['role'] ?? '') === 'admin';
}

function availablePermissions(): array
{
    return [
        'dashboard' => 'Dashboard', 'comedian' => 'Comediantes', 'client' => 'Clientes',
        'crm' => 'CRM', 'event' => 'Eventos e alinhamentos', 'checklist' => 'Checklists',
        'reservation' => 'Reservas e admissões', 'publicpage' => 'Páginas públicas',
        'blogpost' => 'Blog', 'partner' => 'Parceiros', 'publicsite' => 'Publicar website',
        'newsletter' => 'Newsletter', 'presscontact' => 'Contactos Press',
    ];
}

function can(string $permission): bool
{
    if (isAdmin()) {
        return true;
    }
    return in_array($permission, currentUser()['permissions'] ?? [], true);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '?controller=auth&action=login');
        exit;
    }
}

function requireAdmin(): void
{
    requireLogin();
    $controller = strtolower((string)($_GET['controller'] ?? 'dashboard'));
    if (!can($controller)) {
        http_response_code(403);
        echo 'Acesso negado.';
        exit;
    }
}

function flash(string $key, ?string $message = null)
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return;
    }

    if (!empty($_SESSION['flash'][$key])) {
        $value = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $value;
    }

    return null;
}
