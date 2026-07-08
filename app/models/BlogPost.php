<?php

class BlogPost
{
    /** @var PDO */
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->ensureTable();
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM blog_posts ORDER BY sort_order ASC, published_at DESC, created_at DESC');
        return $stmt->fetchAll();
    }

    public function published(): array
    {
        $stmt = $this->db->query('SELECT * FROM blog_posts WHERE is_published = 1 ORDER BY sort_order ASC, published_at DESC, created_at DESC');
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM blog_posts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare('INSERT INTO blog_posts (title, slug, category, excerpt, content, hero_image_url, meta_title, meta_description, is_published, published_at, sort_order) VALUES (:title, :slug, :category, :excerpt, :content, :hero_image_url, :meta_title, :meta_description, :is_published, :published_at, :sort_order)');
        $stmt->execute($this->payload($data));
    }

    public function update(int $id, array $data): void
    {
        $payload = $this->payload($data);
        $payload['id'] = $id;
        $stmt = $this->db->prepare('UPDATE blog_posts SET title = :title, slug = :slug, category = :category, excerpt = :excerpt, content = :content, hero_image_url = :hero_image_url, meta_title = :meta_title, meta_description = :meta_description, is_published = :is_published, published_at = :published_at, sort_order = :sort_order, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute($payload);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM blog_posts WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function payload(array $data): array
    {
        return [
            'title' => $data['title'],
            'slug' => $data['slug'],
            'category' => $data['category'] ?: null,
            'excerpt' => $data['excerpt'] ?: null,
            'content' => $data['content'] ?: null,
            'hero_image_url' => $data['hero_image_url'] ?: null,
            'meta_title' => $data['meta_title'] ?: null,
            'meta_description' => $data['meta_description'] ?: null,
            'is_published' => (int)$data['is_published'],
            'published_at' => $data['published_at'] ?: date('Y-m-d'),
            'sort_order' => (int)$data['sort_order'],
        ];
    }

    private function ensureTable(): void
    {
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS blog_posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                category TEXT DEFAULT NULL,
                excerpt TEXT DEFAULT NULL,
                content TEXT DEFAULT NULL,
                hero_image_url TEXT DEFAULT NULL,
                meta_title TEXT DEFAULT NULL,
                meta_description TEXT DEFAULT NULL,
                is_published INTEGER NOT NULL DEFAULT 0,
                published_at TEXT DEFAULT NULL,
                sort_order INTEGER NOT NULL DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )'
        );
    }
}
