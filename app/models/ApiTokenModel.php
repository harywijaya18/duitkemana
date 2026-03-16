<?php

class ApiTokenModel extends Model
{
    public function create(int $userId, string $plainToken, ?string $deviceName = null): bool
    {
        $tokenHash = hash('sha256', $plainToken);
        $stmt = $this->db->prepare(
            'INSERT INTO api_tokens (user_id, token_hash, device_name) VALUES (:user_id, :token_hash, :device_name)'
        );

        return $stmt->execute([
            ':user_id' => $userId,
            ':token_hash' => $tokenHash,
            ':device_name' => $deviceName,
        ]);
    }

    public function findUserByToken(string $plainToken): ?array
    {
        $tokenHash = hash('sha256', $plainToken);
        $stmt = $this->db->prepare(
            'SELECT u.id, u.name, u.email, u.currency
             FROM api_tokens t
             JOIN users u ON u.id = t.user_id
             WHERE t.token_hash = :token_hash
             LIMIT 1'
        );
        $stmt->execute([':token_hash' => $tokenHash]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function deleteToken(string $plainToken): bool
    {
        $tokenHash = hash('sha256', $plainToken);
        $stmt = $this->db->prepare('DELETE FROM api_tokens WHERE token_hash = :token_hash');
        return $stmt->execute([':token_hash' => $tokenHash]);
    }
}
