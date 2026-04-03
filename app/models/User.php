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

    public function allComedianUsers(): array
    {
        $stmt = $this->db->query("SELECT id, name, email FROM users WHERE role = 'comedian' ORDER BY name");
        return $stmt->fetchAll();
    }
}
