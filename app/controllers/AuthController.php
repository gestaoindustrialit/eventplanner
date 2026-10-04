<?php

class AuthController extends BaseController
{
    public function login(): void
    {
        if (isLoggedIn()) {
            $this->redirect(($_GET['portal'] ?? '') === 'admissions' ? BASE_PATH . '/eventos/' : BASE_URL);
        }

        if (($_GET['portal'] ?? '') === 'admissions') {
            $_SESSION['login_redirect'] = BASE_PATH . '/eventos/';
        }

        $this->render('auth/login');
    }

    public function authenticate(): void
    {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            flash('error', 'Credenciais inválidas.');
            $this->redirect(BASE_URL . '?controller=auth&action=login');
        }

        $userModel = new User($this->db);
        $user = $userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            flash('error', 'Email ou password incorretos.');
            $this->redirect(BASE_URL . '?controller=auth&action=login');
        }

        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'profile_type' => $user['profile_type'] ?? $user['role'],
            'permissions' => json_decode($user['permissions_json'] ?? '[]', true) ?: [],
        ];

        flash('success', 'Sessão iniciada com sucesso.');
        $defaultRedirect = BASE_URL;
        if (!isAdmin() && $userModel->hasAdmissionAccess((int)$user['id']) && empty($_SESSION['user']['permissions'])) {
            $defaultRedirect = BASE_PATH . '/eventos/';
        }
        $redirect = (string)($_SESSION['login_redirect'] ?? $defaultRedirect);
        unset($_SESSION['login_redirect']);
        if ($redirect === '' || preg_match('#^(?:https?:)?//#i', $redirect)) {
            $redirect = BASE_URL;
        }
        $this->redirect($redirect);
    }

    public function logout(): void
    {
        $admissionsPortal = ($_GET['portal'] ?? '') === 'admissions';
        session_destroy();
        session_start();
        flash('success', 'Sessão terminada.');
        $this->redirect(BASE_URL . '?controller=auth&action=login' . ($admissionsPortal ? '&portal=admissions' : ''));
    }
}
