<?php

class ReportController extends Controller
{
    private TransactionModel $transactionModel;

    public function __construct()
    {
        $this->transactionModel = $this->model(TransactionModel::class);
    }

    public function index(): void
    {
        require_auth();
        $user = auth_user();

        [$startDate, $endDate, $filter] = $this->resolveDateRange();

        $rows = $this->transactionModel->reportByRange($user['id'], $startDate, $endDate);
        $total = array_sum(array_map(static fn($r) => (float) $r['amount'], $rows));
        $dayCount = max((int) ((strtotime($endDate) - strtotime($startDate)) / 86400) + 1, 1);
        $avgDaily = $total / $dayCount;
        $categoryTotals = [];
        foreach ($rows as $row) {
            $name = (string) $row['category_name'];
            $categoryTotals[$name] = ($categoryTotals[$name] ?? 0) + (float) $row['amount'];
        }
        arsort($categoryTotals);
        $topCategoryName = array_key_first($categoryTotals);
        $topCategory = $topCategoryName ? ['name' => $topCategoryName, 'total' => $categoryTotals[$topCategoryName]] : null;

        $this->view('reports', [
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filter' => $filter,
            'total' => $total,
            'avgDaily' => $avgDaily,
            'topCategory' => $topCategory,
        ]);
    }

    public function charts(): void
    {
        require_auth();
        $user = auth_user();
        [$startDate, $endDate] = $this->resolveDateRange();

        $byCategory = $this->transactionModel->chartByCategory($user['id'], $startDate, $endDate);
        $byDay = $this->transactionModel->chartByDay($user['id'], $startDate, $endDate);
        $trend = $this->transactionModel->monthlyTrend($user['id'], (int) date('Y'));

        $this->json([
            'category' => $byCategory,
            'daily' => $byDay,
            'trend' => $trend,
        ]);
    }

    public function export(): void
    {
        require_auth();
        $user = auth_user();

        [$startDate, $endDate] = $this->resolveDateRange();
        $rows = $this->transactionModel->reportByRange($user['id'], $startDate, $endDate);
        $format = strtolower($_GET['format'] ?? 'csv');

        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="duitkemana_report.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Category', 'Payment Method', 'Amount', 'Description']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['transaction_date'],
                    $row['category_name'],
                    $row['payment_method_name'],
                    $row['amount'],
                    $row['description'],
                ]);
            }
            fclose($out);
            exit;
        }

        if ($format === 'excel') {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="duitkemana_report.xls"');
            echo "Date\tCategory\tPayment Method\tAmount\tDescription\n";
            foreach ($rows as $row) {
                echo implode("\t", [
                    $row['transaction_date'],
                    $row['category_name'],
                    $row['payment_method_name'],
                    $row['amount'],
                    str_replace(["\r", "\n", "\t"], ' ', (string) $row['description']),
                ]) . "\n";
            }
            exit;
        }

        if ($format === 'pdf') {
            $lines = [];
            foreach ($rows as $row) {
                $lines[] = $row['transaction_date'] . ' | ' . $row['category_name'] . ' | ' . $row['amount'];
            }
            $pdf = generate_simple_pdf('DuitKemana Report', $lines);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="duitkemana_report.pdf"');
            echo $pdf;
            exit;
        }

        redirect('/reports');
    }

    private function resolveDateRange(): array
    {
        $filter = $_GET['filter'] ?? 'monthly';
        $today = date('Y-m-d');

        switch ($filter) {
            case 'daily':
                $startDate = $today;
                $endDate = $today;
                break;
            case 'weekly':
                $startDate = date('Y-m-d', strtotime('-6 days'));
                $endDate = $today;
                break;
            case 'custom':
                $startDate = $_GET['start_date'] ?? date('Y-m-01');
                $endDate = $_GET['end_date'] ?? $today;
                break;
            case 'monthly':
            default:
                $startDate = date('Y-m-01');
                $endDate = date('Y-m-t');
                $filter = 'monthly';
                break;
        }

        if (strtotime($startDate) > strtotime($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$startDate, $endDate, $filter];
    }
}
