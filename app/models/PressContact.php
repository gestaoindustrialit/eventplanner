<?php

class PressContact
{
    /** @var PDO */
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function all(): array
    {
        return $this->db->query('SELECT * FROM press_contacts ORDER BY district, locality, name')->fetchAll();
    }

    public function filterByLocation(?string $district = null, ?string $locality = null): array
    {
        $sql = 'SELECT * FROM press_contacts WHERE email != ""';
        $params = [];

        if ($district) {
            $sql .= ' AND district = :district';
            $params['district'] = $district;
        }

        if ($locality) {
            $sql .= ' AND locality = :locality';
            $params['locality'] = $locality;
        }

        $sql .= ' ORDER BY district, locality, name';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function districts(): array
    {
        $stmt = $this->db->query('SELECT DISTINCT district FROM press_contacts WHERE district IS NOT NULL AND district != "" ORDER BY district');
        return array_map(static function (array $row): string {
            return (string)$row['district'];
        }, $stmt->fetchAll());
    }

    public function localities(?string $district = null): array
    {
        $sql = 'SELECT DISTINCT locality FROM press_contacts WHERE locality IS NOT NULL AND locality != ""';
        $params = [];

        if ($district) {
            $sql .= ' AND district = :district';
            $params['district'] = $district;
        }

        $sql .= ' ORDER BY locality';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map(static function (array $row): string {
            return (string)$row['locality'];
        }, $stmt->fetchAll());
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM press_contacts WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare('INSERT INTO press_contacts (name, email, locality, district, website) VALUES (:name, :email, :locality, :district, :website)');
        return $stmt->execute($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['id'] = $id;
        $stmt = $this->db->prepare('UPDATE press_contacts SET name = :name, email = :email, locality = :locality, district = :district, website = :website WHERE id = :id');
        return $stmt->execute($data);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM press_contacts WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
