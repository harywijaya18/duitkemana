<?php

class AdminOperationsModel extends Model
{
    public function snapshot(int $year, int $month): array
    {
        $year = max(2000, min(2099, $year));
        $month = max(1, min(12, $month));

        return [
            'period' => ['year' => $year, 'month' => $month],
            'recurring' => $this->recurringCoverage($year, $month),
            'mismatches' => $this->recurringMismatches($year, $month, 20),
            'duplicates' => $this->duplicateRecurringTransactions($year, $month, 20),
            'jobs' => $this->jobQueueStatus(),
            'api_health' => $this->apiHealthPlaceholder(),
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

    private function recurringMismatches(int $year, int $month, int $limit): array
    {
        if (!$this->tableExists('recurring_bills')) {
            return [];
        }

        $limit = max(1, min(100, $limit));
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
                LIMIT :lim";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':y1', $year, PDO::PARAM_INT);
        $stmt->bindValue(':y2', $year, PDO::PARAM_INT);
        $stmt->bindValue(':m1', $month, PDO::PARAM_INT);
        $stmt->bindValue(':y3', $year, PDO::PARAM_INT);
        $stmt->bindValue(':y4', $year, PDO::PARAM_INT);
        $stmt->bindValue(':m2', $month, PDO::PARAM_INT);
        $stmt->bindValue(':y5', $year, PDO::PARAM_INT);
        $stmt->bindValue(':m3', $month, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function duplicateRecurringTransactions(int $year, int $month, int $limit): array
    {
        if (!$this->tableExists('recurring_bills')) {
            return [];
        }

        $limit = max(1, min(100, $limit));
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
             LIMIT :lim'
        );
        $stmt->bindValue(':y', $year, PDO::PARAM_INT);
        $stmt->bindValue(':m', $month, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function jobQueueStatus(): array
    {
        $jobTable = null;
        foreach (['jobs', 'background_jobs', 'queue_jobs'] as $candidate) {
            if ($this->tableExists($candidate)) {
                $jobTable = $candidate;
                break;
            }
        }

        if ($jobTable === null) {
            return [
                'available' => false,
                'table' => null,
                'pending' => 0,
                'failed' => 0,
                'last_failed_at' => null,
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
            'table' => $jobTable,
            'pending' => $pending,
            'failed' => $failed,
            'last_failed_at' => $lastFailedAt,
        ];
    }

    private function apiHealthPlaceholder(): array
    {
        return [
            'available' => false,
            'message' => 'API latency/error telemetry belum diinstrumentasi. Tambahkan request logging dan metrics collector.',
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
