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
        $config = $this->captchaConfiguration();
        foreach (['recaptcha_site_key', 'recaptcha_secret_key', 'recaptcha_mode', 'recaptcha_min_score', 'recaptcha_allowed_hosts'] as $key) {
            if (isset($data[$key]) && !is_string($data[$key])) {
                throw new InvalidArgumentException('As chaves reCAPTCHA devem ser texto.');
            }
        }
        $newSite = trim($data['recaptcha_site_key'] ?? '');
        $newSecret = trim($data['recaptcha_secret_key'] ?? '');
        $mode = $data['recaptcha_mode'] ?? $config['mode'];
        $score = $data['recaptcha_min_score'] ?? (string)$config['score'];
        $hosts = $data['recaptcha_allowed_hosts'] ?? implode(',', $config['hosts']);
        if (!in_array($mode, ['v2', 'v3'], true) || !is_numeric($score) || (float)$score < 0.1 || (float)$score > 0.9) {
            throw new InvalidArgumentException('Escolhe reCAPTCHA v2 ou v3 e uma confiança mínima entre 0.1 e 0.9.');
        }
        $hosts = array_values(array_unique(array_filter(preg_split('/[\s,]+/', strtolower(trim($hosts))))));
        if (!$hosts) {
            throw new InvalidArgumentException('Indica pelo menos um domínio autorizado para o CAPTCHA.');
        }
        foreach ($hosts as $host) {
            if (!filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                throw new InvalidArgumentException('Indica apenas domínios, sem https:// nem caminhos.');
            }
        }
        if ($mode !== $config['mode'] && ($newSite === '' || $newSecret === '')) {
            throw new InvalidArgumentException('Ao mudar o tipo de CAPTCHA, indica as duas novas chaves desse tipo.');
        }
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
            throw new InvalidArgumentException('Configura as duas chaves reCAPTCHA do tipo selecionado: site e secreta.');
        }
        $this->db->beginTransaction();
        try {
            $this->set('recaptcha_site_key', $site);
            $this->set('recaptcha_secret_key', $secret);
            $this->set('recaptcha_mode', $mode);
            $this->set('recaptcha_min_score', (string)(float)$score);
            $this->set('recaptcha_allowed_hosts', implode(',', $hosts));
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
        return ['site' => $site, 'secret' => $secret];
    }

    public function captchaConfiguration(): array
    {
        return [
            'mode' => $this->get('recaptcha_mode', 'v2'),
            'score' => (float)$this->get('recaptcha_min_score', '0.5'),
            'hosts' => array_values(array_filter(preg_split('/[\s,]+/', strtolower((string)$this->get('recaptcha_allowed_hosts', 'chorarderir.com,www.chorarderir.com'))))),
        ];
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
