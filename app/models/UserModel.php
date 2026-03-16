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
        $stmt = $this->db->prepare('SELECT id, name, email, currency, status, last_login_at, created_at FROM users WHERE id = :id LIMIT 1');
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

    public function touchLastLogin(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function setStatus(int $id, string $status): bool
    {
        if (!in_array($status, ['active', 'suspended'], true)) {
            return false;
        }

        $stmt = $this->db->prepare('UPDATE users SET status = :status WHERE id = :id');
        return $stmt->execute([':id' => $id, ':status' => $status]);
    }

    public function updatePassword(int $id, string $passwordHash): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET password = :password WHERE id = :id');
        return $stmt->execute([':id' => $id, ':password' => $passwordHash]);
    }

    public function paginateForAdmin(array $filters, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(5, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $fromSql = "FROM users u
                    LEFT JOIN (
                        SELECT user_id, COUNT(*) AS tx_count, COALESCE(SUM(amount), 0) AS total_expense
                        FROM transactions
                        GROUP BY user_id
                    ) tx ON tx.user_id = u.id";

        $where = [];
        $params = [];

        $status = trim((string) ($filters['status'] ?? ''));
        if (in_array($status, ['active', 'suspended'], true)) {
            $where[] = 'u.status = :status';
            $params[':status'] = $status;
        }

        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $where[] = '(u.name LIKE :q OR u.email LIKE :q)';
            $params[':q'] = '%' . $query . '%';
        }

        $createdFrom = trim((string) ($filters['created_from'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $createdFrom) === 1) {
            $where[] = 'u.created_at >= :created_from';
            $params[':created_from'] = $createdFrom . ' 00:00:00';
        }

        $createdTo = trim((string) ($filters['created_to'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $createdTo) === 1) {
            $where[] = 'u.created_at <= :created_to';
            $params[':created_to'] = $createdTo . ' 23:59:59';
        }

        $activity = trim((string) ($filters['activity'] ?? ''));
        if ($activity === 'inactive') {
            $where[] = 'COALESCE(tx.tx_count, 0) = 0';
        } elseif ($activity === 'low') {
            $where[] = 'COALESCE(tx.tx_count, 0) BETWEEN 1 AND 10';
        } elseif ($activity === 'medium') {
            $where[] = 'COALESCE(tx.tx_count, 0) BETWEEN 11 AND 50';
        } elseif ($activity === 'high') {
            $where[] = 'COALESCE(tx.tx_count, 0) >= 51';
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countStmt = $this->db->prepare("SELECT COUNT(*) AS total $fromSql $whereSql");
        $countStmt->execute($params);
        $total = (int) ($countStmt->fetch()['total'] ?? 0);

        $sql = "SELECT u.id, u.name, u.email, u.currency, u.status, u.last_login_at, u.created_at,
                       COALESCE(tx.tx_count, 0) AS tx_count,
                       COALESCE(tx.total_expense, 0) AS total_expense
                $fromSql
                $whereSql
                ORDER BY u.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }
}
