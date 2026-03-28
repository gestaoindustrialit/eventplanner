<?php

abstract class BaseController
{
    protected PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    protected function render(string $view, array $data = []): void
    {
        extract($data);
        $viewPath = __DIR__ . '/../views/' . $view . '.php';
        include __DIR__ . '/../views/partials/header.php';
        include $viewPath;
        include __DIR__ . '/../views/partials/footer.php';
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
