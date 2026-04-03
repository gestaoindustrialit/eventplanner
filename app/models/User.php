<?php

class User
{
    /** @var PDO */
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function createComedian(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => 'comedian',
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function updateComedian(int $id, array $data): bool
    {
        $params = [
            'id' => $id,
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => 'comedian',
        ];

        $sql = 'UPDATE users SET name=:name, email=:email, role=:role';
        if (!empty($data['password'])) {
            $sql .= ', password=:password';
            $params['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE id=:id';

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function allComedianUsers(): array
    {
        $stmt = $this->db->query("SELECT id, name, email FROM users WHERE role = 'comedian' ORDER BY name");
        return $stmt->fetchAll();
    }
}
