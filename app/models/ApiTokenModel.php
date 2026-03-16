<?php

class ApiTokenModel extends Model
{
    public function create(int $userId, string $plainToken, ?string $deviceName = null): bool
    {
        return $this->createToken(
            $userId,
            $plainToken,
            'access',
            (new DateTimeImmutable('+7 days'))->format('Y-m-d H:i:s'),
            $deviceName,
            null
        );
    }

    public function issueTokenPair(int $userId, ?string $deviceName = null): array
    {
        $accessToken = bin2hex(random_bytes(32));
        $refreshToken = bin2hex(random_bytes(32));

        $accessExpiresAt = (new DateTimeImmutable('+1 day'))->format('Y-m-d H:i:s');
        $refreshExpiresAt = (new DateTimeImmutable('+30 days'))->format('Y-m-d H:i:s');

        $this->createToken($userId, $accessToken, 'access', $accessExpiresAt, $deviceName, null);
        $this->createToken($userId, $refreshToken, 'refresh', $refreshExpiresAt, $deviceName, null);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'access_expires_at' => $accessExpiresAt,
            'refresh_expires_at' => $refreshExpiresAt,
            'token_type' => 'Bearer',
        ];
    }

    public function refreshAccessToken(string $plainRefreshToken): ?array
    {
        $refresh = $this->findTokenRow($plainRefreshToken, 'refresh');
        if (!$refresh) {
            return null;
        }

        if (!empty($refresh['revoked_at'])) {
            return null;
        }

        if (!empty($refresh['expires_at']) && strtotime((string) $refresh['expires_at']) < time()) {
            return null;
        }

        $userId = (int) ($refresh['user_id'] ?? 0);
        if ($userId <= 0) {
            return null;
        }

        // Rotate refresh token to reduce replay risk.
        $this->revokeToken($plainRefreshToken);

        return $this->issueTokenPair($userId, $refresh['device_name'] ?? null);
    }

    public function findUserByToken(string $plainToken): ?array
    {
        return $this->findUserByTokenType($plainToken, 'access');
    }

    public function findUserByTokenType(string $plainToken, string $tokenType = 'access'): ?array
    {
        $tokenHash = hash('sha256', $plainToken);
        $stmt = $this->db->prepare(
            'SELECT u.id, u.name, u.email, u.currency
             FROM api_tokens t
             JOIN users u ON u.id = t.user_id
             WHERE t.token_hash = :token_hash
               AND t.token_type = :token_type
               AND t.revoked_at IS NULL
               AND (t.expires_at IS NULL OR t.expires_at >= NOW())
             LIMIT 1'
        );
        $stmt->execute([
            ':token_hash' => $tokenHash,
            ':token_type' => $tokenType,
        ]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function deleteToken(string $plainToken): bool
    {
        return $this->revokeToken($plainToken);
    }

    public function revokeByUser(int $userId): int
    {
        $stmt = $this->db->prepare('UPDATE api_tokens SET revoked_at = NOW() WHERE user_id = :uid AND revoked_at IS NULL');
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->rowCount();
    }

    public function countByUser(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS total FROM api_tokens WHERE user_id = :uid AND revoked_at IS NULL');
        $stmt->execute([':uid' => $userId]);
        return (int) ($stmt->fetch()['total'] ?? 0);
    }

    private function createToken(int $userId, string $plainToken, string $tokenType, string $expiresAt, ?string $deviceName, ?int $parentTokenId): bool
    {
        $tokenHash = hash('sha256', $plainToken);
        $stmt = $this->db->prepare(
            'INSERT INTO api_tokens (user_id, token_hash, token_type, expires_at, device_name, parent_token_id)
             VALUES (:user_id, :token_hash, :token_type, :expires_at, :device_name, :parent_token_id)'
        );

        return $stmt->execute([
            ':user_id' => $userId,
            ':token_hash' => $tokenHash,
            ':token_type' => $tokenType,
            ':expires_at' => $expiresAt,
            ':device_name' => $deviceName,
            ':parent_token_id' => $parentTokenId,
        ]);
    }

    private function revokeToken(string $plainToken): bool
    {
        $tokenHash = hash('sha256', $plainToken);
        $stmt = $this->db->prepare('UPDATE api_tokens SET revoked_at = NOW() WHERE token_hash = :token_hash AND revoked_at IS NULL');
        return $stmt->execute([':token_hash' => $tokenHash]);
    }

    private function findTokenRow(string $plainToken, string $tokenType): ?array
    {
        $tokenHash = hash('sha256', $plainToken);
        $stmt = $this->db->prepare(
            'SELECT *
             FROM api_tokens
             WHERE token_hash = :token_hash
               AND token_type = :token_type
             LIMIT 1'
        );
        $stmt->execute([
            ':token_hash' => $tokenHash,
            ':token_type' => $tokenType,
        ]);

        $row = $stmt->fetch();
        return $row ?: null;
    }
}
