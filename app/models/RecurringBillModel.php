<?php

class RecurringBillModel extends Model
{
    /** All bills for a user – active first, then by id desc. */
    public function allByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT rb.*, c.name AS category_name
             FROM recurring_bills rb
             LEFT JOIN categories c ON c.id = rb.category_id
             WHERE rb.user_id = :uid
             ORDER BY rb.is_active DESC, rb.id DESC'
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT rb.*, c.name AS category_name
             FROM recurring_bills rb
             LEFT JOIN categories c ON c.id = rb.category_id
             WHERE rb.id = :id AND rb.user_id = :uid LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * All bills that are scheduled/active for the given year and month.
     * A bill is active when: start <= (year,month) AND (no end OR end >= (year,month))
     */
    public function activeForMonth(int $userId, int $year, int $month): array
    {
        $stmt = $this->db->prepare(
            'SELECT rb.*, c.name AS category_name
             FROM recurring_bills rb
             LEFT JOIN categories c ON c.id = rb.category_id
             WHERE rb.user_id = :uid
               AND rb.is_active = 1
               AND (rb.start_year < :y1 OR (rb.start_year = :y2 AND rb.start_month <= :m1))
               AND (
                     rb.end_year IS NULL
                     OR rb.end_year > :y3
                     OR (rb.end_year = :y4 AND rb.end_month >= :m2)
                   )
             ORDER BY rb.name'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':y1'  => $year, ':y2' => $year,  ':m1' => $month,
            ':y3'  => $year, ':y4' => $year,  ':m2' => $month,
        ]);
        return $stmt->fetchAll();
    }

    /** Total recurring bill amount for a given month. */
    public function totalForMonth(int $userId, int $year, int $month): float
    {
        $bills = $this->activeForMonth($userId, $year, $month);
        return (float) array_sum(array_column($bills, 'amount'));
    }

    /**
     * Average monthly recurring total over the last N months (not counting current month).
     * Used to strip the recurring component from the historical expense average.
     */
    public function avgMonthlyTotal(int $userId, int $months = 3): float
    {
        $curYear  = (int) date('Y');
        $curMonth = (int) date('n');
        $totals   = [];
        for ($i = 1; $i <= $months; $i++) {
            $ts       = mktime(0, 0, 0, $curMonth - $i, 1, $curYear);
            $totals[] = $this->totalForMonth($userId, (int) date('Y', $ts), (int) date('n', $ts));
        }
        return count($totals) > 0 ? array_sum($totals) / count($totals) : 0.0;
    }

    /**
     * Auto-generate expense transactions for active recurring bills in the given month.
     * Idempotent – skips bills that already have a transaction in that month.
     * Returns count of newly created transactions.
     */
    public function generateForMonth(int $userId, int $year, int $month): int
    {
        $bills     = $this->activeForMonth($userId, $year, $month);
        $generated = 0;
        $date      = sprintf('%04d-%02d-01', $year, $month);
        $defaultPaymentMethodId = $this->resolveDefaultPaymentMethodId();
        $defaultCategoryId = $this->resolveDefaultCategoryId($userId);

        // Cannot generate transactions safely when mandatory FK values are unavailable.
        if ($defaultPaymentMethodId <= 0 || $defaultCategoryId <= 0) {
            return 0;
        }

        foreach ($bills as $bill) {
            // Check duplicate
            $check = $this->db->prepare(
                'SELECT id FROM transactions
                 WHERE recurring_bill_id = :rid
                   AND YEAR(transaction_date)  = :y
                   AND MONTH(transaction_date) = :m
                 LIMIT 1'
            );
            $check->execute([':rid' => $bill['id'], ':y' => $year, ':m' => $month]);
            if ($check->fetch()) {
                continue;
            }

            $ins = $this->db->prepare(
                'INSERT INTO transactions
                 (user_id, category_id, amount, payment_method_id, description,
                  receipt_image, transaction_date, recurring_bill_id)
                 VALUES
                 (:uid, :cid, :amount, :pmid, :desc, NULL, :date, :rbid)'
            );
            $categoryId = !empty($bill['category_id']) ? (int) $bill['category_id'] : $defaultCategoryId;
            $ins->execute([
                ':uid'    => $userId,
                ':cid'    => $categoryId,
                ':amount' => $bill['amount'],
                ':pmid'   => $defaultPaymentMethodId,
                ':desc'   => $bill['name'],
                ':date'   => $date,
                ':rbid'   => $bill['id'],
            ]);
            $generated++;
        }
        return $generated;
    }

    /**
     * Scheduler health snapshot for one user and period.
     */
    public function healthSnapshotForMonth(int $userId, int $year, int $month): array
    {
        $activeBills = $this->activeForMonth($userId, $year, $month);

        $generatedStmt = $this->db->prepare(
            'SELECT COUNT(*) AS generated_rows, COALESCE(SUM(amount), 0) AS generated_amount
             FROM transactions
             WHERE user_id = :uid
               AND recurring_bill_id IS NOT NULL
               AND YEAR(transaction_date) = :y
               AND MONTH(transaction_date) = :m'
        );
        $generatedStmt->execute([':uid' => $userId, ':y' => $year, ':m' => $month]);
        $generated = $generatedStmt->fetch() ?: ['generated_rows' => 0, 'generated_amount' => 0];

        $dupStmt = $this->db->prepare(
            'SELECT recurring_bill_id, COUNT(*) AS dup_count
             FROM transactions
             WHERE user_id = :uid
               AND recurring_bill_id IS NOT NULL
               AND YEAR(transaction_date) = :y
               AND MONTH(transaction_date) = :m
             GROUP BY recurring_bill_id
             HAVING COUNT(*) > 1'
        );
        $dupStmt->execute([':uid' => $userId, ':y' => $year, ':m' => $month]);
        $duplicates = $dupStmt->fetchAll();

        return [
            'period' => [
                'month' => $month,
                'year' => $year,
            ],
            'defaults' => [
                'payment_method_id' => $this->resolveDefaultPaymentMethodId(),
                'category_id' => $this->resolveDefaultCategoryId($userId),
            ],
            'active' => [
                'count' => count($activeBills),
                'amount' => (float) array_sum(array_column($activeBills, 'amount')),
            ],
            'generated' => [
                'count' => (int) ($generated['generated_rows'] ?? 0),
                'amount' => (float) ($generated['generated_amount'] ?? 0),
            ],
            'duplicates' => [
                'count' => count($duplicates),
                'details' => array_map(static function (array $row): array {
                    return [
                        'recurring_bill_id' => (int) ($row['recurring_bill_id'] ?? 0),
                        'duplicate_rows' => (int) ($row['dup_count'] ?? 0),
                    ];
                }, $duplicates),
            ],
        ];
    }

    /**
     * Deactivate bills whose end date has already passed.
     * Called on each page load of the bills list and dashboard.
     */
    public function expireCompleted(int $userId): int
    {
        $y = (int) date('Y');
        $m = (int) date('n');
        $stmt = $this->db->prepare(
            'UPDATE recurring_bills SET is_active = 0
             WHERE user_id = :uid
               AND is_active = 1
               AND end_year IS NOT NULL
               AND (end_year < :y1 OR (end_year = :y2 AND end_month < :m))'
        );
        $stmt->execute([':uid' => $userId, ':y1' => $y, ':y2' => $y, ':m' => $m]);
        return (int) $stmt->rowCount();
    }

    public function create(array $data): bool
    {
        $this->computeEndDate($data);
        $stmt = $this->db->prepare(
            'INSERT INTO recurring_bills
             (user_id, category_id, name, amount, start_year, start_month,
              duration_months, end_year, end_month, is_active, notes)
             VALUES
             (:user_id, :category_id, :name, :amount, :start_year, :start_month,
              :duration_months, :end_year, :end_month, 1, :notes)'
        );
        return $stmt->execute([
            ':user_id'         => $data['user_id'],
            ':category_id'     => $data['category_id'],
            ':name'            => $data['name'],
            ':amount'          => $data['amount'],
            ':start_year'      => $data['start_year'],
            ':start_month'     => $data['start_month'],
            ':duration_months' => $data['duration_months'],
            ':end_year'        => $data['end_year'],
            ':end_month'       => $data['end_month'],
            ':notes'           => $data['notes'],
        ]);
    }

    public function update(int $id, int $userId, array $data): bool
    {
        $this->computeEndDate($data);
        $stmt = $this->db->prepare(
            'UPDATE recurring_bills SET
             category_id = :category_id, name = :name, amount = :amount,
             start_year = :start_year, start_month = :start_month,
             duration_months = :duration_months,
             end_year = :end_year, end_month = :end_month,
             is_active = :is_active, notes = :notes
             WHERE id = :id AND user_id = :user_id'
        );
        return $stmt->execute([
            ':id'              => $id,
            ':user_id'         => $userId,
            ':category_id'     => $data['category_id'],
            ':name'            => $data['name'],
            ':amount'          => $data['amount'],
            ':start_year'      => $data['start_year'],
            ':start_month'     => $data['start_month'],
            ':duration_months' => $data['duration_months'],
            ':end_year'        => $data['end_year'],
            ':end_month'       => $data['end_month'],
            ':is_active'       => $data['is_active'],
            ':notes'           => $data['notes'],
        ]);
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM recurring_bills WHERE id = :id AND user_id = :uid'
        );
        return $stmt->execute([':id' => $id, ':uid' => $userId]);
    }

    /** Compute end_year/end_month from start + duration_months (mutates $data). */
    private function computeEndDate(array &$data): void
    {
        if (!empty($data['duration_months']) && (int) $data['duration_months'] > 0) {
            $ts = mktime(
                0, 0, 0,
                (int) $data['start_month'] + (int) $data['duration_months'] - 1,
                1,
                (int) $data['start_year']
            );
            $data['end_year']  = (int) date('Y', $ts);
            $data['end_month'] = (int) date('n', $ts);
        }
    }

    private function resolveDefaultPaymentMethodId(): int
    {
        $stmt = $this->db->query('SELECT id FROM payment_methods ORDER BY id ASC LIMIT 1');
        $row = $stmt->fetch();
        return (int) ($row['id'] ?? 0);
    }

    private function resolveDefaultCategoryId(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT id FROM categories WHERE user_id = :uid ORDER BY id ASC LIMIT 1');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();
        return (int) ($row['id'] ?? 0);
    }
}
