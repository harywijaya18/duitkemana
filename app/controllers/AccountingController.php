<?php

class AccountingController extends Controller
{
    private AccountingModel $accountingModel;

    public function __construct()
    {
        $this->accountingModel = $this->model(AccountingModel::class);
    }

    public function journal(): void
    {
        require_auth();
        $user = auth_user();
        [$startDate, $endDate] = $this->resolveDateRange();

        $lines = $this->accountingModel->getJournalLines((int) $user['id'], $startDate, $endDate);

        $totalDebit = array_sum(array_map(static fn($x) => (float) $x['debit'], $lines));
        $totalCredit = array_sum(array_map(static fn($x) => (float) $x['credit'], $lines));

        $this->view('journal', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'lines' => $lines,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
        ]);
    }

    public function ledger(): void
    {
        require_auth();
        $user = auth_user();
        [$startDate, $endDate] = $this->resolveDateRange();

        $summary = $this->accountingModel->getLedgerSummary((int) $user['id'], $startDate, $endDate);

        $selectedAccount = (string) ($_GET['account'] ?? '');
        $detail = null;
        if ($selectedAccount !== '') {
            $detail = $this->accountingModel->getLedgerDetail((int) $user['id'], $selectedAccount, $startDate, $endDate);
        }

        $this->view('ledger', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'summary' => $summary,
            'selectedAccount' => $selectedAccount,
            'detail' => $detail,
        ]);
    }

    public function exportJournalXlsx(): void
    {
        require_auth();
        $user = auth_user();
        [$startDate, $endDate] = $this->resolveDateRange();

        if (!class_exists('XLSXWriter')) {
            flash('error', 'Library Excel belum tersedia. Jalankan: composer install');
            redirect('/journal?start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate));
        }

        $lines = $this->accountingModel->getJournalLines((int) $user['id'], $startDate, $endDate);

        $writer = new XLSXWriter();
        $writer->setAuthor('DuitKemana');
        $writer->writeSheetHeader('Journal', [
            'entry_date' => 'string',
            'journal_no' => 'string',
            'description' => 'string',
            'account' => 'string',
            'debit' => 'price',
            'credit' => 'price',
        ]);

        foreach ($lines as $row) {
            $writer->writeSheetRow('Journal', [
                (string) ($row['entry_date'] ?? ''),
                (string) ($row['journal_no'] ?? ''),
                (string) ($row['description'] ?? ''),
                (string) ($row['account_name'] ?? ''),
                (float) ($row['debit'] ?? 0),
                (float) ($row['credit'] ?? 0),
            ]);
        }

        $filename = 'journal-' . $startDate . '-to-' . $endDate . '.xlsx';
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Pragma: no-cache');
        header('Expires: 0');
        $writer->writeToStdOut();
        exit;
    }

    public function exportLedgerXlsx(): void
    {
        require_auth();
        $user = auth_user();
        [$startDate, $endDate] = $this->resolveDateRange();
        $selectedAccount = (string) ($_GET['account'] ?? '');

        if (!class_exists('XLSXWriter')) {
            flash('error', 'Library Excel belum tersedia. Jalankan: composer install');
            redirect('/ledger?start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate));
        }

        $summary = $this->accountingModel->getLedgerSummary((int) $user['id'], $startDate, $endDate);

        $writer = new XLSXWriter();
        $writer->setAuthor('DuitKemana');
        $writer->writeSheetHeader('Ledger Summary', [
            'account' => 'string',
            'debit_total' => 'price',
            'credit_total' => 'price',
            'balance' => 'price',
            'entries_count' => 'integer',
        ]);

        foreach ($summary as $row) {
            $writer->writeSheetRow('Ledger Summary', [
                (string) ($row['account_name'] ?? ''),
                (float) ($row['debit_total'] ?? 0),
                (float) ($row['credit_total'] ?? 0),
                (float) ($row['balance'] ?? 0),
                (int) ($row['entries_count'] ?? 0),
            ]);
        }

        if ($selectedAccount !== '') {
            $detail = $this->accountingModel->getLedgerDetail((int) $user['id'], $selectedAccount, $startDate, $endDate);
            $writer->writeSheetHeader('Ledger Detail', [
                'entry_date' => 'string',
                'journal_no' => 'string',
                'description' => 'string',
                'debit' => 'price',
                'credit' => 'price',
                'running_balance' => 'price',
            ]);

            $writer->writeSheetRow('Ledger Detail', [
                'Opening Balance',
                '',
                '',
                0,
                0,
                (float) ($detail['opening_balance'] ?? 0),
            ]);

            foreach (($detail['rows'] ?? []) as $row) {
                $writer->writeSheetRow('Ledger Detail', [
                    (string) ($row['entry_date'] ?? ''),
                    (string) ($row['journal_no'] ?? ''),
                    (string) ($row['description'] ?? ''),
                    (float) ($row['debit'] ?? 0),
                    (float) ($row['credit'] ?? 0),
                    (float) ($row['running_balance'] ?? 0),
                ]);
            }
        }

        $filename = 'ledger-' . $startDate . '-to-' . $endDate . '.xlsx';
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Pragma: no-cache');
        header('Expires: 0');
        $writer->writeToStdOut();
        exit;
    }

    private function resolveDateRange(): array
    {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');

        if (strtotime($startDate) > strtotime($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$startDate, $endDate];
    }
}
