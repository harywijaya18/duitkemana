<?php

class ApiTransactionController extends ApiController
{
    private TransactionModel $transactionModel;

    public function __construct()
    {
        $this->transactionModel = $this->model(TransactionModel::class);
    }

    public function index(): void
    {
        $this->requireApiAuth();
        $user = $this->apiUser();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 20)));
        $cursorRaw = trim((string) ($_GET['cursor'] ?? ''));
        $cursor = ctype_digit($cursorRaw) ? (int) $cursorRaw : null;

        $total = $this->transactionModel->countByUser((int) $user['id']);
        $result = $this->transactionModel->paginateByUser((int) $user['id'], $page, $perPage, $cursor);
        $rows = $result['items'] ?? [];
        foreach ($rows as &$row) {
            $row['receipt_url'] = receipt_url($row['receipt_image'] ?? null);
        }
        unset($row);

        $this->success([
            'transactions' => $rows,
            'pagination' => [
                'mode' => ($cursor !== null && $cursor > 0) ? 'cursor' : 'page',
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => max(1, (int) ceil($total / $perPage)),
                'cursor' => ($cursor !== null && $cursor > 0) ? $cursor : null,
                'next_cursor' => $result['next_cursor'] ?? null,
                'has_more' => (bool) ($result['has_more'] ?? false),
            ],
        ]);
    }

    public function create(): void
    {
        $this->requireApiAuth();
        $user = $this->apiUser();
        $input = api_read_json_body();

        $validation = $this->validateInput($input);
        if ($validation !== true) {
            $this->error('Validation failed', 422, $validation, 'TX_VALIDATION_FAILED');
        }

        $payload = [
            'user_id' => $user['id'],
            'category_id' => (int) $input['category_id'],
            'amount' => (float) $input['amount'],
            'payment_method_id' => (int) $input['payment_method_id'],
            'description' => trim((string) ($input['description'] ?? '')),
            'receipt_image' => $this->sanitizeReceiptImage($input['receipt_image'] ?? null),
            'transaction_date' => $input['transaction_date'],
        ];

        $this->transactionModel->create($payload);
        $this->success([], 'Transaction created', 201);
    }

    public function update(): void
    {
        $this->requireApiAuth();
        $user = $this->apiUser();
        $input = api_read_json_body();

        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Invalid transaction id', 422, [], 'TX_INVALID_ID');
        }

        $validation = $this->validateInput($input);
        if ($validation !== true) {
            $this->error('Validation failed', 422, $validation, 'TX_VALIDATION_FAILED');
        }

        $existing = $this->transactionModel->findById($id, $user['id']);
        if (!$existing) {
            $this->error('Transaction not found', 404, [], 'TX_NOT_FOUND');
        }

        $payload = [
            'category_id' => (int) $input['category_id'],
            'amount' => (float) $input['amount'],
            'payment_method_id' => (int) $input['payment_method_id'],
            'description' => trim((string) ($input['description'] ?? '')),
            'receipt_image' => $this->sanitizeReceiptImage($input['receipt_image'] ?? $existing['receipt_image']),
            'transaction_date' => $input['transaction_date'],
        ];

        $this->transactionModel->update($id, $user['id'], $payload);
        $this->success([], 'Transaction updated');
    }

    public function delete(): void
    {
        $this->requireApiAuth();
        $user = $this->apiUser();
        $input = api_read_json_body();

        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0) {
            $this->error('Invalid transaction id', 422, [], 'TX_INVALID_ID');
        }

        $this->transactionModel->delete($id, $user['id']);
        $this->success([], 'Transaction deleted');
    }

    public function uploadReceipt(): void
    {
        $this->requireApiAuth();

        if (empty($_FILES['receipt'])) {
            $this->error('Validation failed', 422, ['receipt file is required'], 'TX_RECEIPT_REQUIRED');
        }

        $filename = $this->uploadFile($_FILES['receipt']);
        if (!$filename) {
            $this->error('Upload failed. Allowed: JPG, PNG, WEBP max 2MB.', 422, [], 'TX_RECEIPT_UPLOAD_FAILED');
        }

        $this->success([
            'receipt_image' => $filename,
            'receipt_url' => receipt_url($filename),
        ], 'Receipt uploaded');
    }

    private function validateInput(array $input)
    {
        $errors = [];
        if (!isset($input['amount']) || !validate_amount($input['amount'])) {
            $errors[] = 'amount must be > 0';
        }
        if ((int) ($input['category_id'] ?? 0) <= 0) {
            $errors[] = 'category_id is required';
        }
        if ((int) ($input['payment_method_id'] ?? 0) <= 0) {
            $errors[] = 'payment_method_id is required';
        }
        if (!$this->isValidDate((string) ($input['transaction_date'] ?? ''))) {
            $errors[] = 'transaction_date must be Y-m-d';
        }

        return empty($errors) ? true : $errors;
    }

    private function isValidDate(string $date): bool
    {
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        return $dt && $dt->format('Y-m-d') === $date;
    }

    private function sanitizeReceiptImage($receiptImage): ?string
    {
        if (!is_string($receiptImage) || trim($receiptImage) === '') {
            return null;
        }

        $filename = basename(trim($receiptImage));
        return $filename !== '' ? $filename : null;
    }

    private function uploadFile(array $file): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
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

        $filename = uniqid('receipt_api_', true) . '.' . $allowed[$mime];
        $target = UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            return null;
        }

        return $filename;
    }
}
