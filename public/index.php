<?php

require_once dirname(__DIR__) . '/config/app.php';

$requestId = bin2hex(random_bytes(8));

$writeAppLog = static function (string $message) use ($requestId): void {
	$line = '[' . date('Y-m-d H:i:s') . '][request:' . $requestId . '] ' . $message . PHP_EOL;
	if (defined('APP_LOG_FILE')) {
		@file_put_contents(APP_LOG_FILE, $line, FILE_APPEND);
	}
	error_log('[request:' . $requestId . '] ' . $message);
};

$writeAppLog('Incoming request: ' . ($_SERVER['REQUEST_METHOD'] ?? 'GET') . ' ' . ($_SERVER['REQUEST_URI'] ?? '/'));

set_exception_handler(static function (\Throwable $e) use ($requestId, $writeAppLog): void {
	$writeAppLog('Uncaught exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
	if (!headers_sent()) {
		http_response_code(500);
		header('Content-Type: text/plain; charset=UTF-8');
	}
	echo 'Terjadi error server. Silakan coba lagi. Ref: ' . $requestId;
	exit;
});

register_shutdown_function(static function () use ($requestId, $writeAppLog): void {
	$error = error_get_last();
	if ($error === null) {
		return;
	}

	if (!in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
		return;
	}

	$writeAppLog('Fatal error: ' . ($error['message'] ?? 'unknown') . ' in ' . ($error['file'] ?? '-') . ':' . ($error['line'] ?? 0));
	if (!headers_sent()) {
		http_response_code(500);
		header('Content-Type: text/plain; charset=UTF-8');
	}
	echo 'Terjadi error server. Silakan coba lagi. Ref: ' . $requestId;
});

$router = new Router();

$router->get('/', [DashboardController::class, 'index']);

$router->get('/login', [AuthController::class, 'login']);
$router->post('/login', [AuthController::class, 'doLogin']);
$router->get('/register', [AuthController::class, 'register']);
$router->post('/register', [AuthController::class, 'doRegister']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->post('/language/switch', [LanguageController::class, 'switch']);

// Health checks
$router->get('/health/recurring', [HealthController::class, 'recurring']);

// Admin (desktop)
$router->get('/admin', [AdminDashboardController::class, 'index']);
$router->get('/admin/dashboard', [AdminDashboardController::class, 'index']);
$router->get('/admin/users', [AdminMvpController::class, 'users']);
$router->get('/admin/transactions', [AdminMvpController::class, 'transactions']);
$router->get('/admin/subscriptions', [AdminMvpController::class, 'subscriptions']);
$router->get('/admin/subscriptions/export', [AdminMvpController::class, 'exportSubscriptions']);
$router->get('/admin/operations', [AdminMvpController::class, 'operations']);
$router->get('/admin/journal', [AdminMvpController::class, 'journal']);
$router->get('/admin/ledger', [AdminMvpController::class, 'ledger']);
$router->get('/admin/journal/export', [AdminMvpController::class, 'exportJournal']);
$router->get('/admin/ledger/export', [AdminMvpController::class, 'exportLedger']);
$router->get('/admin/analytics', [AdminMvpController::class, 'analytics']);
$router->get('/admin/support', [AdminMvpController::class, 'support']);
$router->get('/admin/settings', [AdminMvpController::class, 'settings']);
$router->post('/admin/settings/save', [AdminMvpController::class, 'saveSettings']);
$router->post('/admin/users/suspend', [AdminMvpController::class, 'suspendUser']);
$router->post('/admin/users/activate', [AdminMvpController::class, 'activateUser']);
$router->post('/admin/users/reset-password', [AdminMvpController::class, 'resetUserPassword']);
$router->post('/admin/users/reset-api-tokens', [AdminMvpController::class, 'resetUserApiTokens']);

$router->get('/transactions', [TransactionController::class, 'index']);
$router->get('/transactions/add', [TransactionController::class, 'add']);
$router->post('/transactions/store', [TransactionController::class, 'store']);
$router->get('/transactions/edit', [TransactionController::class, 'edit']);
$router->post('/transactions/update', [TransactionController::class, 'update']);
$router->post('/transactions/delete', [TransactionController::class, 'delete']);

$router->get('/categories', [CategoryController::class, 'index']);
$router->post('/categories/store', [CategoryController::class, 'store']);
$router->post('/categories/update', [CategoryController::class, 'update']);
$router->post('/categories/delete', [CategoryController::class, 'delete']);

$router->get('/budget', [BudgetController::class, 'index']);
$router->post('/budget/save', [BudgetController::class, 'save']);
$router->get('/budget/goals', [BudgetController::class, 'goals']);
$router->post('/budget/goals/copy-previous', [BudgetController::class, 'copyPreviousGoals']);
$router->post('/budget/goals/save', [BudgetController::class, 'saveGoal']);
$router->post('/budget/goals/delete', [BudgetController::class, 'deleteGoal']);

$router->get('/reports', [ReportController::class, 'index']);
$router->get('/reports/export', [ReportController::class, 'export']);
$router->get('/reports/charts', [ReportController::class, 'charts']);

$router->get('/journal', [AccountingController::class, 'journal']);
$router->get('/ledger', [AccountingController::class, 'ledger']);
$router->get('/journal/export', [AccountingController::class, 'exportJournalXlsx']);
$router->get('/ledger/export', [AccountingController::class, 'exportLedgerXlsx']);

$router->get('/anomalies', [AnomalyController::class, 'index']);

$router->get('/profile', [ProfileController::class, 'index']);
$router->get('/profile/support-center', [ProfileController::class, 'supportCenter']);
$router->get('/profile/about-app', [ProfileController::class, 'aboutApp']);
$router->post('/profile/update', [ProfileController::class, 'update']);
$router->post('/profile/support-ticket', [ProfileController::class, 'submitSupportTicket']);

$router->get('/notifications', [NotificationController::class, 'index']);
$router->post('/notifications/read', [NotificationController::class, 'markRead']);
$router->post('/notifications/read-all', [NotificationController::class, 'markRead']);

// Salary config
$router->get('/salary-config', [SalaryController::class, 'index']);
$router->post('/salary-config/store', [SalaryController::class, 'store']);
$router->post('/salary-config/update', [SalaryController::class, 'update']);
$router->post('/salary-config/delete', [SalaryController::class, 'delete']);
$router->get('/salary-config/calculate', [SalaryController::class, 'calculate']);

// Income
$router->get('/income', [IncomeController::class, 'index']);
$router->get('/income/add', [IncomeController::class, 'add']);
$router->post('/income/store', [IncomeController::class, 'store']);
$router->get('/income/edit', [IncomeController::class, 'edit']);
$router->post('/income/update', [IncomeController::class, 'update']);
$router->post('/income/delete', [IncomeController::class, 'delete']);
$router->get('/projection', [IncomeController::class, 'projection']);

// Recurring bills
$router->get('/bills', [RecurringBillController::class, 'index']);
$router->get('/bills/add', [RecurringBillController::class, 'add']);
$router->post('/bills/store', [RecurringBillController::class, 'store']);
$router->get('/bills/edit', [RecurringBillController::class, 'edit']);
$router->post('/bills/update', [RecurringBillController::class, 'update']);
$router->post('/bills/delete', [RecurringBillController::class, 'delete']);
$router->post('/bills/generate', [RecurringBillController::class, 'generate']);

// API Auth
$router->post('/api/auth/register', [ApiAuthController::class, 'register']);
$router->post('/api/auth/login', [ApiAuthController::class, 'login']);
$router->post('/api/auth/logout', [ApiAuthController::class, 'logout']);
$router->post('/api/auth/refresh', [ApiAuthController::class, 'refresh']);

// API v1 Auth
$router->post('/api/v1/auth/register', [ApiAuthController::class, 'register']);
$router->post('/api/v1/auth/login', [ApiAuthController::class, 'login']);
$router->post('/api/v1/auth/logout', [ApiAuthController::class, 'logout']);
$router->post('/api/v1/auth/refresh', [ApiAuthController::class, 'refresh']);

// API Transactions CRUD
$router->get('/api/transactions', [ApiTransactionController::class, 'index']);
$router->post('/api/transactions/create', [ApiTransactionController::class, 'create']);
$router->post('/api/transactions/update', [ApiTransactionController::class, 'update']);
$router->post('/api/transactions/delete', [ApiTransactionController::class, 'delete']);
$router->post('/api/transactions/upload-receipt', [ApiTransactionController::class, 'uploadReceipt']);

// API v1 Transactions CRUD
$router->get('/api/v1/transactions', [ApiTransactionController::class, 'index']);
$router->post('/api/v1/transactions/create', [ApiTransactionController::class, 'create']);
$router->post('/api/v1/transactions/update', [ApiTransactionController::class, 'update']);
$router->post('/api/v1/transactions/delete', [ApiTransactionController::class, 'delete']);
$router->post('/api/v1/transactions/upload-receipt', [ApiTransactionController::class, 'uploadReceipt']);

// API Categories CRUD
$router->get('/api/categories', [ApiCategoryController::class, 'index']);
$router->post('/api/categories/create', [ApiCategoryController::class, 'create']);
$router->post('/api/categories/update', [ApiCategoryController::class, 'update']);
$router->post('/api/categories/delete', [ApiCategoryController::class, 'delete']);

// API v1 Categories CRUD
$router->get('/api/v1/categories', [ApiCategoryController::class, 'index']);
$router->post('/api/v1/categories/create', [ApiCategoryController::class, 'create']);
$router->post('/api/v1/categories/update', [ApiCategoryController::class, 'update']);
$router->post('/api/v1/categories/delete', [ApiCategoryController::class, 'delete']);

// API Budget
$router->get('/api/budget', [ApiBudgetController::class, 'get']);
$router->post('/api/budget/save', [ApiBudgetController::class, 'save']);

// API v1 Budget
$router->get('/api/v1/budget', [ApiBudgetController::class, 'get']);
$router->post('/api/v1/budget/save', [ApiBudgetController::class, 'save']);

// API Reports
$router->get('/api/reports/summary', [ApiReportController::class, 'summary']);
$router->get('/api/reports/charts', [ApiReportController::class, 'charts']);
$router->get('/api/reports/export', [ApiReportController::class, 'export']);

// API v1 Reports
$router->get('/api/v1/reports/summary', [ApiReportController::class, 'summary']);
$router->get('/api/v1/reports/charts', [ApiReportController::class, 'charts']);
$router->get('/api/v1/reports/export', [ApiReportController::class, 'export']);

// API Payment Methods
$router->get('/api/payment-methods', [ApiPaymentMethodController::class, 'index']);

// API v1 Payment Methods
$router->get('/api/v1/payment-methods', [ApiPaymentMethodController::class, 'index']);

// API Profile
$router->get('/api/profile/me', [ApiProfileController::class, 'me']);
$router->post('/api/profile/update', [ApiProfileController::class, 'update']);

// API v1 Profile
$router->get('/api/v1/profile/me', [ApiProfileController::class, 'me']);
$router->post('/api/v1/profile/update', [ApiProfileController::class, 'update']);

try {
	$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (\Throwable $e) {
	$writeAppLog('Dispatch error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
	if (!headers_sent()) {
		http_response_code(500);
		header('Content-Type: text/plain; charset=UTF-8');
	}
	echo 'Terjadi error server. Silakan coba lagi. Ref: ' . $requestId;
}
