<?php

class SalaryDeductionModel extends Model
{
    /** All deductions for a specific salary config, ordered by sort_order. */
    public function byConfig(int $configId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM salary_deductions
             WHERE salary_config_id = :cid ORDER BY sort_order, id'
        );
        $stmt->execute([':cid' => $configId]);
        return $stmt->fetchAll();
    }

    /** All deductions for all configs belonging to a user (keyed by salary_config_id). */
    public function byUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT sd.* FROM salary_deductions sd
             INNER JOIN salary_configs sc ON sc.id = sd.salary_config_id
             WHERE sc.user_id = :uid ORDER BY sd.salary_config_id, sd.sort_order, sd.id'
        );
        $stmt->execute([':uid' => $userId]);
        $rows   = $stmt->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['salary_config_id']][] = $row;
        }
        return $result;
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO salary_deductions
             (salary_config_id, name, type, rate, base_type, base_cap, fixed_amount, sort_order)
             VALUES
             (:config_id, :name, :type, :rate, :base_type, :base_cap, :fixed_amount, :sort_order)'
        );
        return $stmt->execute([
            ':config_id'   => $data['salary_config_id'],
            ':name'        => $data['name'],
            ':type'        => $data['type'],
            ':rate'        => $data['rate'],
            ':base_type'   => $data['base_type'],
            ':base_cap'    => $data['base_cap'],
            ':fixed_amount'=> $data['fixed_amount'],
            ':sort_order'  => $data['sort_order'] ?? 0,
        ]);
    }

    /** Replace all deductions for a config (delete + re-insert). */
    public function replaceForConfig(int $configId, array $deductions): void
    {
        $this->deleteByConfig($configId);
        foreach ($deductions as $i => $ded) {
            $ded['salary_config_id'] = $configId;
            $ded['sort_order']       = $i;
            $this->create($ded);
        }
    }

    public function deleteByConfig(int $configId): void
    {
        $stmt = $this->db->prepare('DELETE FROM salary_deductions WHERE salary_config_id = :cid');
        $stmt->execute([':cid' => $configId]);
    }

    /**
     * Calculate deduction amounts given salary components.
     * Returns array of ['name'=>..., 'amount'=>..., 'formula'=>...]
     *
     * @param array $deductions  Rows from salary_deductions table
     * @param float $baseSalary  Gaji Pokok
     * @param float $fixedAllow  Tunjangan Tetap (position_allowance / Tunjangan Jabatan)
     */
    public static function calculateAmounts(array $deductions, float $baseSalary, float $fixedAllow): array
    {
        $results = [];
        foreach ($deductions as $ded) {
            if ($ded['type'] === 'fixed') {
                $amount    = (float) $ded['fixed_amount'];
                $formula   = 'Rp ' . number_format($amount, 0, ',', '.');
            } else {
                // percentage
                $base = $ded['base_type'] === 'basic_only' ? $baseSalary : $baseSalary + $fixedAllow;
                if ($ded['base_cap'] !== null && $base > (float)$ded['base_cap']) {
                    $base = (float) $ded['base_cap'];
                }
                $rate   = (float) $ded['rate'];
                $amount = round($base * $rate / 100, 0);

                $baseLabel = $ded['base_type'] === 'basic_only' ? 'Gaji Pokok' : 'Gaji Pokok + Tunj. Tetap';
                $capNote   = $ded['base_cap'] ? ' (max ' . number_format((float)$ded['base_cap'], 0, ',', '.') . ')' : '';
                $formula   = '(' . $baseLabel . $capNote . ') × ' . rtrim(rtrim(number_format($rate, 4, '.', ''), '0'), '.') . '%';
            }
            $results[] = [
                'name'    => $ded['name'],
                'amount'  => $amount,
                'formula' => $formula,
            ];
        }
        return $results;
    }
}
