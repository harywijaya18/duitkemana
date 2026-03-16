<?php

class IncomeModel extends Model
{
    public function allByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT ir.*, sc.name AS config_name
             FROM income_records ir
             LEFT JOIN salary_configs sc ON ir.salary_config_id = sc.id
             WHERE ir.user_id = :uid
             ORDER BY ir.period_year DESC, ir.period_month DESC, ir.id DESC'
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM income_records WHERE id = :id AND user_id = :uid LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function totalForMonth(int $userId, int $month, int $year): float
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(total_income), 0) AS total
             FROM income_records
             WHERE user_id = :uid AND period_month = :m AND period_year = :y'
        );
        $stmt->execute([':uid' => $userId, ':m' => $month, ':y' => $year]);
        return (float) $stmt->fetch()['total'];
    }

    public function totalBeforeMonth(int $userId, int $month, int $year): float
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(total_income), 0) AS total
             FROM income_records
             WHERE user_id = :uid
               AND (period_year < :y OR (period_year = :y2 AND period_month < :m))'
        );
        $stmt->execute([':uid' => $userId, ':y' => $year, ':y2' => $year, ':m' => $month]);
        return (float) $stmt->fetch()['total'];
    }

    public function avgMonthlyIncome(int $userId, int $months = 3): float
    {
        $months = max(1, (int) $months);
        $stmt = $this->db->prepare(
            "SELECT COALESCE(AVG(monthly_total), 0) AS avg_income FROM (
                SELECT SUM(total_income) AS monthly_total
                FROM income_records
                WHERE user_id = :uid
                GROUP BY period_year, period_month
                ORDER BY period_year DESC, period_month DESC
                LIMIT $months
            ) sub"
        );
        $stmt->execute([':uid' => $userId]);
        return (float) $stmt->fetch()['avg_income'];
    }

    public function monthlyIncomeLast(int $userId, int $months = 6): array
    {
        $months = max(1, (int) $months);
        $stmt = $this->db->prepare(
            "SELECT period_year AS year, period_month AS month, SUM(total_income) AS total
             FROM income_records
             WHERE user_id = :uid
             GROUP BY period_year, period_month
             ORDER BY period_year DESC, period_month DESC
             LIMIT $months"
        );
        $stmt->execute([':uid' => $userId]);
        return array_reverse($stmt->fetchAll());
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO income_records
             (user_id, salary_config_id, source_name, period_year, period_month,
              base_salary, meal_allowance, transport_allowance, position_allowance,
              other_income, working_days, total_income, total_deductions, received_date, notes)
             VALUES
             (:user_id, :salary_config_id, :source_name, :period_year, :period_month,
              :base_salary, :meal_allowance, :transport_allowance, :position_allowance,
              :other_income, :working_days, :total_income, :total_deductions, :received_date, :notes)'
        );
        return $stmt->execute([
            ':user_id'             => $data['user_id'],
            ':salary_config_id'    => $data['salary_config_id'],
            ':source_name'         => $data['source_name'],
            ':period_year'         => $data['period_year'],
            ':period_month'        => $data['period_month'],
            ':base_salary'         => $data['base_salary'],
            ':meal_allowance'      => $data['meal_allowance'],
            ':transport_allowance' => $data['transport_allowance'],
            ':position_allowance'  => $data['position_allowance'],
            ':other_income'        => $data['other_income'],
            ':working_days'        => $data['working_days'],
            ':total_income'        => $data['total_income'],
            ':total_deductions'    => $data['total_deductions'] ?? 0,
            ':received_date'       => $data['received_date'],
            ':notes'               => $data['notes'],
        ]);
    }

    public function update(int $id, int $userId, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE income_records SET
             salary_config_id = :salary_config_id,
             source_name = :source_name,
             period_year = :period_year,
             period_month = :period_month,
             base_salary = :base_salary,
             meal_allowance = :meal_allowance,
             transport_allowance = :transport_allowance,
             position_allowance = :position_allowance,
             other_income = :other_income,
             working_days = :working_days,
             total_income = :total_income,
             total_deductions = :total_deductions,
             received_date = :received_date,
             notes = :notes
             WHERE id = :id AND user_id = :user_id'
        );
        return $stmt->execute([
            ':id'                  => $id,
            ':user_id'             => $userId,
            ':salary_config_id'    => $data['salary_config_id'],
            ':source_name'         => $data['source_name'],
            ':period_year'         => $data['period_year'],
            ':period_month'        => $data['period_month'],
            ':base_salary'         => $data['base_salary'],
            ':meal_allowance'      => $data['meal_allowance'],
            ':transport_allowance' => $data['transport_allowance'],
            ':position_allowance'  => $data['position_allowance'],
            ':other_income'        => $data['other_income'],
            ':working_days'        => $data['working_days'],
            ':total_income'        => $data['total_income'],
            ':total_deductions'    => $data['total_deductions'] ?? 0,
            ':received_date'       => $data['received_date'],
            ':notes'               => $data['notes'],
        ]);
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM income_records WHERE id = :id AND user_id = :user_id'
        );
        return $stmt->execute([':id' => $id, ':user_id' => $userId]);
    }
}
