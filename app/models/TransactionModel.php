<?php

class TransactionModel extends Model
{
    public function create(array $data): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO transactions
            (user_id, category_id, amount, payment_method_id, description, receipt_image, transaction_date)
            VALUES (:user_id, :category_id, :amount, :payment_method_id, :description, :receipt_image, :transaction_date)'
        );

        return $stmt->execute([
            ':user_id' => $data['user_id'],
            ':category_id' => $data['category_id'],
            ':amount' => $data['amount'],
            ':payment_method_id' => $data['payment_method_id'],
            ':description' => $data['description'],
            ':receipt_image' => $data['receipt_image'],
            ':transaction_date' => $data['transaction_date'],
        ]);
    }

    public function update(int $id, int $userId, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE transactions SET
             category_id = :category_id,
             amount = :amount,
             payment_method_id = :payment_method_id,
             description = :description,
             receipt_image = :receipt_image,
             transaction_date = :transaction_date
             WHERE id = :id AND user_id = :user_id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId,
            ':category_id' => $data['category_id'],
            ':amount' => $data['amount'],
            ':payment_method_id' => $data['payment_method_id'],
            ':description' => $data['description'],
            ':receipt_image' => $data['receipt_image'],
            ':transaction_date' => $data['transaction_date'],
        ]);
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM transactions WHERE id = :id AND user_id = :user_id');
        return $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId,
        ]);
    }

    public function findById(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM transactions WHERE id = :id AND user_id = :user_id LIMIT 1');
        $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function recentByUser(int $userId, int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            'SELECT t.*, c.name AS category_name, c.icon AS category_icon, p.name AS payment_method_name
             FROM transactions t
             JOIN categories c ON c.id = t.category_id
             JOIN payment_methods p ON p.id = t.payment_method_id
             WHERE t.user_id = :user_id
             ORDER BY t.transaction_date DESC, t.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function allByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT t.*, c.name AS category_name, c.icon AS category_icon, p.name AS payment_method_name
             FROM transactions t
             JOIN categories c ON c.id = t.category_id
             JOIN payment_methods p ON p.id = t.payment_method_id
             WHERE t.user_id = :user_id
             ORDER BY t.transaction_date DESC, t.id DESC'
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function countByUser(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM transactions WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $userId]);
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    public function paginateByUser(int $userId, int $page = 1, int $perPage = 20, ?int $cursor = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $sql =
            'SELECT t.*, c.name AS category_name, c.icon AS category_icon, p.name AS payment_method_name
             FROM transactions t
             JOIN categories c ON c.id = t.category_id
             JOIN payment_methods p ON p.id = t.payment_method_id
             WHERE t.user_id = :user_id';
        $params = [':user_id' => $userId];

        if ($cursor !== null && $cursor > 0) {
            $sql .= ' AND t.id < :cursor';
            $params[':cursor'] = $cursor;
        }

        $sql .= ' ORDER BY t.transaction_date DESC, t.id DESC LIMIT :limit';
        if ($cursor === null || $cursor <= 0) {
            $sql .= ' OFFSET :offset';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        if ($cursor !== null && $cursor > 0) {
            $stmt->bindValue(':cursor', $cursor, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        if ($cursor === null || $cursor <= 0) {
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        $stmt->execute();

        $items = $stmt->fetchAll();
        $nextCursor = null;
        if (!empty($items)) {
            $last = end($items);
            $nextCursor = (int) ($last['id'] ?? 0);
            if ($nextCursor <= 0) {
                $nextCursor = null;
            }
        }

        return [
            'items' => $items,
            'next_cursor' => $nextCursor,
            'has_more' => count($items) === $perPage,
        ];
    }

    public function filteredByUser(int $userId, array $filters = []): array
    {
        $sql =
            'SELECT t.*, c.name AS category_name, c.icon AS category_icon, p.name AS payment_method_name
             FROM transactions t
             JOIN categories c ON c.id = t.category_id
             JOIN payment_methods p ON p.id = t.payment_method_id
             WHERE t.user_id = :user_id';

        $params = [':user_id' => $userId];

        if (!empty($filters['month'])) {
            $sql .= ' AND MONTH(t.transaction_date) = :month';
            $params[':month'] = (int) $filters['month'];
        }
        if (!empty($filters['year'])) {
            $sql .= ' AND YEAR(t.transaction_date) = :year';
            $params[':year'] = (int) $filters['year'];
        }
        if (!empty($filters['category_id'])) {
            $sql .= ' AND t.category_id = :category_id';
            $params[':category_id'] = (int) $filters['category_id'];
        }
        if (!empty($filters['payment_method_id'])) {
            $sql .= ' AND t.payment_method_id = :payment_method_id';
            $params[':payment_method_id'] = (int) $filters['payment_method_id'];
        }
        if (!empty($filters['start_date'])) {
            $sql .= ' AND t.transaction_date >= :start_date';
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= ' AND t.transaction_date <= :end_date';
            $params[':end_date'] = $filters['end_date'];
        }
        if (!empty($filters['search'])) {
            $sql .= ' AND (t.description LIKE :search OR c.name LIKE :search OR p.name LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql .= ' ORDER BY t.transaction_date DESC, t.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function totalToday(int $userId): float
    {
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(amount), 0) AS total FROM transactions WHERE user_id = :user_id AND transaction_date = CURDATE()');
        $stmt->execute([':user_id' => $userId]);
        return (float) $stmt->fetch()['total'];
    }

    public function totalThisMonth(int $userId, int $month, int $year): float
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(amount), 0) AS total
             FROM transactions
             WHERE user_id = :user_id
               AND MONTH(transaction_date) = :month
               AND YEAR(transaction_date) = :year'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':month' => $month,
            ':year' => $year,
        ]);
        return (float) $stmt->fetch()['total'];
    }

    public function totalBeforeMonth(int $userId, int $month, int $year): float
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(amount), 0) AS total
             FROM transactions
             WHERE user_id = :user_id
               AND (YEAR(transaction_date) < :y
                    OR (YEAR(transaction_date) = :y2 AND MONTH(transaction_date) < :m))'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':y'       => $year,
            ':y2'      => $year,
            ':m'       => $month,
        ]);
        return (float) $stmt->fetch()['total'];
    }

    public function topCategoryThisMonth(int $userId, int $month, int $year): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT c.name, SUM(t.amount) AS total
             FROM transactions t
             JOIN categories c ON c.id = t.category_id
             WHERE t.user_id = :user_id
               AND MONTH(t.transaction_date) = :month
               AND YEAR(t.transaction_date) = :year
             GROUP BY c.id, c.name
             ORDER BY total DESC
             LIMIT 1'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':month' => $month,
            ':year' => $year,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function reportByRange(int $userId, string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare(
            'SELECT t.*, c.name AS category_name, c.icon AS category_icon, p.name AS payment_method_name
             FROM transactions t
             JOIN categories c ON c.id = t.category_id
             JOIN payment_methods p ON p.id = t.payment_method_id
             WHERE t.user_id = :user_id
               AND t.transaction_date BETWEEN :start_date AND :end_date
             ORDER BY t.transaction_date ASC, t.id ASC'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
        ]);

        return $stmt->fetchAll();
    }

    public function countReportByRange(int $userId, string $startDate, string $endDate): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM transactions
             WHERE user_id = :user_id
               AND transaction_date BETWEEN :start_date AND :end_date'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
        ]);

        return (int) ($stmt->fetchColumn() ?: 0);
    }

    public function paginateReportByRange(int $userId, string $startDate, string $endDate, int $page = 1, int $perPage = 20, ?int $cursor = null): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $sql =
            'SELECT t.*, c.name AS category_name, c.icon AS category_icon, p.name AS payment_method_name
             FROM transactions t
             JOIN categories c ON c.id = t.category_id
             JOIN payment_methods p ON p.id = t.payment_method_id
             WHERE t.user_id = :user_id
               AND t.transaction_date BETWEEN :start_date AND :end_date';

        if ($cursor !== null && $cursor > 0) {
            $sql .= ' AND t.id < :cursor';
        }

        $sql .= ' ORDER BY t.transaction_date DESC, t.id DESC LIMIT :limit';
        if ($cursor === null || $cursor <= 0) {
            $sql .= ' OFFSET :offset';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':start_date', $startDate);
        $stmt->bindValue(':end_date', $endDate);
        if ($cursor !== null && $cursor > 0) {
            $stmt->bindValue(':cursor', $cursor, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        if ($cursor === null || $cursor <= 0) {
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        $stmt->execute();

        $items = $stmt->fetchAll();
        $nextCursor = null;
        if (!empty($items)) {
            $last = end($items);
            $nextCursor = (int) ($last['id'] ?? 0);
            if ($nextCursor <= 0) {
                $nextCursor = null;
            }
        }

        return [
            'items' => $items,
            'next_cursor' => $nextCursor,
            'has_more' => count($items) === $perPage,
        ];
    }

    public function chartByCategory(int $userId, string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.name, SUM(t.amount) AS total
             FROM transactions t
             JOIN categories c ON c.id = t.category_id
             WHERE t.user_id = :user_id
               AND t.transaction_date BETWEEN :start_date AND :end_date
             GROUP BY c.id, c.name
             ORDER BY total DESC'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
        ]);

        return $stmt->fetchAll();
    }

    public function chartByDay(int $userId, string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare(
            'SELECT DATE_FORMAT(transaction_date, "%d %b") AS day_label, SUM(amount) AS total
             FROM transactions
             WHERE user_id = :user_id
               AND transaction_date BETWEEN :start_date AND :end_date
             GROUP BY transaction_date
             ORDER BY transaction_date ASC'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
        ]);

        return $stmt->fetchAll();
    }

    public function monthlyTrend(int $userId, int $year): array
    {
        $stmt = $this->db->prepare(
            'SELECT MONTH(transaction_date) AS month_num, COALESCE(SUM(amount), 0) AS total
             FROM transactions
             WHERE user_id = :user_id
               AND YEAR(transaction_date) = :year
             GROUP BY MONTH(transaction_date)
             ORDER BY MONTH(transaction_date) ASC'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':year' => $year,
        ]);

        return $stmt->fetchAll();
    }

    /** Average monthly expense over the last N months with data. */
    public function avgMonthlyExpense(int $userId, int $months = 3): float
    {
        $months = max(1, (int) $months);
        $stmt   = $this->db->prepare(
            "SELECT COALESCE(AVG(monthly), 0) AS avg_expense FROM (
                SELECT SUM(amount) AS monthly
                FROM transactions
                WHERE user_id = :user_id
                GROUP BY YEAR(transaction_date), MONTH(transaction_date)
                ORDER BY YEAR(transaction_date) DESC, MONTH(transaction_date) DESC
                LIMIT $months
            ) sub"
        );
        $stmt->execute([':user_id' => $userId]);
        return (float) $stmt->fetch()['avg_expense'];
    }

    /** Monthly expense totals for the last N months, oldest-first. */
    public function monthlyExpenseLast(int $userId, int $months = 6): array
    {
        $months = max(1, (int) $months);
        $stmt   = $this->db->prepare(
            "SELECT YEAR(transaction_date) AS year, MONTH(transaction_date) AS month, SUM(amount) AS total
             FROM transactions
             WHERE user_id = :user_id
             GROUP BY YEAR(transaction_date), MONTH(transaction_date)
             ORDER BY YEAR(transaction_date) DESC, MONTH(transaction_date) DESC
             LIMIT $months"
        );
        $stmt->execute([':user_id' => $userId]);
        return array_reverse($stmt->fetchAll());
    }
}
