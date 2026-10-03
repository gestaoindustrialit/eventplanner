<?php

// "Event" is also the name of a class provided by the optional PECL event
// extension. Keep the application model name specific so the application can
// run on hosts where that extension is enabled.
class EventModel
{
    /** @var PDO */
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function all(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $sql = 'SELECT e.*, c.name as client_name, s.name as series_name FROM events e LEFT JOIN clients c ON c.id = e.client_id LEFT JOIN event_series s ON s.id = e.series_id WHERE 1=1';
        $params = [];

        if ($dateFrom) {
            $sql .= ' AND e.date >= :date_from';
            $params['date_from'] = $dateFrom;
        }

        if ($dateTo) {
            $sql .= ' AND e.date <= :date_to';
            $params['date_to'] = $dateTo;
        }

        $sql .= ' ORDER BY e.date ASC, e.time ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function openEvents(): array
    {
        $stmt = $this->db->query("SELECT e.*, c.name as client_name, COALESCE(SUM(CASE WHEN r.status != 'cancelled' THEN r.tickets ELSE 0 END), 0) AS active_tickets FROM events e LEFT JOIN clients c ON c.id = e.client_id LEFT JOIN event_reservations r ON r.event_id = e.id WHERE e.date >= date('now') GROUP BY e.id ORDER BY e.date ASC, e.time ASC");
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT e.*, c.name as client_name, c.contact_person, c.phone as client_phone, c.email as client_email, c.address as client_address FROM events e LEFT JOIN clients c ON c.id=e.client_id WHERE e.id=:id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function lineup(int $eventId): array
    {
        $stmt = $this->db->prepare("SELECT ec.*, cm.name, cm.stage_name, cm.email, cm.phone FROM event_comedians ec JOIN comedians cm ON cm.id = ec.comedian_id WHERE ec.event_id=:event_id ORDER BY CASE ec.role WHEN 'host' THEN 1 WHEN 'opener' THEN 2 WHEN 'headliner' THEN 3 ELSE 4 END, cm.name");
        $stmt->execute(['event_id' => $eventId]);
        return $stmt->fetchAll();
    }

    public function scheduleItems(int $eventId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM event_schedule_items WHERE event_id = :event_id ORDER BY sort_order ASC, starts_at ASC, id ASC');
        $stmt->execute(['event_id' => $eventId]);
        return $stmt->fetchAll();
    }

    public function saveScheduleItems(int $eventId, array $items): void
    {
        $allowedTypes = ['artist', 'break', 'technical', 'doors', 'other'];
        $delete = $this->db->prepare('DELETE FROM event_schedule_items WHERE event_id = :event_id');
        $delete->execute(['event_id' => $eventId]);

        $stmt = $this->db->prepare('INSERT INTO event_schedule_items (event_id, starts_at, duration_minutes, item_type, title, responsible, notes, sort_order) VALUES (:event_id, :starts_at, :duration_minutes, :item_type, :title, :responsible, :notes, :sort_order)');

        foreach ($items as $index => $item) {
            if (empty($item['title']) || empty($item['starts_at'])) {
                continue;
            }

            $stmt->execute([
                'event_id' => $eventId,
                'starts_at' => $item['starts_at'],
                'duration_minutes' => max(1, (int)($item['duration_minutes'] ?? 15)),
                'item_type' => in_array($item['item_type'] ?? '', $allowedTypes, true) ? $item['item_type'] : 'other',
                'title' => $item['title'],
                'responsible' => $item['responsible'] ?: null,
                'notes' => $item['notes'] ?: null,
                'sort_order' => $index + 1,
            ]);
        }
    }

    public function create(array $data, array $lineup): int
    {
        $this->validateRelationsAndSlug($data);
        $stmt = $this->db->prepare('INSERT INTO events (title, slug, series_id, date, time, location, client_id, is_visible, reservations_open, reservation_capacity, admission_group, cachet_total, artist_map_link, artist_details, external_ticket_url, poster_url, notes) VALUES (:title, :slug, :series_id, :date, :time, :location, :client_id, :is_visible, :reservations_open, :reservation_capacity, :admission_group, :cachet_total, :artist_map_link, :artist_details, :external_ticket_url, :poster_url, :notes)');
        $stmt->execute($data);
        $eventId = (int)$this->db->lastInsertId();

        $this->saveLineup($eventId, $lineup);

        return $eventId;
    }

    public function update(int $id, array $data, array $lineup): bool
    {
        $this->validateRelationsAndSlug($data, $id);
        $data['id'] = $id;
        $stmt = $this->db->prepare('UPDATE events SET title=:title, slug=:slug, series_id=:series_id, date=:date, time=:time, location=:location, client_id=:client_id, is_visible=:is_visible, reservations_open=:reservations_open, reservation_capacity=:reservation_capacity, admission_group=:admission_group, cachet_total=:cachet_total, artist_map_link=:artist_map_link, artist_details=:artist_details, external_ticket_url=:external_ticket_url, poster_url=:poster_url, notes=:notes WHERE id=:id');
        $ok = $stmt->execute($data);

        $delete = $this->db->prepare('DELETE FROM event_comedians WHERE event_id=:event_id');
        $delete->execute(['event_id' => $id]);
        $this->saveLineup($id, $lineup);

        return $ok;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM events WHERE id=:id');
        return $stmt->execute(['id' => $id]);
    }

    public function setVisibility(int $id, bool $isVisible): bool
    {
        $stmt = $this->db->prepare('UPDATE events SET is_visible = :is_visible WHERE id = :id');
        return $stmt->execute([
            'id' => $id,
            'is_visible' => $isVisible ? 1 : 0,
        ]);
    }

    public function duplicate(int $id, string $newDate): ?int
    {
        $event = $this->find($id);
        if (!$event) {
            return null;
        }

        $lineup = $this->lineup($id);
        $lineupData = array_map(static function (array $member): array {
            return [
                'comedian_id' => (int)$member['comedian_id'],
                'role' => $member['role'],
                'cachet' => (float)$member['cachet'],
                'notes' => $member['notes'],
            ];
        }, $lineup);

        $data = [
            'title' => $event['title'],
            'slug' => $this->uniqueDuplicateSlug((string)$event['slug'], $newDate),
            'series_id' => $event['series_id'] ? (int)$event['series_id'] : null,
            'date' => $newDate,
            'time' => $event['time'],
            'location' => $event['location'],
            'client_id' => (int)$event['client_id'],
            'is_visible' => (int)($event['is_visible'] ?? 1),
            'reservations_open' => (int)($event['reservations_open'] ?? 1),
            'reservation_capacity' => max(0, (int)($event['reservation_capacity'] ?? 0)),
            'admission_group' => $event['admission_group'] ?? null,
            'cachet_total' => (float)$event['cachet_total'],
            'artist_map_link' => $event['artist_map_link'],
            'artist_details' => $event['artist_details'],
            'external_ticket_url' => $event['external_ticket_url'] ?? null,
            'poster_url' => $event['poster_url'],
            'notes' => $event['notes'],
        ];

        $newEventId = $this->create($data, $lineupData);
        $this->duplicateScheduleItems($id, $newEventId);

        return $newEventId;
    }

    public function upcomingCount(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) AS total FROM events WHERE date >= date('now')");
        return (int)$stmt->fetch()['total'];
    }

    public function totalCount(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) AS total FROM events');
        return (int)$stmt->fetch()['total'];
    }

    public function totalCachet(): float
    {
        $stmt = $this->db->query('SELECT COALESCE(SUM(cachet_total), 0) AS total FROM events');
        return (float)$stmt->fetch()['total'];
    }

    public function forComedian(int $comedianId): array
    {
        $stmt = $this->db->prepare('SELECT e.*, ec.role, ec.cachet FROM events e JOIN event_comedians ec ON ec.event_id = e.id WHERE ec.comedian_id = :comedian_id ORDER BY e.date ASC, e.time ASC');
        $stmt->execute(['comedian_id' => $comedianId]);
        return $stmt->fetchAll();
    }

    private function saveLineup(int $eventId, array $lineup): void
    {
        $stmt = $this->db->prepare('INSERT INTO event_comedians (event_id, comedian_id, role, cachet, notes) VALUES (:event_id, :comedian_id, :role, :cachet, :notes)');

        foreach ($lineup as $member) {
            if (empty($member['comedian_id'])) {
                continue;
            }

            $stmt->execute([
                'event_id' => $eventId,
                'comedian_id' => (int)$member['comedian_id'],
                'role' => $member['role'] ?: 'opener',
                'cachet' => (float)($member['cachet'] ?? 0),
                'notes' => $member['notes'] ?? null,
            ]);
        }
    }

    private function duplicateScheduleItems(int $sourceEventId, int $targetEventId): void
    {
        $items = $this->scheduleItems($sourceEventId);
        if (!$items) {
            return;
        }

        $stmt = $this->db->prepare('INSERT INTO event_schedule_items (event_id, starts_at, duration_minutes, item_type, title, responsible, notes, sort_order) VALUES (:event_id, :starts_at, :duration_minutes, :item_type, :title, :responsible, :notes, :sort_order)');

        foreach ($items as $item) {
            $stmt->execute([
                'event_id' => $targetEventId,
                'starts_at' => $item['starts_at'],
                'duration_minutes' => (int)$item['duration_minutes'],
                'item_type' => $item['item_type'],
                'title' => $item['title'],
                'responsible' => $item['responsible'],
                'notes' => $item['notes'],
                'sort_order' => (int)$item['sort_order'],
            ]);
        }
    }

    private function validateRelationsAndSlug(array $data, ?int $id = null): void
    {
        $slug = trim((string)($data['slug'] ?? ''));
        if ($slug === '') {
            throw new InvalidArgumentException('O slug do evento é obrigatório.');
        }
        $seriesId = (int)($data['series_id'] ?? 0);
        if ($seriesId > 0) {
            $series = $this->db->prepare('SELECT id FROM event_series WHERE id=:id LIMIT 1');
            $series->execute(['id' => $seriesId]);
            if (!$series->fetchColumn()) {
                throw new InvalidArgumentException('A série selecionada não existe.');
            }
        }
        $collision = $this->db->prepare('SELECT id FROM events WHERE slug=:slug AND (:id IS NULL OR id != :id) UNION ALL SELECT id FROM event_series WHERE slug=:slug AND (:series_id = 0 OR id != :series_id) LIMIT 1');
        $collision->execute(['slug' => $slug, 'id' => $id, 'series_id' => $seriesId]);
        if ($collision->fetchColumn()) {
            throw new InvalidArgumentException('Este slug já está a ser utilizado por outro evento ou série.');
        }
    }

    private function uniqueDuplicateSlug(string $slug, string $date): string
    {
        $base = rtrim($slug, '-') . '-' . date('d-m-Y', strtotime($date));
        $candidate = $base;
        $suffix = 2;
        $stmt = $this->db->prepare('SELECT 1 FROM events WHERE slug=:slug UNION ALL SELECT 1 FROM event_series WHERE slug=:slug LIMIT 1');
        do {
            $stmt->execute(['slug' => $candidate]);
            if (!$stmt->fetchColumn()) return $candidate;
            $candidate = $base . '-' . $suffix++;
        } while (true);
    }
}
