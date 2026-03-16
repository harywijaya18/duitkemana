<?php

class IncomeController extends Controller
{
    private IncomeModel $incomeModel;
    private SalaryConfigModel $salaryConfigModel;
    private TransactionModel $transactionModel;
    private RecurringBillModel $recurringBillModel;

    public function __construct()
    {
        $this->incomeModel         = $this->model(IncomeModel::class);
        $this->salaryConfigModel   = $this->model(SalaryConfigModel::class);
        $this->transactionModel    = $this->model(TransactionModel::class);
        $this->recurringBillModel  = $this->model(RecurringBillModel::class);
    }

    public function index(): void
    {
        require_auth();
        $user = auth_user();

        $this->view('income', [
            'records' => $this->incomeModel->allByUser($user['id']),
        ]);
    }

    public function add(): void
    {
        require_auth();
        $user = auth_user();

        $this->view('income_form', [
            'record'  => null,
            'configs' => $this->salaryConfigModel->allByUser($user['id']),
        ]);
    }

    public function store(): void
    {
        require_auth();
        if (!verify_csrf()) {
            flash('error', t('Invalid request token.'));
            redirect('/income/add');
        }

        $user = auth_user();
        $data = $this->collectFormData();

        if ($data === null) {
            flash('error', t('Please complete all required fields correctly.'));
            redirect('/income/add');
        }

        $data['user_id'] = $user['id'];

        if ($this->incomeModel->create($data)) {
            flash('success', t('Income saved.'));
            redirect('/income');
        }

        flash('error', t('Failed to save income.'));
        redirect('/income/add');
    }

    public function edit(): void
    {
        require_auth();
        $user   = auth_user();
        $id     = (int) ($_GET['id'] ?? 0);
        $record = $this->incomeModel->findById($id, $user['id']);

        if (!$record) {
            flash('error', t('Income record not found.'));
            redirect('/income');
        }

        $this->view('income_form', [
            'record'  => $record,
            'configs' => $this->salaryConfigModel->allByUser($user['id']),
        ]);
    }

    public function update(): void
    {
        require_auth();
        if (!verify_csrf()) {
            flash('error', t('Invalid request token.'));
            redirect('/income');
        }

        $user = auth_user();
        $id   = (int) ($_POST['id'] ?? 0);

        if (!$id || !$this->incomeModel->findById($id, $user['id'])) {
            flash('error', t('Income record not found.'));
            redirect('/income');
        }

        $data = $this->collectFormData();

        if ($data === null) {
            flash('error', t('Please complete all required fields correctly.'));
            redirect('/income');
        }

        if ($this->incomeModel->update($id, $user['id'], $data)) {
            flash('success', t('Income updated.'));
        } else {
            flash('error', t('Failed to update income.'));
        }

        redirect('/income');
    }

    public function delete(): void
    {
        require_auth();
        if (!verify_csrf()) {
            flash('error', t('Invalid request token.'));
            redirect('/income');
        }

        $user = auth_user();
        $id   = (int) ($_POST['id'] ?? 0);

        if ($this->incomeModel->delete($id, $user['id'])) {
            flash('success', t('Income deleted.'));
        } else {
            flash('error', t('Failed to delete income.'));
        }

        redirect('/income');
    }

    /**
     * Financial projection for the next 6 months.
     * Uses active salary config to estimate income; avg expense for estimate.
     */
    public function projection(): void
    {
        require_auth();
        $user = auth_user();

        $salaryConfig = $this->salaryConfigModel->activeByUser($user['id']);
        $avgExpense   = $this->transactionModel->avgMonthlyExpense($user['id'], 3);
        $avgIncome    = $this->incomeModel->avgMonthlyIncome($user['id'], 3);

        $curMonth = (int) date('n');
        $curYear  = (int) date('Y');

        $curIncome  = $this->incomeModel->totalForMonth($user['id'], $curMonth, $curYear);
        $curExpense = $this->transactionModel->totalThisMonth($user['id'], $curMonth, $curYear);

        // Separate variable and recurring components for accurate projection.
        // recurring avg tells us how much of the historical expense avg was recurring bills.
        // variableExpenseAvg is the 'other' spending (food, etc.) that stays roughly constant.
        $historicRecurringAvg = $this->recurringBillModel->avgMonthlyTotal($user['id'], 3);
        $variableExpenseAvg   = max(0.0, $avgExpense - $historicRecurringAvg);

        // Build projection for next 6 months
        $projections = [];
        for ($i = 1; $i <= 6; $i++) {
            $ts = mktime(0, 0, 0, $curMonth + $i, 1, $curYear);
            $m  = (int) date('n', $ts);
            $y  = (int) date('Y', $ts);

            if ($salaryConfig) {
                $wd = count_working_days(
                    $y, $m,
                    (int) $salaryConfig['cutoff_day'],
                    (int) $salaryConfig['working_days_per_week']
                );
                $estIncome   = (float) $salaryConfig['base_salary']
                    + (float) $salaryConfig['meal_allowance_per_day']      * $wd['working_days']
                    + (float) $salaryConfig['transport_allowance_per_day'] * $wd['working_days']
                    + (float) $salaryConfig['position_allowance'];
                $workingDays = $wd['working_days'];
            } else {
                $estIncome   = $avgIncome;
                $workingDays = 0;
            }

            // Recurring bills active in this projected month
            $recurringBills = $this->recurringBillModel->activeForMonth($user['id'], $y, $m);
            $recurringTotal = (float) array_sum(array_column($recurringBills, 'amount'));
            $estExpense     = $variableExpenseAvg + $recurringTotal;

            $projections[] = [
                'month'           => $m,
                'year'            => $y,
                'est_income'      => $estIncome,
                'est_expense'     => $estExpense,
                'est_savings'     => $estIncome - $estExpense,
                'working_days'    => $workingDays,
                'recurring_total' => $recurringTotal,
                'recurring_bills' => $recurringBills,
            ];
        }

        // Add running cumulative (starting from current month balance)
        $cumulative = $curIncome - $curExpense;
        foreach ($projections as &$p) {
            $cumulative      += $p['est_savings'];
            $p['cumulative']  = $cumulative;
        }
        unset($p);

        $this->view('projection', [
            'salaryConfig'         => $salaryConfig,
            'avgExpense'           => $avgExpense,
            'avgIncome'            => $avgIncome,
            'variableExpenseAvg'   => $variableExpenseAvg,
            'historicRecurringAvg' => $historicRecurringAvg,
            'curIncome'            => $curIncome,
            'curExpense'           => $curExpense,
            'projections'          => $projections,
            'histExpense'          => $this->transactionModel->monthlyExpenseLast($user['id'], 6),
            'histIncome'           => $this->incomeModel->monthlyIncomeLast($user['id'], 6),
        ]);
    }

    private function collectFormData(): ?array
    {
        $configId         = (int)   ($_POST['salary_config_id']   ?? 0) ?: null;
        $sourceName       = trim(    $_POST['source_name']        ?? '');
        $periodYear       = (int)   ($_POST['period_year']        ?? date('Y'));
        $periodMonth      = (int)   ($_POST['period_month']       ?? date('n'));
        $baseSalary       = (float) ($_POST['base_salary']        ?? 0);
        $mealAllowance    = (float) ($_POST['meal_allowance']     ?? 0);
        $transportAllow   = (float) ($_POST['transport_allowance'] ?? 0);
        $positionAllow    = (float) ($_POST['position_allowance'] ?? 0);
        $otherIncome      = (float) ($_POST['other_income']       ?? 0);
        $workingDays      = (int)   ($_POST['working_days']       ?? 0);
        $receivedDate     = trim(    $_POST['received_date']      ?? '') ?: null;
        $notes            = trim(    $_POST['notes']              ?? '') ?: null;

        if (!$sourceName || $periodYear < 2000 || $periodMonth < 1 || $periodMonth > 12) {
            return null;
        }

        $totalDeductions = (float) ($_POST['total_deductions'] ?? 0);
        $totalIncome     = $baseSalary + $mealAllowance + $transportAllow + $positionAllow + $otherIncome;

        return [
            'salary_config_id'    => $configId,
            'source_name'         => $sourceName,
            'period_year'         => $periodYear,
            'period_month'        => $periodMonth,
            'base_salary'         => $baseSalary,
            'meal_allowance'      => $mealAllowance,
            'transport_allowance' => $transportAllow,
            'position_allowance'  => $positionAllow,
            'other_income'        => $otherIncome,
            'working_days'        => $workingDays,
            'total_income'        => $totalIncome,
            'total_deductions'    => $totalDeductions,
            'received_date'       => $receivedDate,
            'notes'               => $notes,
        ];
    }
}
