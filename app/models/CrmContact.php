<?php

class CrmContact
{
    /** @var PDO */
    private $db;

    private const ALLOWED_STATUSES = [
        'novo',
        'por_contactar',
        'contactado',
        'aguardar_resposta',
        'em_negociacao',
        'proposta_enviada',
        'interessado',
        'fechado_ganho',
        'fechado_perdido',
        'sem_interesse',
        'arquivado',
    ];

    private const ALLOWED_TYPES = [
        'venda',
        'apoio',
        'parceria',
        'patrocinador',
        'media',
        'fornecedor',
        'outro',
    ];

    private const ALLOWED_MARKETS = ['nacional', 'internacional'];
    private const ALLOWED_PRIORITIES = ['baixa', 'media', 'alta', 'urgente'];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function listPaginated(array $filters, string $sortBy, string $sortDir, int $page, int $perPage): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(entity_name LIKE :search OR contact_name LIKE :search OR email LIKE :search OR city LIKE :search OR country LIKE :search OR notes LIKE :search OR observations LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        foreach (['status', 'contact_type', 'market', 'priority', 'country'] as $field) {
            if (!empty($filters[$field])) {
                $where[] = "$field = :$field";
                $params[$field] = $filters[$field];
            }
        }

        if (!empty($filters['last_contact_from'])) {
            $where[] = 'last_contact_at >= :last_contact_from';
            $params['last_contact_from'] = $filters['last_contact_from'];
        }

        if (!empty($filters['last_contact_to'])) {
            $where[] = 'last_contact_at <= :last_contact_to';
            $params['last_contact_to'] = $filters['last_contact_to'];
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

        $countStmt = $this->db->prepare('SELECT COUNT(*) FROM crm_contacts' . $whereSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $allowedSort = [
            'entity_name' => 'entity_name',
            'last_contact_at' => 'last_contact_at',
            'next_follow_up_at' => 'next_follow_up_at',
            'potential_value' => 'potential_value',
            'status' => 'status',
        ];

        $sortColumn = $allowedSort[$sortBy] ?? 'updated_at';
        $direction = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        $offset = max(0, ($page - 1) * $perPage);
        $sql = 'SELECT * FROM crm_contacts'
            . $whereSql
            . " ORDER BY {$sortColumn} {$direction}, id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'rows' => $stmt->fetchAll(),
            'total' => $total,
        ];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM crm_contacts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO crm_contacts (
                    entity_name, contact_name, email, phone, website, social_profile,
                    country, city, market, contact_type, status, priority, lead_source,
                    first_contact_at, last_contact_at, next_follow_up_at, potential_value,
                    observations, internal_notes
                ) VALUES (
                    :entity_name, :contact_name, :email, :phone, :website, :social_profile,
                    :country, :city, :market, :contact_type, :status, :priority, :lead_source,
                    :first_contact_at, :last_contact_at, :next_follow_up_at, :potential_value,
                    :observations, :internal_notes
                )';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $data['id'] = $id;

        $sql = 'UPDATE crm_contacts SET
                    entity_name = :entity_name,
                    contact_name = :contact_name,
                    email = :email,
                    phone = :phone,
                    website = :website,
                    social_profile = :social_profile,
                    country = :country,
                    city = :city,
                    market = :market,
                    contact_type = :contact_type,
                    status = :status,
                    priority = :priority,
                    lead_source = :lead_source,
                    first_contact_at = :first_contact_at,
                    last_contact_at = :last_contact_at,
                    next_follow_up_at = :next_follow_up_at,
                    potential_value = :potential_value,
                    observations = :observations,
                    internal_notes = :internal_notes,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id';

        return $this->db->prepare($sql)->execute($data);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM crm_contacts WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function archive(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE crm_contacts SET status = 'arquivado', updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function quickUpdate(int $id, array $changes): bool
    {
        $allowed = ['status', 'next_follow_up_at', 'last_contact_at'];
        $set = [];
        $params = ['id' => $id];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $changes)) {
                $set[] = "$field = :$field";
                $params[$field] = $changes[$field];
            }
        }

        if ($set === []) {
            return false;
        }

        $set[] = 'updated_at = CURRENT_TIMESTAMP';
        $sql = 'UPDATE crm_contacts SET ' . implode(', ', $set) . ' WHERE id = :id';
        return $this->db->prepare($sql)->execute($params);
    }

    public function duplicate(int $id): ?int
    {
        $contact = $this->find($id);
        if (!$contact) {
            return null;
        }

        unset($contact['id'], $contact['created_at'], $contact['updated_at']);
        $contact['entity_name'] = $contact['entity_name'] . ' (Cópia)';
        $contact['status'] = 'novo';
        $contact['last_contact_at'] = null;
        $contact['next_follow_up_at'] = null;

        return $this->create($contact);
    }

    public function statusSummary(): array
    {
        $rows = $this->db->query('SELECT status, COUNT(*) AS total FROM crm_contacts GROUP BY status')->fetchAll();
        $output = [];
        foreach ($rows as $row) {
            $output[$row['status']] = (int)$row['total'];
        }
        return $output;
    }

    public function typeSummary(): array
    {
        $rows = $this->db->query('SELECT contact_type, COUNT(*) AS total FROM crm_contacts GROUP BY contact_type')->fetchAll();
        $output = [];
        foreach ($rows as $row) {
            $output[$row['contact_type']] = (int)$row['total'];
        }
        return $output;
    }

    public function marketSummary(): array
    {
        $rows = $this->db->query('SELECT market, COUNT(*) AS total FROM crm_contacts GROUP BY market')->fetchAll();
        $output = [];
        foreach ($rows as $row) {
            $output[$row['market']] = (int)$row['total'];
        }
        return $output;
    }

    public function metrics(): array
    {
        $total = (int)$this->db->query('SELECT COUNT(*) FROM crm_contacts')->fetchColumn();
        $open = (int)$this->db->query("SELECT COUNT(*) FROM crm_contacts WHERE status NOT IN ('fechado_ganho', 'fechado_perdido', 'sem_interesse', 'arquivado')")->fetchColumn();
        $won = (int)$this->db->query("SELECT COUNT(*) FROM crm_contacts WHERE status = 'fechado_ganho'")->fetchColumn();

        return [
            'total' => $total,
            'open' => $open,
            'won' => $won,
            'status' => $this->statusSummary(),
            'types' => $this->typeSummary(),
            'markets' => $this->marketSummary(),
        ];
    }

    public static function statuses(): array
    {
        return self::ALLOWED_STATUSES;
    }

    public static function types(): array
    {
        return self::ALLOWED_TYPES;
    }

    public static function markets(): array
    {
        return self::ALLOWED_MARKETS;
    }

    public static function priorities(): array
    {
        return self::ALLOWED_PRIORITIES;
    }
}
