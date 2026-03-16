<?php

require_once dirname(__DIR__) . '/config/app.php';

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
$router->get('/admin/subscriptions', [AdminMvpController::class, 'subscriptions']);
$router->get('/admin/operations', [AdminMvpController::class, 'operations']);
$router->get('/admin/analytics', [AdminMvpController::class, 'analytics']);
$router->get('/admin/support', [AdminMvpController::class, 'support']);
$router->get('/admin/settings', [AdminMvpController::class, 'settings']);

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

$router->get('/reports', [ReportController::class, 'index']);
$router->get('/reports/export', [ReportController::class, 'export']);
$router->get('/reports/charts', [ReportController::class, 'charts']);

$router->get('/profile', [ProfileController::class, 'index']);
$router->post('/profile/update', [ProfileController::class, 'update']);

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

// API Transactions CRUD
$router->get('/api/transactions', [ApiTransactionController::class, 'index']);
$router->post('/api/transactions/create', [ApiTransactionController::class, 'create']);
$router->post('/api/transactions/update', [ApiTransactionController::class, 'update']);
$router->post('/api/transactions/delete', [ApiTransactionController::class, 'delete']);
$router->post('/api/transactions/upload-receipt', [ApiTransactionController::class, 'uploadReceipt']);

// API Categories CRUD
$router->get('/api/categories', [ApiCategoryController::class, 'index']);
$router->post('/api/categories/create', [ApiCategoryController::class, 'create']);
$router->post('/api/categories/update', [ApiCategoryController::class, 'update']);
$router->post('/api/categories/delete', [ApiCategoryController::class, 'delete']);

// API Budget
$router->get('/api/budget', [ApiBudgetController::class, 'get']);
$router->post('/api/budget/save', [ApiBudgetController::class, 'save']);

// API Reports
$router->get('/api/reports/summary', [ApiReportController::class, 'summary']);
$router->get('/api/reports/charts', [ApiReportController::class, 'charts']);
$router->get('/api/reports/export', [ApiReportController::class, 'export']);

// API Payment Methods
$router->get('/api/payment-methods', [ApiPaymentMethodController::class, 'index']);

// API Profile
$router->get('/api/profile/me', [ApiProfileController::class, 'me']);
$router->post('/api/profile/update', [ApiProfileController::class, 'update']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
