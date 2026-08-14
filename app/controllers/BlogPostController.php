<?php

class BlogPostController extends BaseController
{
    public function index(): void
    {
        requireAdmin();
        $posts = (new BlogPost($this->db))->all();
        $this->render('blog_posts/index', compact('posts'));
    }

    public function create(): void
    {
        requireAdmin();
        $this->render('blog_posts/form', ['post' => null]);
    }

    public function store(): void
    {
        requireAdmin();
        (new BlogPost($this->db))->create($this->validatedData());
        flash('success', 'Post criado com sucesso. Publica novamente o website para atualizar o blog público.');
        $this->redirect(BASE_URL . '?controller=blogpost&action=index');
    }

    public function edit(): void
    {
        requireAdmin();
        $post = (new BlogPost($this->db))->find((int)($_GET['id'] ?? 0));
        if (!$post) {
            flash('error', 'Post não encontrado.');
            $this->redirect(BASE_URL . '?controller=blogpost&action=index');
        }
        $this->render('blog_posts/form', compact('post'));
    }

    public function update(): void
    {
        requireAdmin();
        (new BlogPost($this->db))->update((int)($_GET['id'] ?? 0), $this->validatedData());
        flash('success', 'Post atualizado. Publica novamente o website para atualizar o blog público.');
        $this->redirect(BASE_URL . '?controller=blogpost&action=index');
    }

    public function delete(): void
    {
        requireAdmin();
        (new BlogPost($this->db))->delete((int)($_GET['id'] ?? 0));
        flash('success', 'Post eliminado.');
        $this->redirect(BASE_URL . '?controller=blogpost&action=index');
    }

    private function validatedData(): array
    {
        $title = trim((string)($_POST['title'] ?? ''));
        $slug = $this->slugify(trim((string)($_POST['slug'] ?? '')) ?: $title);
        if ($title === '' || $slug === '') {
            flash('error', 'Título e slug são obrigatórios.');
            $this->redirect(BASE_URL . '?controller=blogpost&action=index');
        }

        return [
            'title' => $title,
            'slug' => $slug,
            'category' => trim((string)($_POST['category'] ?? '')),
            'excerpt' => trim((string)($_POST['excerpt'] ?? '')),
            'content' => trim((string)($_POST['content'] ?? '')),
            'hero_image_url' => trim((string)($_POST['hero_image_url'] ?? '')),
            'meta_title' => trim((string)($_POST['meta_title'] ?? '')),
            'meta_description' => trim((string)($_POST['meta_description'] ?? '')),
            'show_corporate_form' => isset($_POST['show_corporate_form']) ? 1 : 0,
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
            'published_at' => trim((string)($_POST['published_at'] ?? '')),
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ];
    }

    private function slugify(string $value): string
    {
        $value = trim(function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value));
        $value = strtr($value, ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','í'=>'i','ì'=>'i','î'=>'i','ï'=>'i','ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o','ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n']);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: '';
        return trim($value, '-');
    }
}
