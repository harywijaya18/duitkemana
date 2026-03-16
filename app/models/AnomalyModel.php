<?php

class AnomalyModel extends Model
{
    /**
     * Run all rule-based anomaly checks and return an array of anomaly records.
     * Each record has: type, severity (warning|danger), title, detail, amount, category_name.
     */
    public function detectAnomalies(int $userId, int $month, int $year): array
    {
        $anomalies = [];

        $anomalies = array_merge($anomalies, $this->detectCategorySpike($userId, $month, $year));
        $anomalies = array_merge($anomalies, $this->detectLargeTransaction($userId, $month, $year));
        $anomalies = array_merge($anomalies, $this->detectHighFrequency($userId, $month, $year));
        $anomalies = array_merge($anomalies, $this->detectNewCategorySpend($userId, $month, $year));

        return $anomalies;
    }

    /**
     * Rule 1 – Category spending spike: this month > 2× avg of previous 3 months.
     */
    private function detectCategorySpike(int $userId, int $month, int $year): array
    {
        // Current month totals per category
        $stmt = $this->db->prepare(
            'SELECT c.name AS category_name,
                    COALESCE(SUM(t.amount), 0) AS current_total
             FROM categories c
             LEFT JOIN transactions t
                    ON  t.category_id = c.id
                    AND t.user_id = :uid1
                    AND MONTH(t.transaction_date) = :month
                    AND YEAR(t.transaction_date)  = :year
             WHERE c.user_id = :uid2
             GROUP BY c.id'
        );
        $stmt->execute([':uid1' => $userId, ':month' => $month, ':year' => $year, ':uid2' => $userId]);
        $current = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);   // [name => total]

        // Avg of previous 3 months per category
        $prevMonths = [];
        for ($i = 1; $i <= 3; $i++) {
            $m = $month - $i;
            $y = $year;
            if ($m < 1) { $m += 12; $y--; }
            $prevMonths[] = ['month' => $m, 'year' => $y];
        }

        $stmt2 = $this->db->prepare(
            'SELECT c.name AS category_name,
                    COALESCE(SUM(t.amount), 0) AS total
             FROM categories c
             LEFT JOIN transactions t
                    ON  t.category_id = c.id
                    AND t.user_id = :uid
                    AND ((MONTH(t.transaction_date) = :m1 AND YEAR(t.transaction_date) = :y1)
                      OR (MONTH(t.transaction_date) = :m2 AND YEAR(t.transaction_date) = :y2)
                      OR (MONTH(t.transaction_date) = :m3 AND YEAR(t.transaction_date) = :y3))
             WHERE c.user_id = :uid2
             GROUP BY c.id'
        );
        $stmt2->execute([
            ':uid'  => $userId,
            ':m1'   => $prevMonths[0]['month'], ':y1' => $prevMonths[0]['year'],
            ':m2'   => $prevMonths[1]['month'], ':y2' => $prevMonths[1]['year'],
            ':m3'   => $prevMonths[2]['month'], ':y3' => $prevMonths[2]['year'],
            ':uid2' => $userId,
        ]);
        $prevTotals = $stmt2->fetchAll(PDO::FETCH_KEY_PAIR); // [name => 3-month-total]

        $anomalies = [];
        foreach ($current as $catName => $curTotal) {
            $threeMonthTotal = (float) ($prevTotals[$catName] ?? 0);
            if ($threeMonthTotal <= 0 || $curTotal <= 0) continue;
            $avg = $threeMonthTotal / 3;
            if ($avg < 10000) continue; // ignore tiny averages
            if ($curTotal >= $avg * 2.0) {
                $ratio = round($curTotal / $avg, 1);
                $anomalies[] = [
                    'type'          => 'category_spike',
                    'severity'      => $curTotal >= $avg * 3 ? 'danger' : 'warning',
                    'category_name' => $catName,
                    'title'         => "Lonjakan pengeluaran: {$catName}",
                    'detail'        => "Bulan ini {$ratio}× lebih tinggi dari rata-rata 3 bulan terakhir.",
                    'amount'        => (float) $curTotal,
                ];
            }
        }
        return $anomalies;
    }

    /**
     * Rule 2 – Large single transaction: amount > 3× avg transaction amount for that category.
     */
    private function detectLargeTransaction(int $userId, int $month, int $year): array
    {
        $stmt = $this->db->prepare(
            'SELECT t.id,
                    t.amount,
                    t.description,
                    t.transaction_date,
                    c.name AS category_name,
                    AVG(t2.amount) AS cat_avg
             FROM transactions t
             JOIN categories c ON c.id = t.category_id
             LEFT JOIN transactions t2
                    ON  t2.category_id = t.category_id
                    AND t2.user_id = :uid2
                    AND t2.transaction_date < DATE(:date_boundary)
             WHERE t.user_id = :uid
               AND MONTH(t.transaction_date) = :month
               AND YEAR(t.transaction_date)  = :year
             GROUP BY t.id
             HAVING cat_avg IS NOT NULL
                AND cat_avg > 10000
                AND t.amount >= cat_avg * 3
             ORDER BY t.amount DESC
             LIMIT 5'
        );
        $boundary = sprintf('%04d-%02d-01', $year, $month);
        $stmt->execute([
            ':uid'           => $userId,
            ':uid2'          => $userId,
            ':month'         => $month,
            ':year'          => $year,
            ':date_boundary' => $boundary,
        ]);
        $rows = $stmt->fetchAll();

        $anomalies = [];
        foreach ($rows as $row) {
            $anomalies[] = [
                'type'          => 'large_transaction',
                'severity'      => 'warning',
                'category_name' => $row['category_name'],
                'title'         => "Transaksi besar: {$row['category_name']}",
                'detail'        => sprintf(
                    "Transaksi Rp %s pada %s — %.1f× lebih besar dari rata-rata kategori ini.",
                    number_format((float) $row['amount'], 0, ',', '.'),
                    $row['transaction_date'],
                    ($row['cat_avg'] > 0 ? (float) $row['amount'] / (float) $row['cat_avg'] : 0)
                ),
                'amount' => (float) $row['amount'],
            ];
        }
        return $anomalies;
    }

    /**
     * Rule 3 – High frequency: category has > 2× the average daily transaction count this month.
     */
    private function detectHighFrequency(int $userId, int $month, int $year): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.name AS category_name, COUNT(t.id) AS tx_count
             FROM transactions t
             JOIN categories c ON c.id = t.category_id
             WHERE t.user_id = :uid
               AND MONTH(t.transaction_date) = :month
               AND YEAR(t.transaction_date)  = :year
             GROUP BY t.category_id
             ORDER BY tx_count DESC'
        );
        $stmt->execute([':uid' => $userId, ':month' => $month, ':year' => $year]);
        $rows = $stmt->fetchAll();

        if (empty($rows)) return [];
        $totalTx = array_sum(array_column($rows, 'tx_count'));
        if ($totalTx < 5) return [];
        $avgPerCat = $totalTx / count($rows);
        $anomalies = [];
        foreach ($rows as $row) {
            if ($row['tx_count'] >= $avgPerCat * 2.5 && $row['tx_count'] >= 8) {
                $anomalies[] = [
                    'type'          => 'high_frequency',
                    'severity'      => 'warning',
                    'category_name' => $row['category_name'],
                    'title'         => "Frekuensi tinggi: {$row['category_name']}",
                    'detail'        => "Terdapat {$row['tx_count']} transaksi bulan ini — jauh lebih sering dari kategori lain.",
                    'amount'        => 0,
                ];
            }
        }
        return $anomalies;
    }

    /**
     * Rule 4 – New category: user spent in a category this month that had zero spend in past 3 months.
     */
    private function detectNewCategorySpend(int $userId, int $month, int $year): array
    {
        $prevMonths = [];
        for ($i = 1; $i <= 3; $i++) {
            $m = $month - $i;
            $y = $year;
            if ($m < 1) { $m += 12; $y--; }
            $prevMonths[] = ['month' => $m, 'year' => $y];
        }

        $stmt = $this->db->prepare(
            'SELECT DISTINCT t.category_id
             FROM transactions t
             WHERE t.user_id = :uid
               AND ((MONTH(t.transaction_date) = :m1 AND YEAR(t.transaction_date) = :y1)
                 OR (MONTH(t.transaction_date) = :m2 AND YEAR(t.transaction_date) = :y2)
                 OR (MONTH(t.transaction_date) = :m3 AND YEAR(t.transaction_date) = :y3))'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':m1'  => $prevMonths[0]['month'], ':y1' => $prevMonths[0]['year'],
            ':m2'  => $prevMonths[1]['month'], ':y2' => $prevMonths[1]['year'],
            ':m3'  => $prevMonths[2]['month'], ':y3' => $prevMonths[2]['year'],
        ]);
        $prevCategoryIds = array_column($stmt->fetchAll(), 'category_id');

        $stmt2 = $this->db->prepare(
            'SELECT c.name AS category_name, SUM(t.amount) AS total
             FROM transactions t
             JOIN categories c ON c.id = t.category_id
             WHERE t.user_id = :uid
               AND MONTH(t.transaction_date) = :month
               AND YEAR(t.transaction_date)  = :year
             GROUP BY t.category_id'
        );
        $stmt2->execute([':uid' => $userId, ':month' => $month, ':year' => $year]);
        $currentRows = $stmt2->fetchAll();

        $anomalies = [];
        foreach ($currentRows as $row) {
            if ($row['total'] < 10000) continue; // skip negligible amounts
            // This is a new category if it's not in the previous 3 months
            if (!in_array($row['category_id'] ?? null, $prevCategoryIds, true)) {
                // Re-fetch category_id
                $stCheck = $this->db->prepare(
                    'SELECT t.category_id FROM transactions t
                     JOIN categories c ON c.id = t.category_id
                     WHERE t.user_id = :uid AND c.name = :name LIMIT 1'
                );
                $stCheck->execute([':uid' => $userId, ':name' => $row['category_name']]);
                $catRow  = $stCheck->fetch();
                $catId   = $catRow['category_id'] ?? null;
                if ($catId === null || in_array($catId, $prevCategoryIds, true)) continue;

                $anomalies[] = [
                    'type'          => 'new_category',
                    'severity'      => 'warning',
                    'category_name' => $row['category_name'],
                    'title'         => "Kategori baru: {$row['category_name']}",
                    'detail'        => sprintf(
                        'Kamu mulai berbelanja di kategori ini bulan ini (total Rp %s), padahal tidak ada pengeluaran di 3 bulan terakhir.',
                        number_format((float) $row['total'], 0, ',', '.')
                    ),
                    'amount' => (float) $row['total'],
                ];
            }
        }
        return $anomalies;
    }

    /**
     * Summary stats for the anomaly dashboard widget.
     */
    public function getMonthlyStats(int $userId, int $month, int $year): array
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(amount), 0) AS total, COUNT(*) AS tx_count
             FROM transactions
             WHERE user_id = :uid
               AND MONTH(transaction_date) = :month
               AND YEAR(transaction_date)  = :year'
        );
        $stmt->execute([':uid' => $userId, ':month' => $month, ':year' => $year]);
        return $stmt->fetch() ?: ['total' => 0, 'tx_count' => 0];
    }
}
