<?php

class EventSeries
{
    private $db;

    public function __construct(PDO $db) { $this->db = $db; }

    public function all(bool $activeOnly = false): array
    {
        $sql = 'SELECT s.*, COUNT(e.id) AS event_count FROM event_series s LEFT JOIN events e ON e.series_id = s.id';
        if ($activeOnly) { $sql .= ' WHERE s.is_active = 1'; }
        $sql .= ' GROUP BY s.id ORDER BY s.name ASC';
        return $this->db->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM event_series WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function events(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM events WHERE series_id = :id ORDER BY date ASC, time ASC');
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }

    public function save(?int $id, array $data): int
    {
        if ($id) {
            $data['id'] = $id;
            $stmt = $this->db->prepare('UPDATE event_series SET name=:name, slug=:slug, location=:location, description=:description, cover_image_url=:cover_image_url, is_active=:is_active, updated_at=CURRENT_TIMESTAMP WHERE id=:id');
            $stmt->execute($data);
            return $id;
        }
        $stmt = $this->db->prepare('INSERT INTO event_series (name, slug, location, description, cover_image_url, is_active) VALUES (:name,:slug,:location,:description,:cover_image_url,:is_active)');
        $stmt->execute($data);
        return (int)$this->db->lastInsertId();
    }

    public function slugAvailable(string $slug, ?int $ignoreSeriesId = null): bool
    {
        $sql = 'SELECT 1 FROM event_series WHERE slug=:slug' . ($ignoreSeriesId ? ' AND id != :id' : '') . ' UNION SELECT 1 FROM events WHERE slug=:slug' . ($ignoreSeriesId ? ' AND (series_id IS NULL OR series_id != :id)' : '') . ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $params = ['slug' => $slug];
        if ($ignoreSeriesId) { $params['id'] = $ignoreSeriesId; }
        $stmt->execute($params);
        return !$stmt->fetchColumn();
    }
}
