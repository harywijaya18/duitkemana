<?php

class BudgetModel extends Model
{
    public function setMonthlyBudget(int $userId, int $month, int $year, float $amount): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO budgets (user_id, month, year, amount)
             VALUES (:user_id, :month, :year, :amount)
             ON DUPLICATE KEY UPDATE amount = VALUES(amount)'
        );

        return $stmt->execute([
            ':user_id' => $userId,
            ':month' => $month,
            ':year' => $year,
            ':amount' => $amount,
        ]);
    }

    public function getMonthlyBudget(int $userId, int $month, int $year): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM budgets WHERE user_id = :user_id AND month = :month AND year = :year LIMIT 1'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':month' => $month,
            ':year' => $year,
        ]);

        $budget = $stmt->fetch();
        return $budget ?: null;
    }
}
