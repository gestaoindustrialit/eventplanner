<?php

class PublicPageController extends BaseController
{
    public function index(): void
    {
        requireAdmin();
        $pages = (new PublicPage($this->db))->all();
        $this->render('public_pages/index', compact('pages'));
    }

    public function create(): void
    {
        requireAdmin();
        $this->render('public_pages/form', ['page' => null]);
    }

    public function store(): void
    {
        requireAdmin();
        (new PublicPage($this->db))->create($this->validatedData());
        flash('success', 'Página criada com sucesso.');
        $this->redirect(BASE_URL . '?controller=publicpage&action=index');
    }

    public function edit(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $page = (new PublicPage($this->db))->find($id);

        if (!$page) {
            flash('error', 'Página não encontrada.');
            $this->redirect(BASE_URL . '?controller=publicpage&action=index');
        }

        $this->render('public_pages/form', compact('page'));
    }

    public function update(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        (new PublicPage($this->db))->update($id, $this->validatedData());
        flash('success', 'Página atualizada.');
        $this->redirect(BASE_URL . '?controller=publicpage&action=index');
    }

    public function delete(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        (new PublicPage($this->db))->delete($id);
        flash('success', 'Página eliminada.');
        $this->redirect(BASE_URL . '?controller=publicpage&action=index');
    }

    private function validatedData(): array
    {
        $title = trim((string)($_POST['title'] ?? ''));
        $slug = trim((string)($_POST['slug'] ?? ''));
        $normalizedSlug = $this->slugify($slug !== '' ? $slug : $title);

        if ($title === '' || $normalizedSlug === '') {
            flash('error', 'Título e slug são obrigatórios.');
            $this->redirect(BASE_URL . '?controller=publicpage&action=index');
        }

        return [
            'title' => $title,
            'slug' => $normalizedSlug,
            'excerpt' => trim((string)($_POST['excerpt'] ?? '')),
            'content' => trim((string)($_POST['content'] ?? '')),
            'hero_image_url' => trim((string)($_POST['hero_image_url'] ?? '')),
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ];
    }

    private function slugify(string $value): string
    {
        $value = trim(mb_strtolower($value));
        $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?: '';
        return trim($value, '-');
    }
}
