<?php

class AdminMetricsModel extends Model
{
    public function dashboardSnapshot(int $months = 6): array
    {
        $months = max(3, min(12, $months));

        $kpis = [
            'total_users' => $this->intValue('SELECT COUNT(*) FROM users'),
            'new_users_30d' => $this->intValue('SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)'),
            'active_users_30d' => $this->intValue('SELECT COUNT(DISTINCT user_id) FROM transactions WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)'),
            'transactions_30d' => $this->intValue('SELECT COUNT(*) FROM transactions WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)'),
            'expense_30d' => $this->floatValue('SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)'),
            'income_30d' => $this->incomeExists()
                ? $this->floatValue('SELECT COALESCE(SUM(total_income), 0) FROM income_records WHERE received_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)')
                : 0.0,
            'mrr_proxy' => $this->incomeExists()
                ? $this->floatValue('SELECT COALESCE(SUM(total_income), 0) FROM income_records WHERE period_year = YEAR(CURDATE()) AND period_month = MONTH(CURDATE())')
                : 0.0,
        ];

        $recurring = $this->recurringStatusForCurrentMonth();

        return [
            'kpis' => $kpis,
            'recurring' => $recurring,
            'trend' => $this->monthlyTrend($months),
            'top_spenders' => $this->topSpendersCurrentMonth(8),
            'recent_users' => $this->recentUsers(8),
        ];
    }

    private function recurringStatusForCurrentMonth(): array
    {
        if (!$this->tableExists('recurring_bills')) {
            return [
                'available' => false,
                'active_bills' => 0,
                'generated_rows' => 0,
                'generated_amount' => 0.0,
                'coverage_pct' => 0.0,
            ];
        }

        $year = (int) date('Y');
        $month = (int) date('n');

        $activeBills = $this->intPrepared(
            'SELECT COUNT(*)
             FROM recurring_bills
             WHERE is_active = 1
               AND (start_year < :y1 OR (start_year = :y2 AND start_month <= :m1))
               AND (end_year IS NULL OR end_year > :y3 OR (end_year = :y4 AND end_month >= :m2))',
            [':y1' => $year, ':y2' => $year, ':m1' => $month, ':y3' => $year, ':y4' => $year, ':m2' => $month]
        );

        $generatedRows = $this->intPrepared(
            'SELECT COUNT(*)
             FROM transactions
             WHERE recurring_bill_id IS NOT NULL
               AND YEAR(transaction_date) = :y
               AND MONTH(transaction_date) = :m',
            [':y' => $year, ':m' => $month]
        );

        $generatedAmount = $this->floatPrepared(
            'SELECT COALESCE(SUM(amount), 0)
             FROM transactions
             WHERE recurring_bill_id IS NOT NULL
               AND YEAR(transaction_date) = :y
               AND MONTH(transaction_date) = :m',
            [':y' => $year, ':m' => $month]
        );

        $coverage = $activeBills > 0 ? min(100, round(($generatedRows / $activeBills) * 100, 1)) : 100.0;

        return [
            'available' => true,
            'active_bills' => $activeBills,
            'generated_rows' => $generatedRows,
            'generated_amount' => $generatedAmount,
            'coverage_pct' => $coverage,
        ];
    }

    private function monthlyTrend(int $months): array
    {
        $monthKeys = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $ts = strtotime('-' . $i . ' month');
            $monthKeys[] = date('Y-m', $ts);
        }

        $userRows = $this->db->query(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS cnt
             FROM users
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')"
        )->fetchAll();
        $usersMap = $this->mapRows($userRows, 'ym', 'cnt');

        $txRows = $this->db->query(
            "SELECT DATE_FORMAT(transaction_date, '%Y-%m') AS ym,
                    COUNT(*) AS tx_count,
                    COALESCE(SUM(amount), 0) AS expense_total
             FROM transactions
             WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
             GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')"
        )->fetchAll();
        $txMap = [];
        foreach ($txRows as $row) {
            $key = (string) ($row['ym'] ?? '');
            $txMap[$key] = [
                'tx_count' => (int) ($row['tx_count'] ?? 0),
                'expense_total' => (float) ($row['expense_total'] ?? 0),
            ];
        }

        $incomeMap = [];
        if ($this->incomeExists()) {
            $incomeRows = $this->db->query(
                "SELECT CONCAT(period_year, '-', LPAD(period_month, 2, '0')) AS ym,
                        COALESCE(SUM(total_income), 0) AS income_total
                 FROM income_records
                 WHERE STR_TO_DATE(CONCAT(period_year, '-', LPAD(period_month, 2, '0'), '-01'), '%Y-%m-%d') >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                 GROUP BY period_year, period_month"
            )->fetchAll();
            $incomeMap = $this->mapRows($incomeRows, 'ym', 'income_total');
        }

        $series = [];
        foreach ($monthKeys as $key) {
            $series[] = [
                'key' => $key,
                'label' => date('M Y', strtotime($key . '-01')),
                'new_users' => (int) ($usersMap[$key] ?? 0),
                'transactions' => (int) (($txMap[$key]['tx_count'] ?? 0)),
                'expense' => (float) (($txMap[$key]['expense_total'] ?? 0)),
                'income' => (float) ($incomeMap[$key] ?? 0),
            ];
        }

        return $series;
    }

    private function topSpendersCurrentMonth(int $limit): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.name, u.email,
                    COUNT(t.id) AS tx_count,
                    COALESCE(SUM(t.amount), 0) AS total_expense
             FROM users u
             LEFT JOIN transactions t
                    ON t.user_id = u.id
                   AND YEAR(t.transaction_date) = YEAR(CURDATE())
                   AND MONTH(t.transaction_date) = MONTH(CURDATE())
             GROUP BY u.id, u.name, u.email
             HAVING COUNT(t.id) > 0
             ORDER BY total_expense DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':lim', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function recentUsers(int $limit): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, currency, created_at
             FROM users
             ORDER BY created_at DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':lim', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function mapRows(array $rows, string $keyField, string $valueField): array
    {
        $mapped = [];
        foreach ($rows as $row) {
            $key = (string) ($row[$keyField] ?? '');
            if ($key === '') {
                continue;
            }
            $mapped[$key] = (float) ($row[$valueField] ?? 0);
        }
        return $mapped;
    }

    private function incomeExists(): bool
    {
        return $this->tableExists('income_records');
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name = :table'
        );
        $stmt->execute([':table' => $table]);
        return ((int) $stmt->fetchColumn()) > 0;
    }

    private function intValue(string $sql): int
    {
        return (int) ($this->db->query($sql)->fetchColumn() ?: 0);
    }

    private function floatValue(string $sql): float
    {
        return (float) ($this->db->query($sql)->fetchColumn() ?: 0);
    }

    private function intPrepared(string $sql, array $params): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    private function floatPrepared(string $sql, array $params): float
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (float) ($stmt->fetchColumn() ?: 0);
    }
}
