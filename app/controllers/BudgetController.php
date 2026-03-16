<?php

class BudgetController extends Controller
{
    private BudgetModel $budgetModel;
    private TransactionModel $transactionModel;
    private BudgetGoalModel $goalModel;

    public function __construct()
    {
        $this->budgetModel    = $this->model(BudgetModel::class);
        $this->transactionModel = $this->model(TransactionModel::class);
        $this->goalModel      = $this->model(BudgetGoalModel::class);
    }

    public function index(): void
    {
        require_auth();
        $user = auth_user();

        $month = (int) ($_GET['month'] ?? date('n'));
        $year = (int) ($_GET['year'] ?? date('Y'));

        $budgetRow = $this->budgetModel->getMonthlyBudget($user['id'], $month, $year);
        $budgetAmount = (float) ($budgetRow['amount'] ?? 0);
        $used = $this->transactionModel->totalThisMonth($user['id'], $month, $year);
        $remaining = max($budgetAmount - $used, 0);
        $remainingPct = $budgetAmount > 0 ? ($remaining / $budgetAmount) * 100 : 100;

        $this->view('budget', [
            'month' => $month,
            'year' => $year,
            'budgetAmount' => $budgetAmount,
            'used' => $used,
            'remaining' => $remaining,
            'remainingPct' => $remainingPct,
        ]);
    }

    public function save(): void
    {
        require_auth();
        if (!verify_csrf()) {
            redirect('/budget');
        }

        $user = auth_user();
        $month = (int) ($_POST['month'] ?? date('n'));
        $year = (int) ($_POST['year'] ?? date('Y'));
        $amount = (float) ($_POST['amount'] ?? 0);

        if ($month < 1 || $month > 12 || $year < 2000 || $amount <= 0) {
            flash('error', t('Invalid budget value.'));
            redirect('/budget');
        }

        $this->budgetModel->setMonthlyBudget($user['id'], $month, $year, $amount);
        flash('success', t('Monthly budget saved.'));
        redirect('/budget?month=' . $month . '&year=' . $year);
    }

    // ──────────────────────────────────────
    //  Budget Goals
    // ──────────────────────────────────────

    public function goals(): void
    {
        require_auth();
        $user  = auth_user();
        $month = (int) ($_GET['month'] ?? date('n'));
        $year  = (int) ($_GET['year']  ?? date('Y'));

        $goals      = $this->goalModel->getGoalsByPeriod($user['id'], $month, $year);
        $categories = $this->goalModel->getUserCategories($user['id']);

        $this->view('budget_goals', [
            'goals'      => $goals,
            'categories' => $categories,
            'month'      => $month,
            'year'       => $year,
        ]);
    }

    public function saveGoal(): void
    {
        require_auth();
        if (!verify_csrf()) {
            redirect('/budget/goals');
        }
        $user       = auth_user();
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $month      = (int) ($_POST['month']       ?? date('n'));
        $year       = (int) ($_POST['year']        ?? date('Y'));
        $raw        = str_replace(['.', ','], ['', '.'], $_POST['goal_amount'] ?? '');
        $amount     = (float) $raw;

        if ($categoryId < 1 || $month < 1 || $month > 12 || $year < 2000 || $amount <= 0) {
            flash('error', t('Invalid goal values.'));
            redirect('/budget/goals?month=' . $month . '&year=' . $year);
        }

        $this->goalModel->setGoal($user['id'], $categoryId, $month, $year, $amount);
        flash('success', t('Budget goal saved.'));
        redirect('/budget/goals?month=' . $month . '&year=' . $year);
    }

    public function deleteGoal(): void
    {
        require_auth();
        if (!verify_csrf()) {
            redirect('/budget/goals');
        }
        $user      = auth_user();
        $goalId    = (int) ($_POST['goal_id'] ?? 0);
        $month     = (int) ($_POST['month']   ?? date('n'));
        $year      = (int) ($_POST['year']    ?? date('Y'));

        if ($goalId > 0) {
            $this->goalModel->deleteGoal($goalId, $user['id']);
            flash('success', t('Goal removed.'));
        }
        redirect('/budget/goals?month=' . $month . '&year=' . $year);
    }
}
