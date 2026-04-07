<?php

class Reservation
{
    /** @var PDO */
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->ensureTicketTable();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO event_reservations (event_id, customer_name, customer_email, customer_phone, tickets, notes, status) VALUES (:event_id, :customer_name, :customer_email, :customer_phone, :tickets, :notes, :status)');
        $tickets = max(1, (int)($data['tickets'] ?? 1));
        $stmt->execute([
            'event_id' => (int)$data['event_id'],
            'customer_name' => trim((string)$data['customer_name']),
            'customer_email' => trim((string)$data['customer_email']),
            'customer_phone' => trim((string)($data['customer_phone'] ?? '')),
            'tickets' => $tickets,
            'notes' => trim((string)($data['notes'] ?? '')) ?: null,
            'status' => $data['status'] ?? 'new',
        ]);

        $reservationId = (int)$this->db->lastInsertId();
        $eventId = (int)$data['event_id'];
        $validationBaseUrl = trim((string)($data['validation_base_url'] ?? ''));
        $this->createTicketsForReservation($reservationId, $eventId, $tickets, $validationBaseUrl);

        return $reservationId;
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT r.*, e.title as event_title, e.date as event_date, e.time as event_time, e.poster_url as event_poster_url, COALESCE(t.total_tickets, 0) as generated_tickets, COALESCE(t.used_tickets, 0) as used_tickets FROM event_reservations r JOIN events e ON e.id = r.event_id LEFT JOIN (
            SELECT reservation_id, COUNT(*) as total_tickets, SUM(CASE WHEN is_used = 1 THEN 1 ELSE 0 END) as used_tickets
            FROM event_reservation_tickets
            GROUP BY reservation_id
        ) t ON t.reservation_id = r.id ORDER BY r.created_at DESC, r.id DESC');
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT r.*, e.title as event_title, e.date as event_date, e.time as event_time, e.poster_url as event_poster_url FROM event_reservations r JOIN events e ON e.id = r.event_id WHERE r.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function eventOverview(): array
    {
        $sql = "SELECT
                    e.id,
                    e.title,
                    e.date,
                    e.time,
                    e.reservations_open,
                    e.reservation_capacity,
                    COALESCE(SUM(CASE WHEN r.status != 'cancelled' THEN r.tickets ELSE 0 END), 0) AS active_tickets,
                    COALESCE(SUM(CASE WHEN r.status = 'confirmed' THEN r.tickets ELSE 0 END), 0) AS confirmed_tickets,
                    COALESCE(SUM(CASE WHEN r.status = 'new' THEN r.tickets ELSE 0 END), 0) AS new_tickets
                FROM events e
                LEFT JOIN event_reservations r ON r.event_id = e.id
                GROUP BY e.id
                ORDER BY e.date ASC, e.time ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function updateEventAvailability(int $eventId, bool $open, int $capacity): void
    {
        $stmt = $this->db->prepare('UPDATE events SET reservations_open = :reservations_open, reservation_capacity = :reservation_capacity WHERE id = :id');
        $stmt->execute([
            'reservations_open' => $open ? 1 : 0,
            'reservation_capacity' => max(0, $capacity),
            'id' => $eventId,
        ]);
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

    public function updateReservation(int $id, array $data): void
    {
        $reservation = $this->find($id);
        if (!$reservation) {
            return;
        }

        $tickets = max(1, (int)($data['tickets'] ?? 1));
        $status = (string)($data['status'] ?? $reservation['status']);
        $allowed = ['new', 'confirmed', 'cancelled'];
        $safeStatus = in_array($status, $allowed, true) ? $status : 'new';

        $stmt = $this->db->prepare('UPDATE event_reservations
            SET customer_name = :customer_name,
                customer_email = :customer_email,
                customer_phone = :customer_phone,
                tickets = :tickets,
                notes = :notes,
                status = :status
            WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'customer_name' => trim((string)($data['customer_name'] ?? '')),
            'customer_email' => trim((string)($data['customer_email'] ?? '')),
            'customer_phone' => trim((string)($data['customer_phone'] ?? '')),
            'tickets' => $tickets,
            'notes' => trim((string)($data['notes'] ?? '')) ?: null,
            'status' => $safeStatus,
        ]);

        $validationBaseUrl = trim((string)($data['validation_base_url'] ?? ''));
        $this->syncReservationTickets($id, (int)$reservation['event_id'], $tickets, $validationBaseUrl);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM event_reservations WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function ticketsByReservation(int $reservationId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM event_reservation_tickets WHERE reservation_id = :reservation_id ORDER BY ticket_no ASC, id ASC');
        $stmt->execute(['reservation_id' => $reservationId]);
        return $stmt->fetchAll();
    }

    public function validateTicket(string $token, int $validatorUserId): array
    {
        $token = $this->extractToken($token);
        if ($token === '') {
            return ['ok' => false, 'reason' => 'empty'];
        }

        $stmt = $this->db->prepare('SELECT t.*, r.status, r.customer_name, e.title as event_title, e.date as event_date, e.time as event_time
            FROM event_reservation_tickets t
            JOIN event_reservations r ON r.id = t.reservation_id
            JOIN events e ON e.id = t.event_id
            WHERE t.ticket_token = :token
            LIMIT 1');
        $stmt->execute(['token' => $token]);
        $ticket = $stmt->fetch();

        if (!$ticket) {
            return ['ok' => false, 'reason' => 'not_found'];
        }
        if ((int)$ticket['is_used'] === 1) {
            return ['ok' => false, 'reason' => 'already_used', 'ticket' => $ticket];
        }
        if ((string)$ticket['status'] === 'cancelled') {
            return ['ok' => false, 'reason' => 'cancelled', 'ticket' => $ticket];
        }

        $update = $this->db->prepare('UPDATE event_reservation_tickets SET is_used = 1, used_at = CURRENT_TIMESTAMP, used_by_user_id = :user_id WHERE id = :id');
        $update->execute([
            'id' => (int)$ticket['id'],
            'user_id' => $validatorUserId,
        ]);

        $stmt->execute(['token' => $token]);
        $updated = $stmt->fetch();
        return ['ok' => true, 'ticket' => $updated];
    }

    public function pendingCount(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM event_reservations WHERE status = 'new'");
        return (int)$stmt->fetch()['total'];
    }

    private function syncReservationTickets(int $reservationId, int $eventId, int $tickets, string $validationBaseUrl): void
    {
        $existing = $this->ticketsByReservation($reservationId);
        $existingCount = count($existing);

        if ($existingCount < $tickets) {
            $this->createTicketsForReservation($reservationId, $eventId, $tickets - $existingCount, $validationBaseUrl, $existingCount);
            return;
        }

        if ($existingCount > $tickets) {
            $toRemove = $existingCount - $tickets;
            $removeStmt = $this->db->prepare('DELETE FROM event_reservation_tickets WHERE id = :id');
            for ($i = $existingCount - 1; $i >= 0 && $toRemove > 0; $i--) {
                if ((int)$existing[$i]['is_used'] === 1) {
                    continue;
                }
                $removeStmt->execute(['id' => (int)$existing[$i]['id']]);
                $toRemove--;
            }
        }
    }

    public function createTicketsForReservation(int $reservationId, int $eventId, int $tickets, string $validationBaseUrl, int $offset = 0): void
    {
        if ($tickets <= 0) {
            return;
        }

        $stmt = $this->db->prepare('INSERT INTO event_reservation_tickets (reservation_id, event_id, ticket_no, ticket_token, qr_payload) VALUES (:reservation_id, :event_id, :ticket_no, :ticket_token, :qr_payload)');

        for ($i = 1; $i <= $tickets; $i++) {
            $ticketNo = $offset + $i;
            $token = bin2hex(random_bytes(16));
            $payload = $validationBaseUrl !== ''
                ? $validationBaseUrl . urlencode($token)
                : 'RESERVA:' . $token;

            $stmt->execute([
                'reservation_id' => $reservationId,
                'event_id' => $eventId,
                'ticket_no' => $ticketNo,
                'ticket_token' => $token,
                'qr_payload' => $payload,
            ]);
        }
    }

    private function ensureTicketTable(): void
    {
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS event_reservation_tickets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                reservation_id INTEGER NOT NULL,
                event_id INTEGER NOT NULL,
                ticket_no INTEGER NOT NULL,
                ticket_token TEXT NOT NULL UNIQUE,
                qr_payload TEXT NOT NULL,
                is_used INTEGER NOT NULL DEFAULT 0,
                used_at TEXT DEFAULT NULL,
                used_by_user_id INTEGER DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )'
        );
    }

    private function extractToken(string $rawValue): string
    {
        $value = trim($rawValue);
        if ($value === '') {
            return '';
        }

        if (strpos($value, 'token=') !== false) {
            $query = (string)parse_url($value, PHP_URL_QUERY);
            parse_str($query, $queryParams);
            return trim((string)($queryParams['token'] ?? ''));
        }

        if (str_starts_with($value, 'RESERVA:')) {
            return trim(substr($value, 8));
        }

        return $value;
    }
}
