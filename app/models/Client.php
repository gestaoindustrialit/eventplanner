<?php

class Client
{
    public function __construct(private PDO $db)
    {
    }

    public function all(): array
    {
        return $this->db->query('SELECT * FROM clients ORDER BY name')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM clients WHERE id=:id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare('INSERT INTO clients (name, contact_person, phone, email, address, notes) VALUES (:name, :contact_person, :phone, :email, :address, :notes)');
        return $stmt->execute($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['id'] = $id;
        $stmt = $this->db->prepare('UPDATE clients SET name=:name, contact_person=:contact_person, phone=:phone, email=:email, address=:address, notes=:notes WHERE id=:id');
        return $stmt->execute($data);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM clients WHERE id=:id');
        return $stmt->execute(['id' => $id]);
    }
}
