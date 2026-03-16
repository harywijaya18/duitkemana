<?php

class AdminTransactionsModel extends Model
{
    public function snapshot(array $filters, int $page = 1, int $perPage = 25): array
    {
        $page = max(1, $page);
        $perPage = max(10, min(100, $perPage));

        $listing = $this->listTransactions($filters, $page, $perPage);

        return [
            'listing' => $listing,
            'summary' => $this->summary($filters),
            'users' => $this->users(),
            'categories' => $this->categories(),
            'payment_methods' => $this->paymentMethods(),
        ];
    }

    private function listTransactions(array $filters, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;

        [$whereSql, $params] = $this->buildWhere($filters);

        $countStmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM transactions t
             JOIN users u ON u.id = t.user_id
             JOIN categories c ON c.id = t.category_id
             JOIN payment_methods p ON p.id = t.payment_method_id
             $whereSql"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT t.id, t.user_id, t.category_id, t.payment_method_id,
                       t.amount, t.description, t.receipt_image, t.transaction_date, t.created_at,
                       u.name AS user_name, u.email AS user_email,
                       c.name AS category_name,
                       p.name AS payment_method_name
                FROM transactions t
                JOIN users u ON u.id = t.user_id
                JOIN categories c ON c.id = t.category_id
                JOIN payment_methods p ON p.id = t.payment_method_id
                $whereSql
                ORDER BY t.transaction_date DESC, t.id DESC
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

    private function summary(array $filters): array
    {
        [$whereSql, $params] = $this->buildWhere($filters);

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS tx_count, COALESCE(SUM(t.amount), 0) AS total_amount
             FROM transactions t
             JOIN users u ON u.id = t.user_id
             JOIN categories c ON c.id = t.category_id
             JOIN payment_methods p ON p.id = t.payment_method_id
             $whereSql"
        );
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];

        return [
            'tx_count' => (int) ($row['tx_count'] ?? 0),
            'total_amount' => (float) ($row['total_amount'] ?? 0),
        ];
    }

    private function buildWhere(array $filters): array
    {
        $where = [];
        $params = [];

        $userId = (int) ($filters['user_id'] ?? 0);
        if ($userId > 0) {
            $where[] = 't.user_id = :user_id';
            $params[':user_id'] = $userId;
        }

        $categoryId = (int) ($filters['category_id'] ?? 0);
        if ($categoryId > 0) {
            $where[] = 't.category_id = :category_id';
            $params[':category_id'] = $categoryId;
        }

        $paymentMethodId = (int) ($filters['payment_method_id'] ?? 0);
        if ($paymentMethodId > 0) {
            $where[] = 't.payment_method_id = :payment_method_id';
            $params[':payment_method_id'] = $paymentMethodId;
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(u.name LIKE :q OR u.email LIKE :q2 OR c.name LIKE :q3 OR p.name LIKE :q4 OR t.description LIKE :q5)';
            $params[':q'] = '%' . $q . '%';
            $params[':q2'] = '%' . $q . '%';
            $params[':q3'] = '%' . $q . '%';
            $params[':q4'] = '%' . $q . '%';
            $params[':q5'] = '%' . $q . '%';
        }

        $startDate = trim((string) ($filters['start_date'] ?? ''));
        if ($startDate !== '') {
            $where[] = 't.transaction_date >= :start_date';
            $params[':start_date'] = $startDate;
        }

        $endDate = trim((string) ($filters['end_date'] ?? ''));
        if ($endDate !== '') {
            $where[] = 't.transaction_date <= :end_date';
            $params[':end_date'] = $endDate;
        }

        $minAmountRaw = trim((string) ($filters['min_amount'] ?? ''));
        if ($minAmountRaw !== '' && is_numeric($minAmountRaw)) {
            $where[] = 't.amount >= :min_amount';
            $params[':min_amount'] = (float) $minAmountRaw;
        }

        $maxAmountRaw = trim((string) ($filters['max_amount'] ?? ''));
        if ($maxAmountRaw !== '' && is_numeric($maxAmountRaw)) {
            $where[] = 't.amount <= :max_amount';
            $params[':max_amount'] = (float) $maxAmountRaw;
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        return [$whereSql, $params];
    }

    private function users(): array
    {
        return $this->db->query('SELECT id, name, email FROM users ORDER BY created_at DESC')->fetchAll();
    }

    private function categories(): array
    {
        return $this->db->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();
    }

    private function paymentMethods(): array
    {
        return $this->db->query('SELECT id, name FROM payment_methods ORDER BY name ASC')->fetchAll();
    }
}
