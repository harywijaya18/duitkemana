<?php

class AdminMvpController extends Controller
{
    private UserModel $userModel;
    private AdminAuditLogModel $auditLogModel;
    private ApiTokenModel $apiTokenModel;
    private AdminOperationsModel $operationsModel;
    private AdminBillingModel $billingModel;
    private AdminAnalyticsModel $analyticsModel;
    private AdminSupportModel $supportModel;
    private AdminSettingsModel $settingsModel;
    private AdminTransactionsModel $transactionsModel;

    public function __construct()
    {
        $this->userModel = $this->model(UserModel::class);
        $this->auditLogModel = $this->model(AdminAuditLogModel::class);
        $this->apiTokenModel = $this->model(ApiTokenModel::class);
        $this->operationsModel = $this->model(AdminOperationsModel::class);
        $this->billingModel = $this->model(AdminBillingModel::class);
        $this->analyticsModel = $this->model(AdminAnalyticsModel::class);
        $this->supportModel = $this->model(AdminSupportModel::class);
        $this->settingsModel = $this->model(AdminSettingsModel::class);
        $this->transactionsModel = $this->model(AdminTransactionsModel::class);
    }

    public function users(): void
    {
        require_admin();

        $admin = auth_user();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = [
            'status' => trim((string) ($_GET['status'] ?? '')),
            'q' => trim((string) ($_GET['q'] ?? '')),
            'created_from' => trim((string) ($_GET['created_from'] ?? '')),
            'created_to' => trim((string) ($_GET['created_to'] ?? '')),
            'activity' => trim((string) ($_GET['activity'] ?? '')),
        ];

        $pagination = $this->userModel->paginateForAdmin($filters, $page, 20);
        $auditLogs = $this->auditLogModel->recent(12);

        $viewFile = APP_PATH . '/views/admin/users.php';
        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo 'View not found';
            return;
        }

        require APP_PATH . '/views/layouts/admin_header.php';
        require $viewFile;
        require APP_PATH . '/views/layouts/admin_footer.php';
    }

    public function suspendUser(): void
    {
        $this->changeUserStatus('suspended');
    }

    public function activateUser(): void
    {
        $this->changeUserStatus('active');
    }

    public function resetUserPassword(): void
    {
        require_admin();
        if (!verify_csrf()) {
            flash('error', t('Invalid request token.'));
            redirect('/admin/users');
        }

        $admin = auth_user();
        $target = $this->resolveTargetUserForAction($admin);
        if ($target === null) {
            return;
        }

        $temporaryPassword = $this->generateTemporaryPassword(10);
        $hash = password_hash($temporaryPassword, PASSWORD_DEFAULT);

        if (!$this->userModel->updatePassword((int) $target['id'], $hash)) {
            flash('error', 'Gagal reset password user.');
            redirect('/admin/users');
        }

        $this->auditLogModel->log(
            (int) ($admin['id'] ?? 0),
            'user_password_reset',
            (int) $target['id'],
            [
                'target_email' => $target['email'] ?? null,
            ]
        );

        flash('success', 'Password sementara untuk ' . ($target['email'] ?? 'user') . ': ' . $temporaryPassword);
        redirect('/admin/users');
    }

    public function resetUserApiTokens(): void
    {
        require_admin();
        if (!verify_csrf()) {
            flash('error', t('Invalid request token.'));
            redirect('/admin/users');
        }

        $admin = auth_user();
        $target = $this->resolveTargetUserForAction($admin);
        if ($target === null) {
            return;
        }

        $before = $this->apiTokenModel->countByUser((int) $target['id']);
        $revoked = $this->apiTokenModel->revokeByUser((int) $target['id']);

        $this->auditLogModel->log(
            (int) ($admin['id'] ?? 0),
            'user_api_tokens_revoked',
            (int) $target['id'],
            [
                'target_email' => $target['email'] ?? null,
                'token_count_before' => $before,
                'token_count_revoked' => $revoked,
            ]
        );

        flash('success', 'Berhasil revoke ' . $revoked . ' API token untuk ' . ($target['email'] ?? 'user') . '.');
        redirect('/admin/users');
    }

    public function subscriptions(): void
    {
        require_admin();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = [
            'status' => trim((string) ($_GET['status'] ?? '')),
            'plan_id' => (int) ($_GET['plan_id'] ?? 0),
            'q' => trim((string) ($_GET['q'] ?? '')),
        ];

        $result = $this->billingModel->listSubscriptions($filters, $page, 20);
        $billingAvailable = $this->billingModel->isAvailable();

        $viewFile = APP_PATH . '/views/admin/subscriptions.php';
        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo 'View not found';
            return;
        }

        require APP_PATH . '/views/layouts/admin_header.php';
        require $viewFile;
        require APP_PATH . '/views/layouts/admin_footer.php';
    }

    public function transactions(): void
    {
        require_admin();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = [
            'user_id' => (int) ($_GET['user_id'] ?? 0),
            'category_id' => (int) ($_GET['category_id'] ?? 0),
            'payment_method_id' => (int) ($_GET['payment_method_id'] ?? 0),
            'q' => trim((string) ($_GET['q'] ?? '')),
            'start_date' => trim((string) ($_GET['start_date'] ?? '')),
            'end_date' => trim((string) ($_GET['end_date'] ?? '')),
            'min_amount' => trim((string) ($_GET['min_amount'] ?? '')),
            'max_amount' => trim((string) ($_GET['max_amount'] ?? '')),
        ];

        $snapshot = $this->transactionsModel->snapshot($filters, $page, 25);

        $viewFile = APP_PATH . '/views/admin/transactions.php';
        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo 'View not found';
            return;
        }

        require APP_PATH . '/views/layouts/admin_header.php';
        require $viewFile;
        require APP_PATH . '/views/layouts/admin_footer.php';
    }

    public function exportSubscriptions(): void
    {
        require_admin();

        $filters = [
            'status' => trim((string) ($_GET['status'] ?? '')),
            'plan_id' => (int) ($_GET['plan_id'] ?? 0),
            'q' => trim((string) ($_GET['q'] ?? '')),
        ];

        $rows = $this->billingModel->exportSubscriptionsCsv($filters);
        $filename = 'subscriptions-' . date('Ymd-His') . '.xlsx';

        if (!class_exists('XLSXWriter')) {
            flash('error', 'Library Excel belum tersedia. Jalankan: composer install');
            redirect('/admin/subscriptions');
        }

        $writer = new XLSXWriter();
        $writer->setAuthor('DuitKemana');
        $writer->writeSheetHeader('Subscriptions', [
            'subscription_id' => 'integer',
            'user_name' => 'string',
            'user_email' => 'string',
            'plan_name' => 'string',
            'status' => 'string',
            'price_monthly' => 'price',
            'currency' => 'string',
            'current_period_end' => 'string',
            'last_invoice_status' => 'string',
            'last_invoice_due' => 'string',
        ]);

        foreach ($rows as $row) {
            $writer->writeSheetRow('Subscriptions', [
                (int) ($row['id'] ?? 0),
                (string) ($row['user_name'] ?? ''),
                (string) ($row['user_email'] ?? ''),
                (string) ($row['plan_name'] ?? ''),
                (string) ($row['status'] ?? ''),
                (float) ($row['price_monthly'] ?? 0),
                (string) ($row['currency'] ?? ''),
                (string) ($row['current_period_end'] ?? ''),
                (string) ($row['last_invoice_status'] ?? ''),
                (string) ($row['last_invoice_due'] ?? ''),
            ]);
        }

        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Pragma: no-cache');
        header('Expires: 0');

        $writer->writeToStdOut();
        exit;
    }

    public function operations(): void
    {
        require_admin();

        $year = (int) ($_GET['year'] ?? date('Y'));
        $month = (int) ($_GET['month'] ?? date('n'));
        $year = max(2000, min(2099, $year));
        $month = max(1, min(12, $month));

        $ops = $this->operationsModel->snapshot($year, $month);

        $viewFile = APP_PATH . '/views/admin/operations.php';
        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo 'View not found';
            return;
        }

        require APP_PATH . '/views/layouts/admin_header.php';
        require $viewFile;
        require APP_PATH . '/views/layouts/admin_footer.php';
    }

    public function analytics(): void
    {
        require_admin();

        $months = max(3, min(12, (int) ($_GET['months'] ?? 6)));
        $snapshot = $this->analyticsModel->snapshot($months);

        $viewFile = APP_PATH . '/views/admin/analytics.php';
        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo 'View not found';
            return;
        }

        require APP_PATH . '/views/layouts/admin_header.php';
        require $viewFile;
        require APP_PATH . '/views/layouts/admin_footer.php';
    }

    public function support(): void
    {
        require_admin();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = [
            'status' => trim((string) ($_GET['status'] ?? '')),
            'priority' => trim((string) ($_GET['priority'] ?? '')),
            'q' => trim((string) ($_GET['q'] ?? '')),
        ];

        $snapshot = $this->supportModel->snapshot($filters, $page, 20);
        $supportAvailable = $this->supportModel->isAvailable();

        $viewFile = APP_PATH . '/views/admin/support.php';
        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo 'View not found';
            return;
        }

        require APP_PATH . '/views/layouts/admin_header.php';
        require $viewFile;
        require APP_PATH . '/views/layouts/admin_footer.php';
    }

    public function settings(): void
    {
        require_admin();

        $snapshot = $this->settingsModel->snapshot();
        $settingsAvailable = $this->settingsModel->isAvailable();

        $viewFile = APP_PATH . '/views/admin/settings.php';
        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo 'View not found';
            return;
        }

        require APP_PATH . '/views/layouts/admin_header.php';
        require $viewFile;
        require APP_PATH . '/views/layouts/admin_footer.php';
    }

    public function saveSettings(): void
    {
        require_admin();
        if (!verify_csrf()) {
            flash('error', t('Invalid request token.'));
            redirect('/admin/settings');
        }

        $admin = auth_user();
        $payload = [
            'feature_enable_api_v1' => isset($_POST['feature_enable_api_v1']) ? '1' : '0',
            'feature_enable_support_center' => isset($_POST['feature_enable_support_center']) ? '1' : '0',
            'feature_enable_recurring_auto' => isset($_POST['feature_enable_recurring_auto']) ? '1' : '0',
            'security_admin_session_timeout_min' => (string) max(5, min(240, (int) ($_POST['security_admin_session_timeout_min'] ?? 30))),
            'security_max_failed_login' => (string) max(3, min(20, (int) ($_POST['security_max_failed_login'] ?? 5))),
            'security_password_reset_ttl_min' => (string) max(5, min(180, (int) ($_POST['security_password_reset_ttl_min'] ?? 30))),
        ];

        $saved = $this->settingsModel->saveMany($payload, (int) ($admin['id'] ?? 0));
        if (!$saved) {
            flash('error', 'Gagal menyimpan settings. Pastikan migrasi support/settings sudah dijalankan.');
            redirect('/admin/settings');
        }

        $this->auditLogModel->log(
            (int) ($admin['id'] ?? 0),
            'admin_settings_updated',
            null,
            ['keys' => array_keys($payload)]
        );

        flash('success', 'Admin settings berhasil disimpan.');
        redirect('/admin/settings');
    }

    private function renderMvpPage(string $activeMenu, string $title, string $description): void
    {
        require_admin();

        $user = auth_user();
        $viewFile = APP_PATH . '/views/admin/mvp_section.php';

        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo 'View not found';
            return;
        }

        require APP_PATH . '/views/layouts/admin_header.php';
        require $viewFile;
        require APP_PATH . '/views/layouts/admin_footer.php';
    }

    private function changeUserStatus(string $targetStatus): void
    {
        require_admin();
        if (!verify_csrf()) {
            flash('error', t('Invalid request token.'));
            redirect('/admin/users');
        }

        $admin = auth_user();
        $target = $this->resolveTargetUserForAction($admin);
        if ($target === null) {
            return;
        }
        $targetUserId = (int) $target['id'];

        $currentStatus = (string) ($target['status'] ?? 'active');
        if ($currentStatus === $targetStatus) {
            flash('success', 'Status user sudah sesuai.');
            redirect('/admin/users');
        }

        if (!$this->userModel->setStatus($targetUserId, $targetStatus)) {
            flash('error', 'Gagal mengubah status user. Pastikan migrasi admin sudah dijalankan.');
            redirect('/admin/users');
        }

        $this->auditLogModel->log(
            (int) ($admin['id'] ?? 0),
            'user_status_changed',
            $targetUserId,
            [
                'from' => $currentStatus,
                'to' => $targetStatus,
                'target_email' => $target['email'] ?? null,
            ]
        );

        flash('success', 'Status user berhasil diperbarui.');
        redirect('/admin/users');
    }

    private function resolveTargetUserForAction(?array $admin): ?array
    {
        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        if ($targetUserId <= 0) {
            flash('error', 'User target tidak valid.');
            redirect('/admin/users');
        }

        if ($targetUserId === (int) ($admin['id'] ?? 0)) {
            flash('error', 'Anda tidak bisa mengubah akun sendiri dari menu ini.');
            redirect('/admin/users');
        }

        $target = $this->userModel->findById($targetUserId);
        if (!$target) {
            flash('error', 'User tidak ditemukan.');
            redirect('/admin/users');
        }

        if (is_admin_user($target)) {
            flash('error', 'Akun admin dilindungi dan tidak bisa diubah dari menu ini.');
            redirect('/admin/users');
        }

        return $target;
    }

    private function generateTemporaryPassword(int $length = 10): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
        $max = strlen($alphabet) - 1;
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, $max)];
        }
        return $password;
    }
}
