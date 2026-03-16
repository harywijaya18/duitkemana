<?php

class AdminDashboardController extends Controller
{
    private AdminMetricsModel $metricsModel;

    public function __construct()
    {
        $this->metricsModel = $this->model(AdminMetricsModel::class);
    }

    public function index(): void
    {
        require_admin();

        $user = auth_user();
        $months = max(3, min(12, (int) ($_GET['months'] ?? 6)));
        $topPage = max(1, (int) ($_GET['top_page'] ?? 1));
        $recentPage = max(1, (int) ($_GET['recent_page'] ?? 1));
        $perPage = max(5, min(50, (int) ($_GET['per_page'] ?? 10)));

        $snapshot = $this->metricsModel->dashboardSnapshot($months, $topPage, $recentPage, $perPage);

        $viewFile = APP_PATH . '/views/admin/dashboard.php';
        if (!file_exists($viewFile)) {
            http_response_code(404);
            echo 'View not found';
            return;
        }

        require APP_PATH . '/views/layouts/admin_header.php';
        require $viewFile;
        require APP_PATH . '/views/layouts/admin_footer.php';
    }
}
