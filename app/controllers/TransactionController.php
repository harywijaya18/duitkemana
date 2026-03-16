<?php

class TransactionController extends Controller
{
    private TransactionModel $transactionModel;
    private CategoryModel $categoryModel;
    private PaymentMethodModel $paymentMethodModel;

    public function __construct()
    {
        $this->transactionModel = $this->model(TransactionModel::class);
        $this->categoryModel = $this->model(CategoryModel::class);
        $this->paymentMethodModel = $this->model(PaymentMethodModel::class);
    }

    public function index(): void
    {
        require_auth();
        $user = auth_user();

        $filters = $this->collectFilters();

        $this->view('transactions', [
            'transactions'   => $this->transactionModel->filteredByUser($user['id'], $filters),
            'categories'     => $this->categoryModel->allByUser($user['id']),
            'paymentMethods' => $this->paymentMethodModel->all(),
            'filters'        => $filters,
        ]);
    }

    public function add(): void
    {
        require_auth();
        $user = auth_user();

        $this->view('add_transaction', [
            'categories' => $this->categoryModel->allByUser($user['id']),
            'paymentMethods' => $this->paymentMethodModel->all(),
        ]);
    }

    public function store(): void
    {
        require_auth();
        if (!verify_csrf()) {
            flash('error', t('Invalid request token.'));
            redirect('/transactions/add');
        }

        $user = auth_user();
        $data = $this->collectFormData();

        if ($data === null) {
            save_old_input($_POST);
            redirect('/transactions/add');
        }

        $data['user_id'] = $user['id'];
        if ($this->transactionModel->create($data)) {
            flash('success', t('Expense added successfully.'));
            clear_old_input();
            redirect('/transactions');
        }

        flash('error', t('Failed to save transaction.'));
        redirect('/transactions/add');
    }

    public function edit(): void
    {
        require_auth();
        $user = auth_user();
        $id = (int) ($_GET['id'] ?? 0);

        $transaction = $this->transactionModel->findById($id, $user['id']);
        if (!$transaction) {
            flash('error', t('Transaction not found.'));
            redirect('/transactions');
        }

        $this->view('edit_transaction', [
            'transaction' => $transaction,
            'categories' => $this->categoryModel->allByUser($user['id']),
            'paymentMethods' => $this->paymentMethodModel->all(),
        ]);
    }

    public function update(): void
    {
        require_auth();
        if (!verify_csrf()) {
            flash('error', t('Invalid request token.'));
            redirect('/transactions');
        }

        $user = auth_user();
        $id = (int) ($_POST['id'] ?? 0);
        $old = $this->transactionModel->findById($id, $user['id']);

        if (!$old) {
            flash('error', t('Transaction not found.'));
            redirect('/transactions');
        }

        $data = $this->collectFormData($old['receipt_image']);
        if ($data === null) {
            redirect('/transactions/edit?id=' . $id);
        }

        if ($this->transactionModel->update($id, $user['id'], $data)) {
            flash('success', t('Transaction updated.'));
            redirect('/transactions');
        }

        flash('error', t('Failed to update transaction.'));
        redirect('/transactions/edit?id=' . $id);
    }

    public function delete(): void
    {
        require_auth();
        if (!verify_csrf()) {
            redirect('/transactions');
        }

        $user = auth_user();
        $id = (int) ($_POST['id'] ?? 0);
        $this->transactionModel->delete($id, $user['id']);
        flash('success', t('Transaction deleted.'));
        redirect('/transactions');
    }

    private function collectFormData(?string $existingReceipt = null): ?array
    {
        $amount = $_POST['amount'] ?? '';
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $paymentMethodId = (int) ($_POST['payment_method_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $transactionDate = $_POST['transaction_date'] ?? '';

        if (!validate_amount($amount) || $categoryId <= 0 || $paymentMethodId <= 0 || !$this->isValidDate($transactionDate)) {
            flash('error', t('Please complete all required fields correctly.'));
            return null;
        }

        $receiptImage = $existingReceipt;
        if (!empty($_FILES['receipt_image']['name'])) {
            $uploaded = $this->uploadReceipt($_FILES['receipt_image']);
            if (!$uploaded) {
                flash('error', t('Receipt upload failed. Allowed: JPG, PNG, WEBP max 2MB.'));
                return null;
            }
            $receiptImage = $uploaded;
        }

        return [
            'category_id' => $categoryId,
            'amount' => (float) $amount,
            'payment_method_id' => $paymentMethodId,
            'description' => $description,
            'receipt_image' => $receiptImage,
            'transaction_date' => $transactionDate,
        ];
    }

    private function isValidDate(string $date): bool
    {
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        return $dt && $dt->format('Y-m-d') === $date;
    }

    private function uploadReceipt(array $file): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            return null;
        }

        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset($allowed[$mime])) {
            return null;
        }

        if (!is_dir(UPLOAD_PATH)) {
            mkdir(UPLOAD_PATH, 0755, true);
        }

        $filename = uniqid('receipt_', true) . '.' . $allowed[$mime];
        $target = UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            return null;
        }

        return $filename;
    }

    private function collectFilters(): array
    {
        $month = (int) ($_GET['month'] ?? 0);
        $year  = (int) ($_GET['year'] ?? 0);
        $categoryId = (int) ($_GET['category_id'] ?? 0);
        $paymentMethodId = (int) ($_GET['payment_method_id'] ?? 0);
        $search = trim((string) ($_GET['search'] ?? ''));
        $startDate = trim((string) ($_GET['start_date'] ?? ''));
        $endDate   = trim((string) ($_GET['end_date'] ?? ''));

        $startDate = $this->isValidDate($startDate) ? $startDate : null;
        $endDate   = $this->isValidDate($endDate) ? $endDate : null;

        if ($startDate && $endDate && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [
            'month'             => ($month >= 1 && $month <= 12) ? $month : null,
            'year'              => ($year >= 2000 && $year <= 2099) ? $year : null,
            'category_id'       => $categoryId > 0 ? $categoryId : null,
            'payment_method_id' => $paymentMethodId > 0 ? $paymentMethodId : null,
            'search'            => $search !== '' ? $search : null,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
        ];
    }
}
