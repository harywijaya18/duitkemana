<?php

class AdminAnalyticsModel extends Model
{
    public function snapshot(int $months = 6): array
    {
        $months = max(3, min(12, $months));

        return [
            'months' => $months,
            'retention_overview' => [
                'd1' => $this->retentionRate(1),
                'd7' => $this->retentionRate(7),
                'd30' => $this->retentionRate(30),
            ],
            'cohorts' => $this->cohorts($months),
            'adoption' => $this->adoptionMatrix(),
            'activation' => $this->activationMetrics(),
            'funnel' => $this->conversionFunnel(),
        ];
    }

    private function retentionRate(int $days, int $windowDays = 120): array
    {
        $days = max(1, $days);
        $windowDays = max($days + 1, $windowDays);

        $startDate = date('Y-m-d', strtotime('-' . $windowDays . ' days'));
        $endDate = date('Y-m-d', strtotime('-' . $days . ' days'));

        $eligible = $this->intPrepared(
            'SELECT COUNT(*)
             FROM users
             WHERE DATE(created_at) BETWEEN :start_date AND :end_date',
            [':start_date' => $startDate, ':end_date' => $endDate]
        );

        if ($eligible <= 0) {
            return [
                'eligible' => 0,
                'retained' => 0,
                'rate' => 0.0,
            ];
        }

        $retained = $this->intPrepared(
            'SELECT COUNT(*)
             FROM users u
             WHERE DATE(u.created_at) BETWEEN :start_date AND :end_date
               AND EXISTS (
                    SELECT 1
                    FROM transactions t
                    WHERE t.user_id = u.id
                      AND t.transaction_date = DATE_ADD(DATE(u.created_at), INTERVAL ' . $days . ' DAY)
               )',
            [':start_date' => $startDate, ':end_date' => $endDate]
        );

        return [
            'eligible' => $eligible,
            'retained' => $retained,
            'rate' => round(($retained / $eligible) * 100, 2),
        ];
    }

    private function cohorts(int $months): array
    {
        $rows = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $first = date('Y-m-01', strtotime('-' . $i . ' month'));
            $last = date('Y-m-t', strtotime($first));
            $size = $this->intPrepared(
                'SELECT COUNT(*) FROM users WHERE DATE(created_at) BETWEEN :start_date AND :end_date',
                [':start_date' => $first, ':end_date' => $last]
            );

            $d1 = $this->retentionInRange($first, $last, 1);
            $d7 = $this->retentionInRange($first, $last, 7);
            $d30 = $this->retentionInRange($first, $last, 30);

            $rows[] = [
                'month_key' => date('Y-m', strtotime($first)),
                'label' => date('M Y', strtotime($first)),
                'cohort_size' => $size,
                'd1' => $d1,
                'd7' => $d7,
                'd30' => $d30,
            ];
        }

        return $rows;
    }

    private function retentionInRange(string $startDate, string $endDate, int $days): array
    {
        $eligible = $this->intPrepared(
            'SELECT COUNT(*)
             FROM users
             WHERE DATE(created_at) BETWEEN :start_date AND :end_date
               AND DATE(created_at) <= DATE_SUB(CURDATE(), INTERVAL ' . max(1, $days) . ' DAY)',
            [':start_date' => $startDate, ':end_date' => $endDate]
        );

        if ($eligible <= 0) {
            return ['eligible' => 0, 'retained' => 0, 'rate' => 0.0];
        }

        $retained = $this->intPrepared(
            'SELECT COUNT(*)
             FROM users u
             WHERE DATE(u.created_at) BETWEEN :start_date AND :end_date
               AND DATE(u.created_at) <= DATE_SUB(CURDATE(), INTERVAL ' . max(1, $days) . ' DAY)
               AND EXISTS (
                    SELECT 1
                    FROM transactions t
                    WHERE t.user_id = u.id
                      AND t.transaction_date = DATE_ADD(DATE(u.created_at), INTERVAL ' . max(1, $days) . ' DAY)
               )',
            [':start_date' => $startDate, ':end_date' => $endDate]
        );

        return [
            'eligible' => $eligible,
            'retained' => $retained,
            'rate' => round(($retained / $eligible) * 100, 2),
        ];
    }

    private function adoptionMatrix(): array
    {
        $totalUsers = max(1, $this->intValue('SELECT COUNT(*) FROM users'));

        $items = [];
        $items[] = $this->adoptionItem('Budget Setup', $this->intValue('SELECT COUNT(DISTINCT user_id) FROM budgets'), $totalUsers, true);
        $items[] = $this->adoptionItem('API Token Usage', $this->intValue('SELECT COUNT(DISTINCT user_id) FROM api_tokens'), $totalUsers, true);

        if ($this->tableExists('recurring_bills')) {
            $items[] = $this->adoptionItem(
                'Recurring Bills',
                $this->intValue('SELECT COUNT(DISTINCT user_id) FROM recurring_bills WHERE is_active = 1'),
                $totalUsers,
                true
            );
        } else {
            $items[] = $this->adoptionItem('Recurring Bills', 0, $totalUsers, false);
        }

        if ($this->tableExists('subscriptions')) {
            $items[] = $this->adoptionItem(
                'Paid/Tracked Subscription',
                $this->intValue("SELECT COUNT(DISTINCT user_id) FROM subscriptions WHERE status IN ('trial','active','grace','past_due')"),
                $totalUsers,
                true
            );
        } else {
            $items[] = $this->adoptionItem('Paid/Tracked Subscription', 0, $totalUsers, false);
        }

        if ($this->tableExists('income_records')) {
            $items[] = $this->adoptionItem(
                'Income Tracking',
                $this->intValue('SELECT COUNT(DISTINCT user_id) FROM income_records'),
                $totalUsers,
                true
            );
        } else {
            $items[] = $this->adoptionItem('Income Tracking', 0, $totalUsers, false);
        }

        return [
            'total_users' => $totalUsers,
            'items' => $items,
        ];
    }

    private function adoptionItem(string $name, int $adopted, int $totalUsers, bool $available): array
    {
        $adopted = max(0, $adopted);
        $rate = $totalUsers > 0 ? round(($adopted / $totalUsers) * 100, 2) : 0.0;

        return [
            'feature' => $name,
            'adopted' => $adopted,
            'total' => $totalUsers,
            'rate' => $rate,
            'available' => $available,
        ];
    }

    private function activationMetrics(): array
    {
        $rows = $this->db->query(
            'SELECT DATEDIFF(MIN(t.transaction_date), DATE(u.created_at)) AS lag_days
             FROM users u
             JOIN transactions t ON t.user_id = u.id
             GROUP BY u.id'
        )->fetchAll();

        $lags = [];
        foreach ($rows as $row) {
            $lags[] = max(0, (int) ($row['lag_days'] ?? 0));
        }
        sort($lags);

        $count = count($lags);
        $avg = 0.0;
        $median = 0.0;
        if ($count > 0) {
            $avg = array_sum($lags) / $count;
            $middle = intdiv($count, 2);
            if ($count % 2 === 0) {
                $median = ($lags[$middle - 1] + $lags[$middle]) / 2;
            } else {
                $median = $lags[$middle];
            }
        }

        $eligible7d = $this->intValue("SELECT COUNT(*) FROM users WHERE DATE(created_at) <= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
        $activated7d = 0;
        if ($eligible7d > 0) {
            $activated7d = $this->intValue(
                'SELECT COUNT(*)
                 FROM users u
                 WHERE DATE(u.created_at) <= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                   AND EXISTS (
                        SELECT 1
                        FROM transactions t
                        WHERE t.user_id = u.id
                          AND t.transaction_date BETWEEN DATE(u.created_at)
                              AND DATE_ADD(DATE(u.created_at), INTERVAL 7 DAY)
                   )'
            );
        }

        return [
            'users_with_first_tx' => $count,
            'avg_days_to_first_tx' => round($avg, 2),
            'median_days_to_first_tx' => round($median, 2),
            'activation_7d_rate' => $eligible7d > 0 ? round(($activated7d / $eligible7d) * 100, 2) : 0.0,
            'activation_7d_eligible' => $eligible7d,
            'activation_7d_users' => $activated7d,
        ];
    }

    private function conversionFunnel(): array
    {
        $registered = $this->intValue('SELECT COUNT(*) FROM users');
        $firstTx = $this->intValue('SELECT COUNT(DISTINCT user_id) FROM transactions');
        $threeTx = $this->intValue('SELECT COUNT(*) FROM (SELECT user_id FROM transactions GROUP BY user_id HAVING COUNT(*) >= 3) x');
        $active30d = $this->intValue('SELECT COUNT(DISTINCT user_id) FROM transactions WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)');

        $steps = [
            ['label' => 'Registered Users', 'value' => $registered],
            ['label' => 'First Transaction', 'value' => $firstTx],
            ['label' => '3+ Transactions', 'value' => $threeTx],
            ['label' => 'Active 30 Days', 'value' => $active30d],
        ];

        $prev = null;
        foreach ($steps as $i => $step) {
            $val = (int) $step['value'];
            $fromRegistered = $registered > 0 ? round(($val / $registered) * 100, 2) : 0.0;
            $stepToStep = ($prev !== null && $prev > 0) ? round(($val / $prev) * 100, 2) : 100.0;
            $steps[$i]['from_registered_pct'] = $fromRegistered;
            $steps[$i]['step_pct'] = $stepToStep;
            $prev = $val;
        }

        return [
            'registered' => $registered,
            'steps' => $steps,
        ];
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

    private function intPrepared(string $sql, array $params): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) ($stmt->fetchColumn() ?: 0);
    }
}
