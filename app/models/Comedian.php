<?php

class Comedian
{
    /** @var PDO */
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT c.*, u.name as user_name FROM comedians c LEFT JOIN users u ON u.id = c.user_id ORDER BY c.name');
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM comedians WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM comedians WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare('INSERT INTO comedians (name, stage_name, email, phone, instagram, notes, user_id) VALUES (:name, :stage_name, :email, :phone, :instagram, :notes, :user_id)');
        return $stmt->execute($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['id'] = $id;
        $stmt = $this->db->prepare('UPDATE comedians SET name=:name, stage_name=:stage_name, email=:email, phone=:phone, instagram=:instagram, notes=:notes, user_id=:user_id WHERE id=:id');
        return $stmt->execute($data);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM comedians WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
