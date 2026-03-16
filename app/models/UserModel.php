<?php

class UserModel extends Model
{
    public function create(string $name, string $email, string $passwordHash, string $currency = 'IDR'): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, password, currency) VALUES (:name, :email, :password, :currency)'
        );
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $passwordHash,
            ':currency' => $currency,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, name, email, currency, created_at FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function updateProfile(int $id, string $name, string $currency): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET name = :name, currency = :currency WHERE id = :id');
        return $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':currency' => $currency,
        ]);
    }
}
