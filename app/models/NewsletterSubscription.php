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
                status TEXT NOT NULL DEFAULT "active" CHECK (status IN ("active", "unsubscribed")),
                subscribed_at TEXT DEFAULT CURRENT_TIMESTAMP,
                unsubscribed_at TEXT DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )'
        );
    }
}
