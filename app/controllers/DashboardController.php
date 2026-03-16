<?php

class DashboardController extends Controller
{
    private TransactionModel $transactionModel;
    private BudgetModel $budgetModel;
    private IncomeModel $incomeModel;
    private RecurringBillModel $recurringBillModel;

    public function __construct()
    {
        $this->transactionModel    = $this->model(TransactionModel::class);
        $this->budgetModel         = $this->model(BudgetModel::class);
        $this->incomeModel         = $this->model(IncomeModel::class);
        $this->recurringBillModel  = $this->model(RecurringBillModel::class);
    }

    public function index(): void
    {
        require_auth();

        $user  = auth_user();
        $month = (int) date('n');
        $year  = (int) date('Y');

        // Auto-expire completed bills, then silently generate this month's transactions
        $this->recurringBillModel->expireCompleted($user['id']);
        $this->recurringBillModel->generateForMonth($user['id'], $year, $month);

        $todayExpense = $this->transactionModel->totalToday($user['id']);
        $monthExpense = $this->transactionModel->totalThisMonth($user['id'], $month, $year);
        $monthIncome  = $this->incomeModel->totalForMonth($user['id'], $month, $year);
        $latestIncomeRows = $this->incomeModel->monthlyIncomeLast($user['id'], 1);
        $latestIncomeMonth = $latestIncomeRows[0] ?? null;

        $prevTotalIncome  = $this->incomeModel->totalBeforeMonth($user['id'], $month, $year);
        $prevTotalExpense = $this->transactionModel->totalBeforeMonth($user['id'], $month, $year);
        $carryover        = $prevTotalIncome - $prevTotalExpense;

        $balance      = $carryover + $monthIncome - $monthExpense;
        $savingsRate  = $monthIncome > 0 ? round(($monthIncome - $monthExpense) / $monthIncome * 100, 1) : 0;

        $budgetRow    = $this->budgetModel->getMonthlyBudget($user['id'], $month, $year);
        $budgetAmount = (float) ($budgetRow['amount'] ?? 0);
        $remaining    = max($budgetAmount - $monthExpense, 0);
        $remainingPct = $budgetAmount > 0 ? ($remaining / $budgetAmount) * 100 : 100;

        $recurringThisMonth = $this->recurringBillModel->totalForMonth($user['id'], $year, $month);

        $topCategory = $this->transactionModel->topCategoryThisMonth($user['id'], $month, $year);
        $prevMonth   = $month === 1 ? 12 : $month - 1;
        $prevYear    = $month === 1 ? $year - 1 : $year;
        $prevTotal   = $this->transactionModel->totalThisMonth($user['id'], $prevMonth, $prevYear);
        $growth      = $prevTotal > 0 ? (($monthExpense - $prevTotal) / $prevTotal) * 100 : 0;

        $insights = [];
        if ($topCategory) {
            $insights[] = t(':category is your top expense category this month.', ['category' => $topCategory['name']]);
        }
        if ($prevTotal > 0 && abs($growth) >= 5) {
            $direction  = $growth > 0 ? t('more') : t('less');
            $insights[] = t('You spent :pct% :dir than last month.', ['pct' => abs((int) round($growth)), 'dir' => $direction]);
        }
        if ($budgetAmount > 0 && $remainingPct < 20) {
            $insights[] = t('Warning: Remaining budget is below 20%.');
        }
        if ($monthIncome > 0 && $savingsRate < 10) {
            $insights[] = t('Heads up: Your savings rate is below 10% this month.');
        }
        if ($monthIncome > 0 && $savingsRate >= 20) {
            $insights[] = t('Great job! You are saving :pct% of your income this month.', ['pct' => $savingsRate]);
        }
        if ($recurringThisMonth > 0) {
            $insights[] = t('Recurring bills this month: :amt.', ['amt' => currency_format($recurringThisMonth)]);
        }
        if ($latestIncomeMonth
            && ((int) $latestIncomeMonth['month'] !== $month || (int) $latestIncomeMonth['year'] !== $year)
        ) {
            $insights[] = t(
                'Latest income record is for :period, so it is not included in this month dashboard.',
                ['period' => sprintf('%02d/%04d', (int) $latestIncomeMonth['month'], (int) $latestIncomeMonth['year'])]
            );
        }

        $this->view('dashboard', [
            'user'               => $user,
            'todayExpense'       => $todayExpense,
            'monthExpense'       => $monthExpense,
            'monthIncome'        => $monthIncome,
            'carryover'          => $carryover,
            'balance'            => $balance,
            'savingsRate'        => $savingsRate,
            'budgetAmount'       => $budgetAmount,
            'remaining'          => $remaining,
            'remainingPct'       => $remainingPct,
            'currentMonth'       => $month,
            'currentYear'        => $year,
            'latestIncomeMonth'  => $latestIncomeMonth,
            'recentTransactions'  => $this->transactionModel->recentByUser($user['id'], 5),
            'insights'            => $insights,
            'recurringThisMonth'  => $recurringThisMonth,
        ]);
    }
}
