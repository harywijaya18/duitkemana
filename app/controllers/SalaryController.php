<?php

class SalaryController extends Controller
{
    private SalaryConfigModel $salaryConfigModel;
    private SalaryDeductionModel $deductionModel;

    public function __construct()
    {
        $this->salaryConfigModel = $this->model(SalaryConfigModel::class);
        $this->deductionModel    = $this->model(SalaryDeductionModel::class);
    }

    public function index(): void
    {
        require_auth();
        $user    = auth_user();
        $configs = $this->salaryConfigModel->allByUser($user['id']);
        $dedMap  = $this->deductionModel->byUser($user['id']);
        foreach ($configs as &$cfg) {
            $cfg['deductions'] = $dedMap[(int)$cfg['id']] ?? [];
        }
        unset($cfg);

        $this->view('salary_config', [
            'configs' => $configs,
        ]);
    }

    public function store(): void
    {
        require_auth();
        if (!verify_csrf()) {
            flash('error', t('Invalid request token.'));
            redirect('/salary-config');
        }

        $user = auth_user();
        $data = $this->collectData($user['id']);

        if ($data === null) {
            flash('error', t('Please complete all required fields correctly.'));
            redirect('/salary-config');
        }

        if ($data['is_active']) {
            $this->salaryConfigModel->deactivateAll($user['id']);
        }

        if ($this->salaryConfigModel->create($data)) {
            $newId = (int) $this->db->lastInsertId();
            $deds  = $this->parseDeductions();
            if ($newId && $deds !== null) {
                $this->deductionModel->replaceForConfig($newId, $deds);
            }
            flash('success', t('Salary config saved.'));
        } else {
            flash('error', t('Failed to save salary config.'));
        }

        redirect('/salary-config');
    }

    public function update(): void
    {
        require_auth();
        if (!verify_csrf()) {
            flash('error', t('Invalid request token.'));
            redirect('/salary-config');
        }

        $user  = auth_user();
        $id    = (int) ($_POST['id'] ?? 0);
        $data  = $this->collectData($user['id']);

        if (!$id || $data === null) {
            flash('error', t('Please complete all required fields correctly.'));
            redirect('/salary-config');
        }

        if ($data['is_active']) {
            $this->salaryConfigModel->deactivateAll($user['id']);
        }

        if ($this->salaryConfigModel->update($id, $user['id'], $data)) {
            $deds = $this->parseDeductions();
            if ($deds !== null) {
                $this->deductionModel->replaceForConfig($id, $deds);
            }
            flash('success', t('Salary config updated.'));
        } else {
            flash('error', t('Failed to update salary config.'));
        }

        redirect('/salary-config');
    }

    public function delete(): void
    {
        require_auth();
        if (!verify_csrf()) {
            flash('error', t('Invalid request token.'));
            redirect('/salary-config');
        }

        $user = auth_user();
        $id   = (int) ($_POST['id'] ?? 0);

        if ($this->salaryConfigModel->delete($id, $user['id'])) {
            flash('success', t('Salary config deleted.'));
        } else {
            flash('error', t('Failed to delete salary config.'));
        }

        redirect('/salary-config');
    }

    /**
     * AJAX: calculate working days + income components for a given config/month/year.
     * GET /salary-config/calculate?config_id=1&year=2026&month=3
     */
    public function calculate(): void
    {
        require_auth();
        $user     = auth_user();
        $configId = (int) ($_GET['config_id'] ?? 0);
        $year     = (int) ($_GET['year']      ?? date('Y'));
        $month    = (int) ($_GET['month']     ?? date('n'));

        if (!$configId || $month < 1 || $month > 12 || $year < 2000) {
            $this->json(['error' => 'Invalid params'], 400);
            return;
        }

        $config = $this->salaryConfigModel->findById($configId, $user['id']);
        if (!$config) {
            $this->json(['error' => 'Config not found'], 404);
            return;
        }

        $wd        = count_working_days($year, $month, (int) $config['cutoff_day'], (int) $config['working_days_per_week']);
        $base      = (float) $config['base_salary'];
        $meal      = round((float) $config['meal_allowance_per_day'] * $wd['working_days'], 0);
        $transport = round((float) $config['transport_allowance_per_day'] * $wd['working_days'], 0);
        $position  = (float) $config['position_allowance'];

        $grossIncome     = $base + $meal + $transport + $position;
        $rawDeds         = $this->deductionModel->byConfig($configId);
        $deductions      = SalaryDeductionModel::calculateAmounts($rawDeds, $base, $position);
        $totalDeductions = array_sum(array_column($deductions, 'amount'));

        $this->json([
            'working_days'     => $wd['working_days'],
            'period_start'     => $wd['period_start'],
            'period_end'       => $wd['period_end'],
            'base_salary'      => $base,
            'meal_allowance_per_day' => (float) $config['meal_allowance_per_day'],
            'meal_allowance'   => $meal,
            'transport_allowance_per_day' => (float) $config['transport_allowance_per_day'],
            'transport_allowance' => $transport,
            'position_allowance'  => $position,
            'total_income'        => $grossIncome,
            'deductions'          => $deductions,
            'total_deductions'    => $totalDeductions,
            'net_income'          => $grossIncome - $totalDeductions,
        ]);
    }

    private function collectData(int $userId): ?array
    {
        $name         = trim($_POST['name']                        ?? '');
        $baseSalary   = (float) ($_POST['base_salary']            ?? 0);
        $mealDay      = (float) ($_POST['meal_allowance_per_day'] ?? 0);
        $transportDay = (float) ($_POST['transport_allowance_per_day'] ?? 0);
        $position     = (float) ($_POST['position_allowance']     ?? 0);
        $cutoffDay    = (int)   ($_POST['cutoff_day']             ?? 0);
        $daysPerWeek  = (int)   ($_POST['working_days_per_week']  ?? 5);
        $isActive     = isset($_POST['is_active']) ? 1 : 0;

        if (!$name || $baseSalary <= 0) {
            return null;
        }

        $cutoffDay   = max(0, min(28, $cutoffDay));
        $daysPerWeek = in_array($daysPerWeek, [5, 6]) ? $daysPerWeek : 5;

        return [
            'user_id'                     => $userId,
            'name'                        => $name,
            'base_salary'                 => $baseSalary,
            'meal_allowance_per_day'      => $mealDay,
            'transport_allowance_per_day' => $transportDay,
            'position_allowance'          => $position,
            'cutoff_day'                  => $cutoffDay,
            'working_days_per_week'       => $daysPerWeek,
            'is_active'                   => $isActive,
        ];
    }

    /**
     * Parse deduction rows from POST arrays (ded_name[], ded_type[], etc.).
     * Returns array of deduction data, or null when arrays absent from POST.
     */
    private function parseDeductions(): ?array
    {
        $names = $_POST['ded_name'] ?? null;
        if ($names === null) return null;

        $names     = (array) $names;
        $types     = (array) ($_POST['ded_type']         ?? []);
        $rates     = (array) ($_POST['ded_rate']         ?? []);
        $baseTypes = (array) ($_POST['ded_base_type']    ?? []);
        $baseCaps  = (array) ($_POST['ded_base_cap']     ?? []);
        $fixedAmts = (array) ($_POST['ded_fixed_amount'] ?? []);

        $results = [];
        foreach ($names as $i => $name) {
            $name = trim($name);
            if ($name === '') continue;
            $type     = in_array($types[$i] ?? '', ['percentage','fixed']) ? $types[$i] : 'percentage';
            $baseType = in_array($baseTypes[$i] ?? '', ['basic_only','basic_fixed']) ? $baseTypes[$i] : 'basic_fixed';
            $cap      = isset($baseCaps[$i]) && (float)$baseCaps[$i] > 0 ? (float)$baseCaps[$i] : null;
            $results[] = [
                'name'         => $name,
                'type'         => $type,
                'rate'         => $type === 'percentage' ? (float)($rates[$i] ?? 0) : null,
                'base_type'    => $baseType,
                'base_cap'     => $cap,
                'fixed_amount' => $type === 'fixed' ? (float)($fixedAmts[$i] ?? 0) : null,
            ];
        }
        return $results;
    }
}
