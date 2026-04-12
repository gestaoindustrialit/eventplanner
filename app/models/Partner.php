<?php

class Partner
{
    /** @var PDO */
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function all(): array
    {
        return $this->db->query('SELECT * FROM partners ORDER BY sort_order ASC, company_name ASC')->fetchAll();
    }

    public function activeForPublic(): array
    {
        $sql = "SELECT * FROM partners
                WHERE date(partnership_start_date) <= date('now')
                  AND date(partnership_start_date, '+1 year') > date('now')
                ORDER BY sort_order ASC, company_name ASC";
        return $this->db->query($sql)->fetchAll() ?: [];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM partners WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO partners (company_name, logo_url, company_url, partnership_start_date, sort_order)
             VALUES (:company_name, :logo_url, :company_url, :partnership_start_date, :sort_order)'
        );
        return $stmt->execute($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['id'] = $id;
        $stmt = $this->db->prepare(
            'UPDATE partners
             SET company_name = :company_name,
                 logo_url = :logo_url,
                 company_url = :company_url,
                 partnership_start_date = :partnership_start_date,
                 sort_order = :sort_order
             WHERE id = :id'
        );
        return $stmt->execute($data);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM partners WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
