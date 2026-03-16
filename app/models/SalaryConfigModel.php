<?php

class SalaryConfigModel extends Model
{
    public function allByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM salary_configs WHERE user_id = :uid ORDER BY is_active DESC, id DESC'
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM salary_configs WHERE id = :id AND user_id = :uid LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function activeByUser(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM salary_configs WHERE user_id = :uid AND is_active = 1 ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO salary_configs
             (user_id, name, base_salary, meal_allowance_per_day, transport_allowance_per_day,
              position_allowance, cutoff_day, working_days_per_week, is_active)
             VALUES
             (:user_id, :name, :base_salary, :meal_allowance_per_day, :transport_allowance_per_day,
              :position_allowance, :cutoff_day, :working_days_per_week, :is_active)'
        );
        return $stmt->execute([
            ':user_id'                     => $data['user_id'],
            ':name'                        => $data['name'],
            ':base_salary'                 => $data['base_salary'],
            ':meal_allowance_per_day'      => $data['meal_allowance_per_day'],
            ':transport_allowance_per_day' => $data['transport_allowance_per_day'],
            ':position_allowance'          => $data['position_allowance'],
            ':cutoff_day'                  => $data['cutoff_day'],
            ':working_days_per_week'       => $data['working_days_per_week'],
            ':is_active'                   => $data['is_active'],
        ]);
    }

    public function update(int $id, int $userId, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE salary_configs SET
             name = :name,
             base_salary = :base_salary,
             meal_allowance_per_day = :meal_allowance_per_day,
             transport_allowance_per_day = :transport_allowance_per_day,
             position_allowance = :position_allowance,
             cutoff_day = :cutoff_day,
             working_days_per_week = :working_days_per_week,
             is_active = :is_active
             WHERE id = :id AND user_id = :user_id'
        );
        return $stmt->execute([
            ':id'                          => $id,
            ':user_id'                     => $userId,
            ':name'                        => $data['name'],
            ':base_salary'                 => $data['base_salary'],
            ':meal_allowance_per_day'      => $data['meal_allowance_per_day'],
            ':transport_allowance_per_day' => $data['transport_allowance_per_day'],
            ':position_allowance'          => $data['position_allowance'],
            ':cutoff_day'                  => $data['cutoff_day'],
            ':working_days_per_week'       => $data['working_days_per_week'],
            ':is_active'                   => $data['is_active'],
        ]);
    }

    public function deactivateAll(int $userId): void
    {
        $stmt = $this->db->prepare('UPDATE salary_configs SET is_active = 0 WHERE user_id = :uid');
        $stmt->execute([':uid' => $userId]);
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM salary_configs WHERE id = :id AND user_id = :user_id'
        );
        return $stmt->execute([':id' => $id, ':user_id' => $userId]);
    }
}
