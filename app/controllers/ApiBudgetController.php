<?php

class ApiBudgetController extends ApiController
{
    private BudgetModel $budgetModel;
    private TransactionModel $transactionModel;

    public function __construct()
    {
        $this->budgetModel = $this->model(BudgetModel::class);
        $this->transactionModel = $this->model(TransactionModel::class);
    }

    public function get(): void
    {
        $this->requireApiAuth();
        $user = $this->apiUser();

        $month = (int) ($_GET['month'] ?? date('n'));
        $year = (int) ($_GET['year'] ?? date('Y'));

        if ($month < 1 || $month > 12 || $year < 2000) {
            $this->error('Invalid month/year', 422, [], 'BUDGET_INVALID_PERIOD');
        }

        $budget = $this->budgetModel->getMonthlyBudget($user['id'], $month, $year);
        $amount = (float) ($budget['amount'] ?? 0);
        $used = $this->transactionModel->totalThisMonth($user['id'], $month, $year);
        $remaining = max($amount - $used, 0);

        $this->success([
            'month' => $month,
            'year' => $year,
            'budget_amount' => $amount,
            'used_budget' => $used,
            'remaining_budget' => $remaining,
            'is_warning' => $amount > 0 ? (($remaining / $amount) * 100 < 20) : false,
        ]);
    }

    public function save(): void
    {
        $this->requireApiAuth();
        $user = $this->apiUser();
        $input = api_read_json_body();

        $month = (int) ($input['month'] ?? date('n'));
        $year = (int) ($input['year'] ?? date('Y'));
        $amount = (float) ($input['amount'] ?? 0);

        if ($month < 1 || $month > 12 || $year < 2000 || $amount <= 0) {
            $this->error('Validation failed', 422, ['month/year/amount is invalid'], 'BUDGET_VALIDATION_FAILED');
        }

        $this->budgetModel->setMonthlyBudget($user['id'], $month, $year, $amount);
        $this->success([], 'Budget saved');
    }
}
