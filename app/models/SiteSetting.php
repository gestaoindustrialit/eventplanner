<?php

class SiteSetting
{
    /** @var PDO */
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->ensureTable();
    }

    public function get(string $settingKey, ?string $defaultValue = null): ?string
    {
        $stmt = $this->db->prepare('SELECT setting_value FROM site_settings WHERE setting_key = :setting_key LIMIT 1');
        $stmt->execute(['setting_key' => $settingKey]);
        $row = $stmt->fetch();

        return $row['setting_value'] ?? $defaultValue;
    }

    public function set(string $settingKey, string $value): void
    {
        $update = $this->db->prepare('UPDATE site_settings SET setting_value = :setting_value, updated_at = CURRENT_TIMESTAMP WHERE setting_key = :setting_key');
        $update->execute([
            'setting_key' => $settingKey,
            'setting_value' => $value,
        ]);

        if ($update->rowCount() === 0) {
            $insert = $this->db->prepare('INSERT INTO site_settings (setting_key, setting_value, updated_at) VALUES (:setting_key, :setting_value, CURRENT_TIMESTAMP)');
            $insert->execute([
                'setting_key' => $settingKey,
                'setting_value' => $value,
            ]);
        }
    }

    private function ensureTable(): void
    {
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS site_settings (
                setting_key TEXT PRIMARY KEY,
                setting_value TEXT NOT NULL,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )'
        );
    }
}
