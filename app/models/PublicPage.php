<?php

class PublicPage
{
    /** @var PDO */
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM public_pages ORDER BY sort_order ASC, title ASC');
        return $stmt->fetchAll();
    }

    public function allPublished(): array
    {
        $stmt = $this->db->query('SELECT * FROM public_pages WHERE is_published = 1 ORDER BY sort_order ASC, title ASC');
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM public_pages WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $page = $stmt->fetch();
        return $page ?: null;
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare('INSERT INTO public_pages (title, slug, excerpt, content, hero_image_url, is_published, sort_order) VALUES (:title, :slug, :excerpt, :content, :hero_image_url, :is_published, :sort_order)');
        $stmt->execute([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'excerpt' => $data['excerpt'] ?: null,
            'content' => $data['content'] ?: null,
            'hero_image_url' => $data['hero_image_url'] ?: null,
            'is_published' => $data['is_published'],
            'sort_order' => $data['sort_order'],
        ]);
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare('UPDATE public_pages SET title = :title, slug = :slug, excerpt = :excerpt, content = :content, hero_image_url = :hero_image_url, is_published = :is_published, sort_order = :sort_order WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'title' => $data['title'],
            'slug' => $data['slug'],
            'excerpt' => $data['excerpt'] ?: null,
            'content' => $data['content'] ?: null,
            'hero_image_url' => $data['hero_image_url'] ?: null,
            'is_published' => $data['is_published'],
            'sort_order' => $data['sort_order'],
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM public_pages WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
