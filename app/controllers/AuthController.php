<?php

class AuthController extends BaseController
{
    public function login(): void
    {
        if (isLoggedIn()) {
            $this->redirect(BASE_URL);
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
        $this->redirect(BASE_URL);
    }

    public function logout(): void
    {
        session_destroy();
        session_start();
        flash('success', 'Sessão terminada.');
        $this->redirect(BASE_URL . '?controller=auth&action=login');
    }
}
