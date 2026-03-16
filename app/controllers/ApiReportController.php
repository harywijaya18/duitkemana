<?php

class ApiReportController extends ApiController
{
    private TransactionModel $transactionModel;

    public function __construct()
    {
        $this->transactionModel = $this->model(TransactionModel::class);
    }

    public function summary(): void
    {
        $this->requireApiAuth();
        $user = $this->apiUser();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 20)));
        $cursorRaw = trim((string) ($_GET['cursor'] ?? ''));
        $cursor = ctype_digit($cursorRaw) ? (int) $cursorRaw : null;

        [$startDate, $endDate, $filter] = $this->resolveDateRange();
        $totalTransactions = $this->transactionModel->countReportByRange((int) $user['id'], $startDate, $endDate);
        $paged = $this->transactionModel->paginateReportByRange((int) $user['id'], $startDate, $endDate, $page, $perPage, $cursor);
        $rows = $paged['items'] ?? [];

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

        $insights = [];
        if ($topCategoryName) {
            $topAmount = (float) $categoryTotals[$topCategoryName];
            $topPct = $total > 0 ? ($topAmount / $total) * 100 : 0;
            $insights[] = $topCategoryName . ' adalah kategori pengeluaran tertinggi (' . (int) round($topPct) . '%).';
        }

        $this->success([
            'filter' => $filter,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_spending' => $total,
            'average_daily_spending' => $avgDaily,
            'top_category' => $topCategoryName ? [
                'name' => $topCategoryName,
                'amount' => (float) $categoryTotals[$topCategoryName],
            ] : null,
            'transactions_count' => $totalTransactions,
            'transactions_on_page' => count($rows),
            'pagination' => [
                'mode' => ($cursor !== null && $cursor > 0) ? 'cursor' : 'page',
                'page' => $page,
                'per_page' => $perPage,
                'total' => $totalTransactions,
                'total_pages' => max(1, (int) ceil($totalTransactions / $perPage)),
                'cursor' => ($cursor !== null && $cursor > 0) ? $cursor : null,
                'next_cursor' => $paged['next_cursor'] ?? null,
                'has_more' => (bool) ($paged['has_more'] ?? false),
            ],
            'insights' => $insights,
            'transactions' => $rows,
        ]);
    }

    public function charts(): void
    {
        $this->requireApiAuth();
        $user = $this->apiUser();

        [$startDate, $endDate] = $this->resolveDateRange();

        $byCategory = $this->transactionModel->chartByCategory($user['id'], $startDate, $endDate);
        $byDay = $this->transactionModel->chartByDay($user['id'], $startDate, $endDate);
        $trend = $this->transactionModel->monthlyTrend($user['id'], (int) date('Y', strtotime($endDate)));

        $this->success([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'category' => $byCategory,
            'daily' => $byDay,
            'trend' => $trend,
        ]);
    }

    public function export(): void
    {
        $this->requireApiAuth();
        $user = $this->apiUser();

        [$startDate, $endDate] = $this->resolveDateRange();
        $rows = $this->transactionModel->reportByRange($user['id'], $startDate, $endDate);
        $format = strtolower($_GET['format'] ?? 'csv');

        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="duitkemana_report_api.csv"');
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
            header('Content-Disposition: attachment; filename="duitkemana_report_api.xls"');
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
            $pdf = generate_simple_pdf('DuitKemana API Report', $lines);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="duitkemana_report_api.pdf"');
            echo $pdf;
            exit;
        }

        $this->error('Invalid format. Use csv, excel, or pdf.', 422, [], 'REPORT_INVALID_FORMAT');
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
