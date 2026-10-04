<?php

class NewsletterSubscription
{
    /** @var PDO */
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->ensureTable();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO newsletter_subscriptions (email, name, gdpr_consent, consent_text, source, status) VALUES (:email, :name, :gdpr_consent, :consent_text, :source, :status)');
        $stmt->execute([
            'email' => trim((string)$data['email']),
            'name' => trim((string)($data['name'] ?? '')) ?: null,
            'gdpr_consent' => (int)($data['gdpr_consent'] ?? 1),
            'consent_text' => trim((string)($data['consent_text'] ?? '')),
            'source' => trim((string)($data['source'] ?? 'website')),
            'status' => trim((string)($data['status'] ?? 'active')),
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM newsletter_subscriptions ORDER BY created_at DESC, id DESC');
        return $stmt->fetchAll();
    }

    public function subscribeFromReservation(array $data): void
    {
        $values = [
            'email' => trim((string)$data['email']),
            'name' => trim((string)($data['name'] ?? '')) ?: null,
            'consent_text' => trim((string)($data['consent_text'] ?? '')),
            'source' => trim((string)($data['source'] ?? 'reserva')),
            'segment' => trim((string)($data['segment'] ?? 'Sem cidade')),
        ];

        // Some shared hosts still run SQLite versions older than 3.24, which
        // do not support the "ON CONFLICT ... DO UPDATE" upsert syntax.
        $find = $this->db->prepare('SELECT id FROM newsletter_subscriptions WHERE email = :email LIMIT 1');
        $find->execute(['email' => $values['email']]);
        $subscriptionId = $find->fetchColumn();

        if ($subscriptionId !== false) {
            $update = $this->db->prepare(
                'UPDATE newsletter_subscriptions
                 SET name = COALESCE(:name, name),
                     gdpr_consent = 1,
                     consent_text = :consent_text,
                     source = :source,
                     segment = :segment,
                     status = \'active\',
                     unsubscribed_at = NULL
                 WHERE id = :id'
            );
            $update->execute([
                'id' => (int)$subscriptionId,
                'name' => $values['name'],
                'consent_text' => $values['consent_text'],
                'source' => $values['source'],
                'segment' => $values['segment'],
            ]);
            return;
        }

        $insert = $this->db->prepare(
            'INSERT INTO newsletter_subscriptions (email, name, gdpr_consent, consent_text, source, segment, status)
             VALUES (:email, :name, 1, :consent_text, :source, :segment, \'active\')'
        );
        $insert->execute($values);
    }

    public function deactivate(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE newsletter_subscriptions SET status = 'unsubscribed', unsubscribed_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    private function ensureTable(): void
    {
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS newsletter_subscriptions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL UNIQUE,
                name TEXT DEFAULT NULL,
                gdpr_consent INTEGER NOT NULL DEFAULT 0,
                consent_text TEXT NOT NULL,
                source TEXT DEFAULT NULL,
                segment TEXT DEFAULT NULL,
                status TEXT NOT NULL DEFAULT "active" CHECK (status IN ("active", "unsubscribed")),
                subscribed_at TEXT DEFAULT CURRENT_TIMESTAMP,
                unsubscribed_at TEXT DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )'
        );
        $columns = array_column($this->db->query('PRAGMA table_info(newsletter_subscriptions)')->fetchAll(), 'name');
        if (!in_array('segment', $columns, true)) {
            $this->db->exec('ALTER TABLE newsletter_subscriptions ADD COLUMN segment TEXT DEFAULT NULL');
        }
    }
}
