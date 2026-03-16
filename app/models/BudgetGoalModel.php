<?php

class BudgetGoalModel extends Model
{
    public function isAvailable(): bool
    {
        static $checked = null;
        if ($checked !== null) {
            return $checked;
        }

        try {
            $this->db->query('SELECT 1 FROM budget_goals LIMIT 1');
            $checked = true;
        } catch (\Throwable $e) {
            $checked = false;
        }

        return $checked;
    }

    /**
     * Upsert a budget goal for a specific category/month/year.
     */
    public function setGoal(int $userId, int $categoryId, int $month, int $year, float $amount): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO budget_goals (user_id, category_id, month, year, goal_amount)
             VALUES (:user_id, :category_id, :month, :year, :amount)
             ON DUPLICATE KEY UPDATE goal_amount = VALUES(goal_amount)'
        );
        return $stmt->execute([
            ':user_id'     => $userId,
            ':category_id' => $categoryId,
            ':month'       => $month,
            ':year'        => $year,
            ':amount'      => $amount,
        ]);
    }

    /**
     * Return all goals for a user in a given period, including category info and spent amount.
     */
    public function getGoalsByPeriod(int $userId, int $month, int $year): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT g.id,
                    g.category_id,
                    g.goal_amount,
                    c.name  AS category_name,
                    c.icon  AS category_icon,
                    COALESCE(SUM(t.amount), 0) AS spent
             FROM budget_goals g
             JOIN categories c ON c.id = g.category_id
             LEFT JOIN transactions t
                    ON  t.user_id     = g.user_id
                    AND t.category_id = g.category_id
                    AND MONTH(t.transaction_date) = g.month
                    AND YEAR(t.transaction_date)  = g.year
             WHERE g.user_id = :user_id
               AND g.month   = :month
               AND g.year    = :year
             GROUP BY g.id
             ORDER BY c.name ASC'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':month'   => $month,
            ':year'    => $year,
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Get all user categories (for the goal form dropdown).
     */
    public function getUserCategories(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, icon FROM categories WHERE user_id = :user_id ORDER BY name ASC'
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Delete a single goal (must belong to the user).
     */
    public function deleteGoal(int $goalId, int $userId): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $stmt = $this->db->prepare(
            'DELETE FROM budget_goals WHERE id = :id AND user_id = :user_id'
        );
        return $stmt->execute([':id' => $goalId, ':user_id' => $userId]);
    }

    /**
     * Copy goals from the previous period into target period.
     * Existing goals in target period are kept and will be skipped.
     *
     * @return array{copied:int, skipped:int, source_count:int}
     */
    public function copyFromPreviousPeriod(int $userId, int $targetMonth, int $targetYear): array
    {
        if (!$this->isAvailable()) {
            return ['copied' => 0, 'skipped' => 0, 'source_count' => 0];
        }

        $prevMonth = $targetMonth - 1;
        $prevYear = $targetYear;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }

        $sourceStmt = $this->db->prepare(
            'SELECT category_id, goal_amount
             FROM budget_goals
             WHERE user_id = :user_id
               AND month = :month
               AND year = :year'
        );
        $sourceStmt->execute([
            ':user_id' => $userId,
            ':month' => $prevMonth,
            ':year' => $prevYear,
        ]);
        $sourceRows = $sourceStmt->fetchAll();

        if (empty($sourceRows)) {
            return ['copied' => 0, 'skipped' => 0, 'source_count' => 0];
        }

        $existsStmt = $this->db->prepare(
            'SELECT 1 FROM budget_goals
             WHERE user_id = :user_id
               AND category_id = :category_id
               AND month = :month
               AND year = :year
             LIMIT 1'
        );

        $insertStmt = $this->db->prepare(
            'INSERT INTO budget_goals (user_id, category_id, month, year, goal_amount)
             VALUES (:user_id, :category_id, :month, :year, :goal_amount)'
        );

        $copied = 0;
        $skipped = 0;

        foreach ($sourceRows as $row) {
            $existsStmt->execute([
                ':user_id' => $userId,
                ':category_id' => (int) $row['category_id'],
                ':month' => $targetMonth,
                ':year' => $targetYear,
            ]);

            if ($existsStmt->fetchColumn()) {
                $skipped++;
                continue;
            }

            $insertStmt->execute([
                ':user_id' => $userId,
                ':category_id' => (int) $row['category_id'],
                ':month' => $targetMonth,
                ':year' => $targetYear,
                ':goal_amount' => (float) $row['goal_amount'],
            ]);
            $copied++;
        }

        return ['copied' => $copied, 'skipped' => $skipped, 'source_count' => count($sourceRows)];
    }
}
