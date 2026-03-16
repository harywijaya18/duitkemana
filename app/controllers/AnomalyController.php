<?php

class AnomalyController extends Controller
{
    private AnomalyModel $anomalyModel;

    public function __construct()
    {
        $this->anomalyModel = $this->model(AnomalyModel::class);
    }

    public function index(): void
    {
        require_auth();
        $user  = auth_user();
        $month = (int) ($_GET['month'] ?? date('n'));
        $year  = (int) ($_GET['year']  ?? date('Y'));

        $anomalies = $this->anomalyModel->detectAnomalies($user['id'], $month, $year);
        $stats     = $this->anomalyModel->getMonthlyStats($user['id'], $month, $year);

        $this->view('anomalies', [
            'anomalies' => $anomalies,
            'stats'     => $stats,
            'month'     => $month,
            'year'      => $year,
        ]);
    }
}
