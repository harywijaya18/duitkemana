<?php

class RecurringBillController extends Controller
{
    private RecurringBillModel $billModel;
    private CategoryModel $categoryModel;

    public function __construct()
    {
        $this->billModel     = $this->model(RecurringBillModel::class);
        $this->categoryModel = $this->model(CategoryModel::class);
    }

    public function index(): void
    {
        require_auth();
        $user     = auth_user();
        $curYear  = (int) date('Y');
        $curMonth = (int) date('n');

        $this->billModel->expireCompleted($user['id']);

        $this->view('recurring_bills', [
            'bills'     => $this->billModel->allByUser($user['id']),
            'thisMonth' => $this->billModel->activeForMonth($user['id'], $curYear, $curMonth),
            'curYear'   => $curYear,
            'curMonth'  => $curMonth,
        ]);
    }

    public function add(): void
    {
        require_auth();
        $user = auth_user();

        $this->view('recurring_bill_form', [
            'bill'       => null,
            'categories' => $this->categoryModel->allByUser($user['id']),
        ]);
    }

    public function store(): void
    {
        require_auth();
        if (!verify_csrf()) {
            flash('error', t('Invalid request token.'));
            redirect('/bills/add');
        }

        $user = auth_user();
        $data = $this->collectFormData();

        if ($data === null) {
            flash('error', t('Please complete all required fields correctly.'));
            redirect('/bills/add');
        }

        $data['user_id'] = $user['id'];

        if ($this->billModel->create($data)) {
            flash('success', t('Recurring bill saved.'));
            redirect('/bills');
        }

        flash('error', t('Failed to save recurring bill.'));
        redirect('/bills/add');
    }

    public function edit(): void
    {
        require_auth();
        $user = auth_user();
        $id   = (int) ($_GET['id'] ?? 0);
        $bill = $this->billModel->findById($id, $user['id']);

        if (!$bill) {
            flash('error', t('Recurring bill not found.'));
            redirect('/bills');
        }

        $this->view('recurring_bill_form', [
            'bill'       => $bill,
            'categories' => $this->categoryModel->allByUser($user['id']),
        ]);
    }

    public function update(): void
    {
        require_auth();
        if (!verify_csrf()) {
            flash('error', t('Invalid request token.'));
            redirect('/bills');
        }

        $user = auth_user();
        $id   = (int) ($_POST['id'] ?? 0);

        if (!$id || !$this->billModel->findById($id, $user['id'])) {
            flash('error', t('Recurring bill not found.'));
            redirect('/bills');
        }

        $data = $this->collectFormData();

        if ($data === null) {
            flash('error', t('Please complete all required fields correctly.'));
            redirect('/bills');
        }

        $data['is_active'] = isset($_POST['is_active']) ? 1 : 0;

        if ($this->billModel->update($id, $user['id'], $data)) {
            flash('success', t('Recurring bill updated.'));
        } else {
            flash('error', t('Failed to update recurring bill.'));
        }

        redirect('/bills');
    }

    public function delete(): void
    {
        require_auth();
        if (!verify_csrf()) {
            flash('error', t('Invalid request token.'));
            redirect('/bills');
        }

        $user = auth_user();
        $id   = (int) ($_POST['id'] ?? 0);

        if ($this->billModel->delete($id, $user['id'])) {
            flash('success', t('Recurring bill deleted.'));
        } else {
            flash('error', t('Failed to delete recurring bill.'));
        }

        redirect('/bills');
    }

    /**
     * Manually trigger generation of transactions for active recurring bills.
     * The model is idempotent so running this multiple times is safe.
     */
    public function generate(): void
    {
        require_auth();
        if (!verify_csrf()) {
            flash('error', t('Invalid request token.'));
            redirect('/bills');
        }

        $user  = auth_user();
        $year  = (int) ($_POST['year']  ?? date('Y'));
        $month = (int) ($_POST['month'] ?? date('n'));

        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2099) {
            flash('error', t('Invalid month or year.'));
            redirect('/bills');
        }

        $count = $this->billModel->generateForMonth($user['id'], $year, $month);
        $period = sprintf('%02d/%04d', $month, $year);

        if ($count > 0) {
            flash('success', t(':n recurring bill(s) generated.', ['n' => $count]));
        } else {
            flash('info', t('All recurring bills already generated for period :period.', ['period' => $period]));
        }

        redirect('/bills');
    }

    // ──────────────────────────────────────────────────────
    private function collectFormData(): ?array
    {
        $name           = trim($_POST['name']              ?? '');
        $amount         = (float) ($_POST['amount']        ?? 0);
        $categoryId     = (int) ($_POST['category_id']     ?? 0) ?: null;
        $startYear      = (int) ($_POST['start_year']      ?? date('Y'));
        $startMonth     = (int) ($_POST['start_month']     ?? date('n'));
        $endType        = $_POST['end_type']               ?? 'indefinite';
        $durationMonths = (int) ($_POST['duration_months'] ?? 0) ?: null;
        $endYear        = (int) ($_POST['end_year']        ?? 0) ?: null;
        $endMonth       = (int) ($_POST['end_month']       ?? 0) ?: null;
        $notes          = trim($_POST['notes']             ?? '') ?: null;

        if (!$name || $amount <= 0 || $startYear < 2000 || $startMonth < 1 || $startMonth > 12) {
            return null;
        }

        // Resolve end type
        if ($endType === 'duration') {
            $endYear  = null;
            $endMonth = null;
            // duration_months passed through; model will compute end_year/end_month
        } elseif ($endType === 'end_date') {
            $durationMonths = null;
            if (!$endYear || !$endMonth) {
                $endYear = null; $endMonth = null;
            }
        } else {
            // indefinite
            $durationMonths = null;
            $endYear        = null;
            $endMonth       = null;
        }

        return [
            'name'            => $name,
            'amount'          => $amount,
            'category_id'     => $categoryId,
            'start_year'      => $startYear,
            'start_month'     => $startMonth,
            'duration_months' => $durationMonths,
            'end_year'        => $endYear,
            'end_month'       => $endMonth,
            'notes'           => $notes,
        ];
    }
}
