<?php

class EventSeries
{
    /** @var PDO */
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function all(bool $activeOnly = false): array
    {
        $sql = 'SELECT s.*, COUNT(e.id) AS event_count FROM event_series s LEFT JOIN events e ON e.series_id = s.id';
        if ($activeOnly) {
            $sql .= ' WHERE s.is_active = 1';
        }
        $sql .= ' GROUP BY s.id ORDER BY s.name ASC';
        return $this->db->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM event_series WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function sessions(int $id): array
    {
        $stmt = $this->db->prepare('SELECT e.*, c.name AS client_name FROM events e LEFT JOIN clients c ON c.id = e.client_id WHERE e.series_id = :id ORDER BY e.date ASC, e.time ASC');
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }

    public function save(array $data, ?int $id = null): int
    {
        $this->assertSlugAvailable($data['slug'], $id);
        if ($id !== null) {
            $data['id'] = $id;
            $stmt = $this->db->prepare('UPDATE event_series SET name=:name, slug=:slug, location=:location, description=:description, cover_image_url=:cover_image_url, is_active=:is_active, updated_at=CURRENT_TIMESTAMP WHERE id=:id');
            $stmt->execute($data);
            return $id;
        }
        $stmt = $this->db->prepare('INSERT INTO event_series (name, slug, location, description, cover_image_url, is_active) VALUES (:name, :slug, :location, :description, :cover_image_url, :is_active)');
        $stmt->execute($data);
        return (int)$this->db->lastInsertId();
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = $this->db->prepare('UPDATE event_series SET is_active=:active, updated_at=CURRENT_TIMESTAMP WHERE id=:id');
        $stmt->execute(['active' => $active ? 1 : 0, 'id' => $id]);
    }

    public function assertSlugAvailable(string $slug, ?int $seriesId = null): void
    {
        $stmt = $this->db->prepare('SELECT id FROM event_series WHERE slug=:slug AND (:id IS NULL OR id != :id) UNION ALL SELECT id FROM events WHERE slug=:slug AND (:id IS NULL OR series_id IS NULL OR series_id != :id) LIMIT 1');
        $stmt->execute(['slug' => $slug, 'id' => $seriesId]);
        if ($stmt->fetchColumn()) {
            throw new InvalidArgumentException('Este slug já está a ser utilizado por outra série ou evento.');
        }
    }

    public static function slugify(string $value): string
    {
        $value = strtolower(trim(strtr($value, ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i','ó'=>'o','õ'=>'o','ô'=>'o','ú'=>'u','ç'=>'c'])));
        return trim((string)preg_replace('/[^a-z0-9]+/', '-', $value), '-');
    }
}
