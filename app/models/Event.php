<?php

class Event
{
    public function __construct(private PDO $db)
    {
    }

    public function all(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $sql = 'SELECT e.*, c.name as client_name FROM events e LEFT JOIN clients c ON c.id = e.client_id WHERE 1=1';
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

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT e.*, c.name as client_name, c.contact_person, c.phone as client_phone, c.email as client_email, c.address as client_address FROM events e LEFT JOIN clients c ON c.id=e.client_id WHERE e.id=:id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function lineup(int $eventId): array
    {
        $stmt = $this->db->prepare('SELECT ec.*, cm.name, cm.stage_name FROM event_comedians ec JOIN comedians cm ON cm.id = ec.comedian_id WHERE ec.event_id=:event_id ORDER BY FIELD(ec.role, "host", "opener", "headliner"), cm.name');
        $stmt->execute(['event_id' => $eventId]);
        return $stmt->fetchAll();
    }

    public function create(array $data, array $lineup): int
    {
        $stmt = $this->db->prepare('INSERT INTO events (title, date, time, location, client_id, cachet_total, notes) VALUES (:title, :date, :time, :location, :client_id, :cachet_total, :notes)');
        $stmt->execute($data);
        $eventId = (int)$this->db->lastInsertId();

        $this->saveLineup($eventId, $lineup);

        return $eventId;
    }

    public function update(int $id, array $data, array $lineup): bool
    {
        $data['id'] = $id;
        $stmt = $this->db->prepare('UPDATE events SET title=:title, date=:date, time=:time, location=:location, client_id=:client_id, cachet_total=:cachet_total, notes=:notes WHERE id=:id');
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

    public function upcomingCount(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) AS total FROM events WHERE date >= CURDATE()');
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
}
