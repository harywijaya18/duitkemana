<?php

class AccountingModel extends Model
{
    public function getJournalLines(int $userId, string $startDate, string $endDate): array
    {
        $lines = [];

        // Expense transactions -> Dr Expense, Cr Cash/Bank by payment method
        $txStmt = $this->db->prepare(
            'SELECT t.id, t.transaction_date, t.amount, t.description,
                    c.id AS category_id, c.name AS category_name,
                    p.id AS payment_method_id, p.name AS payment_method_name
             FROM transactions t
             JOIN categories c ON c.id = t.category_id
             JOIN payment_methods p ON p.id = t.payment_method_id
             WHERE t.user_id = :uid
               AND t.transaction_date BETWEEN :start_date AND :end_date
             ORDER BY t.transaction_date ASC, t.id ASC'
        );
        $txStmt->execute([
            ':uid' => $userId,
            ':start_date' => $startDate,
            ':end_date' => $endDate,
        ]);

        foreach ($txStmt->fetchAll() as $row) {
            $amount = (float) $row['amount'];
            $entryDate = (string) $row['transaction_date'];
            $journalNo = 'TX-' . (int) $row['id'];
            $desc = trim((string) ($row['description'] ?? ''));
            if ($desc === '') {
                $desc = t('Expense') . ': ' . $row['category_name'];
            }

            $lines[] = [
                'entry_date' => $entryDate,
                'journal_no' => $journalNo,
                'source_type' => 'transaction',
                'source_id' => (int) $row['id'],
                'description' => $desc,
                'account_key' => 'EXP:CAT:' . (int) $row['category_id'],
                'account_name' => t('Expense') . ' - ' . $row['category_name'],
                'debit' => $amount,
                'credit' => 0.0,
            ];

            $lines[] = [
                'entry_date' => $entryDate,
                'journal_no' => $journalNo,
                'source_type' => 'transaction',
                'source_id' => (int) $row['id'],
                'description' => $desc,
                'account_key' => 'AST:PM:' . (int) $row['payment_method_id'],
                'account_name' => t('Cash/Bank') . ' - ' . $row['payment_method_name'],
                'debit' => 0.0,
                'credit' => $amount,
            ];
        }

        // Income records -> Dr Cash/Bank, Cr Income source
        if ($this->incomeTableExists()) {
            $incStmt = $this->db->prepare(
                'SELECT id, source_name, total_income, received_date
                 FROM income_records
                 WHERE user_id = :uid
                   AND received_date BETWEEN :start_date AND :end_date
                 ORDER BY received_date ASC, id ASC'
            );
            $incStmt->execute([
                ':uid' => $userId,
                ':start_date' => $startDate,
                ':end_date' => $endDate,
            ]);

            foreach ($incStmt->fetchAll() as $row) {
                $amount = (float) $row['total_income'];
                $entryDate = (string) $row['received_date'];
                $journalNo = 'INC-' . (int) $row['id'];
                $sourceName = trim((string) ($row['source_name'] ?? '')) ?: t('Income');
                $desc = t('Income') . ': ' . $sourceName;

                $lines[] = [
                    'entry_date' => $entryDate,
                    'journal_no' => $journalNo,
                    'source_type' => 'income',
                    'source_id' => (int) $row['id'],
                    'description' => $desc,
                    'account_key' => 'AST:CASHBANK',
                    'account_name' => t('Cash/Bank'),
                    'debit' => $amount,
                    'credit' => 0.0,
                ];

                $lines[] = [
                    'entry_date' => $entryDate,
                    'journal_no' => $journalNo,
                    'source_type' => 'income',
                    'source_id' => (int) $row['id'],
                    'description' => $desc,
                    'account_key' => 'INC:SRC:' . md5($sourceName),
                    'account_name' => t('Income') . ' - ' . $sourceName,
                    'debit' => 0.0,
                    'credit' => $amount,
                ];
            }
        }

        usort($lines, static function (array $a, array $b): int {
            $cmpDate = strcmp($a['entry_date'], $b['entry_date']);
            if ($cmpDate !== 0) {
                return $cmpDate;
            }
            $cmpNo = strcmp($a['journal_no'], $b['journal_no']);
            if ($cmpNo !== 0) {
                return $cmpNo;
            }
            // Debit line first for readability
            return ($b['debit'] <=> $a['debit']);
        });

        return $lines;
    }

    public function getLedgerSummary(int $userId, string $startDate, string $endDate): array
    {
        $lines = $this->getJournalLines($userId, $startDate, $endDate);
        $accounts = [];

        foreach ($lines as $line) {
            $key = (string) $line['account_key'];
            if (!isset($accounts[$key])) {
                $accounts[$key] = [
                    'account_key' => $key,
                    'account_name' => (string) $line['account_name'],
                    'debit_total' => 0.0,
                    'credit_total' => 0.0,
                    'entries_count' => 0,
                ];
            }

            $accounts[$key]['debit_total'] += (float) $line['debit'];
            $accounts[$key]['credit_total'] += (float) $line['credit'];
            $accounts[$key]['entries_count']++;
        }

        foreach ($accounts as &$acc) {
            $acc['balance'] = (float) $acc['debit_total'] - (float) $acc['credit_total'];
        }
        unset($acc);

        $result = array_values($accounts);
        usort($result, static function (array $a, array $b): int {
            return strcmp($a['account_name'], $b['account_name']);
        });

        return $result;
    }

    public function getLedgerDetail(int $userId, string $accountKey, string $startDate, string $endDate): array
    {
        $lines = $this->getJournalLines($userId, $startDate, $endDate);
        $detail = array_values(array_filter($lines, static function (array $row) use ($accountKey): bool {
            return $row['account_key'] === $accountKey;
        }));

        $openingBalance = $this->getBalanceUntil($userId, $accountKey, $startDate);
        $running = $openingBalance;
        foreach ($detail as &$row) {
            $running += (float) $row['debit'] - (float) $row['credit'];
            $row['running_balance'] = $running;
        }
        unset($row);

        return [
            'opening_balance' => $openingBalance,
            'rows' => $detail,
        ];
    }

    private function getBalanceUntil(int $userId, string $accountKey, string $startDate): float
    {
        $dayBefore = date('Y-m-d', strtotime($startDate . ' -1 day'));
        $allBefore = $this->getJournalLines($userId, '2000-01-01', $dayBefore);

        $balance = 0.0;
        foreach ($allBefore as $line) {
            if ($line['account_key'] !== $accountKey) {
                continue;
            }
            $balance += (float) $line['debit'] - (float) $line['credit'];
        }

        return $balance;
    }

    private function incomeTableExists(): bool
    {
        static $checked = null;
        if ($checked !== null) {
            return $checked;
        }

        try {
            $this->db->query('SELECT 1 FROM income_records LIMIT 1');
            $checked = true;
        } catch (\Throwable $e) {
            $checked = false;
        }

        return $checked;
    }
}
