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

        if ($update->rowCount() === 0 && $this->get($settingKey) === null) {
            $insert = $this->db->prepare('INSERT INTO site_settings (setting_key, setting_value, updated_at) VALUES (:setting_key, :setting_value, CURRENT_TIMESTAMP)');
            $insert->execute([
                'setting_key' => $settingKey,
                'setting_value' => $value,
            ]);
        }
    }

    public function saveRecaptchaKeys(array $data): array
    {
        $site = trim((string)$this->get('recaptcha_site_key', ''));
        $secret = trim((string)$this->get('recaptcha_secret_key', ''));
        foreach (['recaptcha_site_key', 'recaptcha_secret_key'] as $key) {
            if (isset($data[$key]) && !is_string($data[$key])) {
                throw new InvalidArgumentException('As chaves reCAPTCHA devem ser texto.');
            }
        }
        $newSite = trim($data['recaptcha_site_key'] ?? '');
        $newSecret = trim($data['recaptcha_secret_key'] ?? '');
        if ($newSite !== '' && $newSite !== $site && $newSecret === '') {
            throw new InvalidArgumentException('Ao alterar a chave de site, indica também a chave secreta correspondente.');
        }
        $site = $newSite !== '' ? $newSite : $site;
        $secret = $newSecret !== '' ? $newSecret : $secret;
        foreach ([$site, $secret] as $value) {
            if ($value !== '' && !preg_match('/^[A-Za-z0-9_-]{20,200}$/D', $value)) {
                throw new InvalidArgumentException('Chave reCAPTCHA inválida: copia a chave completa, sem espaços.');
            }
        }
        if (($site === '') !== ($secret === '')) {
            throw new InvalidArgumentException('Configura as duas chaves reCAPTCHA v2: site e secreta.');
        }
        $this->db->beginTransaction();
        try {
            $this->set('recaptcha_site_key', $site);
            $this->set('recaptcha_secret_key', $secret);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
        return ['site' => $site, 'secret' => $secret];
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
