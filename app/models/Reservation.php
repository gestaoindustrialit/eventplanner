<?php

class Reservation
{
    /** @var PDO */
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO event_reservations (event_id, customer_name, customer_email, customer_phone, tickets, notes, status) VALUES (:event_id, :customer_name, :customer_email, :customer_phone, :tickets, :notes, :status)');
        $stmt->execute([
            'event_id' => (int)$data['event_id'],
            'customer_name' => trim((string)$data['customer_name']),
            'customer_email' => trim((string)$data['customer_email']),
            'customer_phone' => trim((string)($data['customer_phone'] ?? '')),
            'tickets' => max(1, (int)($data['tickets'] ?? 1)),
            'notes' => trim((string)($data['notes'] ?? '')) ?: null,
            'status' => $data['status'] ?? 'new',
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT r.*, e.title as event_title, e.date as event_date, e.time as event_time FROM event_reservations r JOIN events e ON e.id = r.event_id ORDER BY r.created_at DESC, r.id DESC');
        return $stmt->fetchAll();
    }

    public function updateStatus(int $id, string $status): void
    {
        $allowed = ['new', 'confirmed', 'cancelled'];
        $safeStatus = in_array($status, $allowed, true) ? $status : 'new';

        $stmt = $this->db->prepare('UPDATE event_reservations SET status = :status WHERE id = :id');
        $stmt->execute([
            'status' => $safeStatus,
            'id' => $id,
        ]);
    }

    public function pendingCount(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM event_reservations WHERE status = 'new'");
        return (int)$stmt->fetch()['total'];
    }
}
