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

    public function all(): array
    {
        return $this->db->query('SELECT id, name, email, role, profile_type, permissions_json, created_at FROM users ORDER BY name')->fetchAll();
    }

    public function createProfile(array $data): int
    {
        $stmt = $this->db->prepare('INSERT INTO users (name,email,password,role,profile_type,permissions_json) VALUES (:name,:email,:password,\'comedian\',:profile_type,:permissions_json)');
        $stmt->execute(['name'=>$data['name'], 'email'=>$data['email'], 'password'=>password_hash($data['password'], PASSWORD_DEFAULT), 'profile_type'=>$data['profile_type'], 'permissions_json'=>json_encode($data['permissions'])]);
        $id = (int)$this->db->lastInsertId();
        $this->saveAdmissionAccess($id, $data['admission_event_ids'], $data['admission_groups']);
        return $id;
    }

    public function updateProfile(int $id, array $data): bool
    {
        $params = ['id'=>$id, 'name'=>$data['name'], 'email'=>$data['email'], 'profile_type'=>$data['profile_type'], 'permissions_json'=>json_encode($data['permissions'])];
        $passwordSql = '';
        if ($data['password'] !== '') { $passwordSql = ', password=:password'; $params['password'] = password_hash($data['password'], PASSWORD_DEFAULT); }
        $ok = $this->db->prepare('UPDATE users SET name=:name,email=:email,profile_type=:profile_type,permissions_json=:permissions_json'.$passwordSql.' WHERE id=:id AND role<>\'admin\'')->execute($params);
        if ($ok) {
            $this->saveAdmissionAccess($id, $data['admission_event_ids'], $data['admission_groups']);
        }
        return $ok;
    }

    public function deleteProfile(int $id): bool
    {
        return $this->db->prepare("DELETE FROM users WHERE id=:id AND role<>'admin'")->execute(['id'=>$id]);
    }

    public function admissionAccess(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT event_id, event_group FROM user_admission_access WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        $rows = $stmt->fetchAll();
        return [
            'event_ids' => array_values(array_map('intval', array_filter(array_column($rows, 'event_id')))),
            'groups' => array_values(array_filter(array_column($rows, 'event_group'))),
        ];
    }

    public function hasAdmissionAccess(int $userId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM user_admission_access WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        return (bool)$stmt->fetchColumn();
    }

    private function saveAdmissionAccess(int $userId, array $eventIds, array $groups): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare('DELETE FROM user_admission_access WHERE user_id = :user_id')->execute(['user_id' => $userId]);
            $eventStmt = $this->db->prepare('INSERT INTO user_admission_access (user_id, event_id) SELECT :user_id, id FROM events WHERE id = :event_id');
            foreach (array_unique(array_map('intval', $eventIds)) as $eventId) {
                if ($eventId > 0) {
                    $eventStmt->execute(['user_id' => $userId, 'event_id' => $eventId]);
                }
            }
            $groupStmt = $this->db->prepare('INSERT INTO user_admission_access (user_id, event_group) VALUES (:user_id, :event_group)');
            foreach (array_unique(array_filter(array_map('trim', $groups))) as $group) {
                $groupStmt->execute(['user_id' => $userId, 'event_group' => $group]);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
