<?php

abstract class BaseController
{
    /** @var PDO */
    protected $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    protected function render(string $view, array $data = [], string $layout = 'app'): void
    {
        extract($data);
        $db = $this->db;
        $viewPath = __DIR__ . '/../views/' . $view . '.php';
        $header = $layout === 'admissions' ? 'admissions_header.php' : 'header.php';
        $footer = $layout === 'admissions' ? 'admissions_footer.php' : 'footer.php';
        include __DIR__ . '/../views/partials/' . $header;
        include $viewPath;
        include __DIR__ . '/../views/partials/' . $footer;
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
