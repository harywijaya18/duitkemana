<?php

class AdminOperationsModel extends Model
{
    public function snapshot(
        int $year,
        int $month,
        int $billsPage = 1,
        int $billsPerPage = 20,
        int $mismatchesPage = 1,
        int $duplicatesPage = 1,
        int $opsPerPage = 20
    ): array
    {
        $year = max(2000, min(2099, $year));
        $month = max(1, min(12, $month));
        $billsPage = max(1, $billsPage);
        $billsPerPage = max(10, min(100, $billsPerPage));
        $mismatchesPage = max(1, $mismatchesPage);
        $duplicatesPage = max(1, $duplicatesPage);
        $opsPerPage = max(10, min(100, $opsPerPage));

        return [
            'period' => ['year' => $year, 'month' => $month],
            'recurring' => $this->recurringCoverage($year, $month),
            'active_recurring_bills' => $this->activeRecurringBills($year, $month, $billsPage, $billsPerPage),
            'mismatches' => $this->recurringMismatches($year, $month, $mismatchesPage, $opsPerPage),
            'duplicates' => $this->duplicateRecurringTransactions($year, $month, $duplicatesPage, $opsPerPage),
            'jobs' => $this->jobQueueStatus($year, $month),
            'api_health' => $this->apiHealthSummary(),
        ];
    }

    private function activeRecurringBills(int $year, int $month, int $page, int $perPage): array
    {
        if (!$this->tableExists('recurring_bills')) {
            return [
                'items' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => $perPage,
                'total_pages' => 1,
            ];
        }

        $page = max(1, $page);
        $perPage = max(10, min(100, $perPage));
        $hasCategories = $this->tableExists('categories');

        $total = $this->intPrepared(
            'SELECT COUNT(*)
             FROM recurring_bills rb
             WHERE rb.is_active = 1
               AND (rb.start_year < :y1 OR (rb.start_year = :y2 AND rb.start_month <= :m1))
               AND (rb.end_year IS NULL OR rb.end_year > :y3 OR (rb.end_year = :y4 AND rb.end_month >= :m2))',
            [':y1' => $year, ':y2' => $year, ':m1' => $month, ':y3' => $year, ':y4' => $year, ':m2' => $month]
        );

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT rb.id, rb.user_id, rb.name, rb.amount,
                       rb.start_month, rb.start_year, rb.end_month, rb.end_year,
                       u.name AS user_name, u.email AS user_email,'
             . ($hasCategories ? ' COALESCE(c.name, "-") AS category_name' : ' "-" AS category_name')
             . ' FROM recurring_bills rb
                 JOIN users u ON u.id = rb.user_id'
             . ($hasCategories ? ' LEFT JOIN categories c ON c.id = rb.category_id' : '')
             . ' WHERE rb.is_active = 1
                   AND (rb.start_year < :y1 OR (rb.start_year = :y2 AND rb.start_month <= :m1))
                   AND (rb.end_year IS NULL OR rb.end_year > :y3 OR (rb.end_year = :y4 AND rb.end_month >= :m2))
                 ORDER BY u.id ASC, rb.amount DESC, rb.id ASC
                 LIMIT :off, :lim';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':y1', $year, PDO::PARAM_INT);
        $stmt->bindValue(':y2', $year, PDO::PARAM_INT);
        $stmt->bindValue(':m1', $month, PDO::PARAM_INT);
        $stmt->bindValue(':y3', $year, PDO::PARAM_INT);
        $stmt->bindValue(':y4', $year, PDO::PARAM_INT);
        $stmt->bindValue(':m2', $month, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    private function recurringCoverage(int $year, int $month): array
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

    private function recurringMismatches(int $year, int $month, int $page, int $perPage): array
    {
        if (!$this->tableExists('recurring_bills')) {
            return [
                'items' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => $perPage,
                'total_pages' => 1,
            ];
        }

        $page = max(1, $page);
        $perPage = max(10, min(100, $perPage));

        $total = $this->intPrepared(
            "SELECT COUNT(*)
             FROM users u
             LEFT JOIN (
                SELECT rb.user_id, COUNT(*) AS active_count
                FROM recurring_bills rb
                WHERE rb.is_active = 1
                  AND (rb.start_year < :y1 OR (rb.start_year = :y2 AND rb.start_month <= :m1))
                  AND (rb.end_year IS NULL OR rb.end_year > :y3 OR (rb.end_year = :y4 AND rb.end_month >= :m2))
                GROUP BY rb.user_id
             ) a ON a.user_id = u.id
             LEFT JOIN (
                SELECT t.user_id, COUNT(DISTINCT t.recurring_bill_id) AS generated_count
                FROM transactions t
                WHERE t.recurring_bill_id IS NOT NULL
                  AND YEAR(t.transaction_date) = :y5
                  AND MONTH(t.transaction_date) = :m3
                GROUP BY t.user_id
             ) g ON g.user_id = u.id
             WHERE COALESCE(a.active_count, 0) <> COALESCE(g.generated_count, 0)",
            [':y1' => $year, ':y2' => $year, ':m1' => $month, ':y3' => $year, ':y4' => $year, ':m2' => $month, ':y5' => $year, ':m3' => $month]
        );

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT u.id, u.name, u.email,
                       COALESCE(a.active_count, 0) AS active_count,
                       COALESCE(g.generated_count, 0) AS generated_count,
                       (COALESCE(a.active_count, 0) - COALESCE(g.generated_count, 0)) AS missing_count
                FROM users u
                LEFT JOIN (
                    SELECT rb.user_id, COUNT(*) AS active_count
                    FROM recurring_bills rb
                    WHERE rb.is_active = 1
                      AND (rb.start_year < :y1 OR (rb.start_year = :y2 AND rb.start_month <= :m1))
                      AND (rb.end_year IS NULL OR rb.end_year > :y3 OR (rb.end_year = :y4 AND rb.end_month >= :m2))
                    GROUP BY rb.user_id
                ) a ON a.user_id = u.id
                LEFT JOIN (
                    SELECT t.user_id, COUNT(DISTINCT t.recurring_bill_id) AS generated_count
                    FROM transactions t
                    WHERE t.recurring_bill_id IS NOT NULL
                      AND YEAR(t.transaction_date) = :y5
                      AND MONTH(t.transaction_date) = :m3
                    GROUP BY t.user_id
                ) g ON g.user_id = u.id
                WHERE COALESCE(a.active_count, 0) <> COALESCE(g.generated_count, 0)
                ORDER BY missing_count DESC, u.id ASC
                LIMIT :off, :lim";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':y1', $year, PDO::PARAM_INT);
        $stmt->bindValue(':y2', $year, PDO::PARAM_INT);
        $stmt->bindValue(':m1', $month, PDO::PARAM_INT);
        $stmt->bindValue(':y3', $year, PDO::PARAM_INT);
        $stmt->bindValue(':y4', $year, PDO::PARAM_INT);
        $stmt->bindValue(':m2', $month, PDO::PARAM_INT);
        $stmt->bindValue(':y5', $year, PDO::PARAM_INT);
        $stmt->bindValue(':m3', $month, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    private function duplicateRecurringTransactions(int $year, int $month, int $page, int $perPage): array
    {
        if (!$this->tableExists('recurring_bills')) {
            return [
                'items' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => $perPage,
                'total_pages' => 1,
            ];
        }

        $page = max(1, $page);
        $perPage = max(10, min(100, $perPage));

        $total = $this->intPrepared(
            'SELECT COUNT(*)
             FROM (
                 SELECT t.user_id, t.recurring_bill_id
                 FROM transactions t
                 WHERE t.recurring_bill_id IS NOT NULL
                   AND YEAR(t.transaction_date) = :y
                   AND MONTH(t.transaction_date) = :m
                 GROUP BY t.user_id, t.recurring_bill_id
                 HAVING COUNT(*) > 1
             ) d',
            [':y' => $year, ':m' => $month]
        );

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare(
            'SELECT t.user_id, u.email,
                    t.recurring_bill_id,
                    COUNT(*) AS duplicate_rows
             FROM transactions t
             JOIN users u ON u.id = t.user_id
             WHERE t.recurring_bill_id IS NOT NULL
               AND YEAR(t.transaction_date) = :y
               AND MONTH(t.transaction_date) = :m
             GROUP BY t.user_id, u.email, t.recurring_bill_id
             HAVING COUNT(*) > 1
             ORDER BY duplicate_rows DESC
                         LIMIT :off, :lim'
        );
        $stmt->bindValue(':y', $year, PDO::PARAM_INT);
        $stmt->bindValue(':m', $month, PDO::PARAM_INT);
                $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
                $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->execute();
                return [
                        'items' => $stmt->fetchAll(),
                        'total' => $total,
                        'page' => $page,
                        'per_page' => $perPage,
                        'total_pages' => $totalPages,
                ];
    }

    private function jobQueueStatus(int $year, int $month): array
    {
        $jobTable = null;
        foreach (['jobs', 'background_jobs', 'queue_jobs'] as $candidate) {
            if ($this->tableExists($candidate)) {
                $jobTable = $candidate;
                break;
            }
        }

        if ($jobTable === null) {
            if (!$this->tableExists('recurring_bills')) {
                return [
                    'available' => false,
                    'mode' => 'none',
                    'table' => null,
                    'pending' => 0,
                    'failed' => 0,
                    'last_failed_at' => null,
                ];
            }

            $expected = $this->intPrepared(
                'SELECT COUNT(*)
                 FROM recurring_bills
                 WHERE is_active = 1
                   AND (start_year < :y1 OR (start_year = :y2 AND start_month <= :m1))
                   AND (end_year IS NULL OR end_year > :y3 OR (end_year = :y4 AND end_month >= :m2))',
                [':y1' => $year, ':y2' => $year, ':m1' => $month, ':y3' => $year, ':y4' => $year, ':m2' => $month]
            );

            $generated = $this->intPrepared(
                'SELECT COUNT(DISTINCT recurring_bill_id)
                 FROM transactions
                 WHERE recurring_bill_id IS NOT NULL
                   AND YEAR(transaction_date) = :y
                   AND MONTH(transaction_date) = :m',
                [':y' => $year, ':m' => $month]
            );

            $duplicateGroups = $this->intPrepared(
                'SELECT COUNT(*)
                 FROM (
                    SELECT recurring_bill_id
                    FROM transactions
                    WHERE recurring_bill_id IS NOT NULL
                      AND YEAR(transaction_date) = :y
                      AND MONTH(transaction_date) = :m
                    GROUP BY recurring_bill_id
                    HAVING COUNT(*) > 1
                 ) d',
                [':y' => $year, ':m' => $month]
            );

            return [
                'available' => true,
                'mode' => 'derived',
                'table' => null,
                'pending' => max($expected - $generated, 0),
                'failed' => $duplicateGroups,
                'last_failed_at' => null,
                'expected' => $expected,
                'generated' => $generated,
                'hint' => 'Queue table tidak ada. Status dihitung dari backlog recurring periode ini.',
            ];
        }

        // Generic, best-effort counters (depends on schema convention)
        $pending = 0;
        $failed = 0;
        $lastFailedAt = null;

        $columns = $this->tableColumns($jobTable);
        if (in_array('status', $columns, true)) {
            $pending = $this->intValue("SELECT COUNT(*) FROM {$jobTable} WHERE status IN ('pending','queued','processing')");
            $failed = $this->intValue("SELECT COUNT(*) FROM {$jobTable} WHERE status IN ('failed','error')");
            if (in_array('updated_at', $columns, true)) {
                $lastFailedAt = $this->stringValue("SELECT MAX(updated_at) FROM {$jobTable} WHERE status IN ('failed','error')");
            }
        }

        return [
            'available' => true,
            'mode' => 'table',
            'table' => $jobTable,
            'pending' => $pending,
            'failed' => $failed,
            'last_failed_at' => $lastFailedAt,
        ];
    }

    private function apiHealthSummary(): array
    {
        if (!$this->tableExists('api_request_logs')) {
            return [
                'available' => false,
                'message' => 'Tabel api_request_logs tidak ditemukan.',
            ];
        }

        $windowFrom = date('Y-m-d H:i:s', time() - (24 * 60 * 60));

        $total = $this->intPrepared(
            'SELECT COUNT(*) FROM api_request_logs WHERE created_at >= :from',
            [':from' => $windowFrom]
        );

        $errors4xx = $this->intPrepared(
            'SELECT COUNT(*) FROM api_request_logs WHERE created_at >= :from AND status_code BETWEEN 400 AND 499',
            [':from' => $windowFrom]
        );

        $errors5xx = $this->intPrepared(
            'SELECT COUNT(*) FROM api_request_logs WHERE created_at >= :from AND status_code >= 500',
            [':from' => $windowFrom]
        );

        $avgLatency = $this->floatPrepared(
            'SELECT COALESCE(AVG(duration_ms), 0) FROM api_request_logs WHERE created_at >= :from',
            [':from' => $windowFrom]
        );

        $p95Latency = $this->percentileDuration($windowFrom, $total, 0.95);

        $endpointStmt = $this->db->prepare(
            'SELECT method, path,
                    COUNT(*) AS requests,
                    ROUND(AVG(duration_ms), 1) AS avg_latency_ms,
                    SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) AS server_errors
             FROM api_request_logs
             WHERE created_at >= :from
             GROUP BY method, path
             ORDER BY requests DESC
             LIMIT 5'
        );
        $endpointStmt->execute([':from' => $windowFrom]);
        $endpoints = $endpointStmt->fetchAll();

        $errorStmt = $this->db->prepare(
            'SELECT method, path, status_code, duration_ms, error_code, created_at
             FROM api_request_logs
             WHERE created_at >= :from
               AND status_code >= 500
             ORDER BY id DESC
             LIMIT 10'
        );
        $errorStmt->execute([':from' => $windowFrom]);
        $recentErrors = $errorStmt->fetchAll();

        $errorRatio = $total > 0 ? round(($errors5xx / $total) * 100, 2) : 0.0;
        $clientErrorRatio = $total > 0 ? round(($errors4xx / $total) * 100, 2) : 0.0;

        return [
            'available' => true,
            'window_label' => 'Last 24h',
            'total_requests' => $total,
            'errors_4xx' => $errors4xx,
            'errors_5xx' => $errors5xx,
            'error_ratio_pct' => $errorRatio,
            'client_error_ratio_pct' => $clientErrorRatio,
            'avg_latency_ms' => round($avgLatency, 1),
            'p95_latency_ms' => $p95Latency,
            'endpoints' => $endpoints,
            'recent_errors' => $recentErrors,
            'message' => $total > 0 ? '' : 'Belum ada request API dalam 24 jam terakhir.',
        ];
    }

    private function percentileDuration(string $windowFrom, int $total, float $percentile): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        $offset = (int) max(0, min($total - 1, ceil($total * $percentile) - 1));

        $stmt = $this->db->prepare(
            'SELECT duration_ms
             FROM api_request_logs
             WHERE created_at >= :from
             ORDER BY duration_ms ASC
             LIMIT :off, 1'
        );
        $stmt->bindValue(':from', $windowFrom, PDO::PARAM_STR);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $value = $stmt->fetchColumn();
        return $value === false ? 0.0 : (float) $value;
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

    private function tableColumns(string $table): array
    {
        $stmt = $this->db->prepare(
            'SELECT column_name
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = :table'
        );
        $stmt->execute([':table' => $table]);
        return array_map(static fn($r) => (string) $r['column_name'], $stmt->fetchAll());
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

    private function intValue(string $sql): int
    {
        return (int) ($this->db->query($sql)->fetchColumn() ?: 0);
    }

    private function stringValue(string $sql): ?string
    {
        $val = $this->db->query($sql)->fetchColumn();
        return $val === false || $val === null ? null : (string) $val;
    }
}
