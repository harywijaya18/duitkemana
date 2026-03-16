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
        $snapshot = $this->metricsModel->dashboardSnapshot(6);

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
