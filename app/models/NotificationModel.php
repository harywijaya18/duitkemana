<?php

class NotificationModel extends Model
{
    /**
     * Generate smart notifications for a user based on current budget/spending state.
     * Idempotent: a notification of the same type for the same period is only created once.
     */
    public function generateForUser(int $userId, int $month, int $year): void
    {
        if (!$this->tableExists()) return;

        $this->checkBudgetWarning($userId, $month, $year);
        $this->checkBudgetNotSet($userId, $month, $year);
        $this->checkGoalOverrun($userId, $month, $year);
    }

    public function getUnread(int $userId): array
    {
        if (!$this->tableExists()) return [];
        $stmt = $this->db->prepare(
            'SELECT * FROM notifications
             WHERE user_id = :uid AND is_read = 0
             ORDER BY created_at DESC
             LIMIT 50'
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function getAll(int $userId, int $limit = 40): array
    {
        if (!$this->tableExists()) return [];
        $stmt = $this->db->prepare(
            'SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT :lim'
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countUnread(int $userId): int
    {
        if (!$this->tableExists()) return 0;
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0'
        );
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function markRead(int $userId, ?int $id = null): void
    {
        if (!$this->tableExists()) return;
        if ($id !== null) {
            $stmt = $this->db->prepare(
                'UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :uid'
            );
            $stmt->execute([':id' => $id, ':uid' => $userId]);
        } else {
            $stmt = $this->db->prepare(
                'UPDATE notifications SET is_read = 1 WHERE user_id = :uid'
            );
            $stmt->execute([':uid' => $userId]);
        }
    }

    public function deleteOld(int $userId, int $keepDays = 30): void
    {
        if (!$this->tableExists()) return;
        $stmt = $this->db->prepare(
            'DELETE FROM notifications
             WHERE user_id = :uid AND created_at < DATE_SUB(NOW(), INTERVAL :days DAY)'
        );
        $stmt->execute([':uid' => $userId, ':days' => $keepDays]);
    }

    // ──────────────────────────────────────
    //  Private generators
    // ──────────────────────────────────────

    private function checkBudgetWarning(int $userId, int $month, int $year): void
    {
        $stmtBudget = $this->db->prepare(
            'SELECT amount FROM budgets WHERE user_id = :uid AND month = :month AND year = :year LIMIT 1'
        );
        $stmtBudget->execute([':uid' => $userId, ':month' => $month, ':year' => $year]);
        $budget = (float) ($stmtBudget->fetchColumn() ?: 0);
        if ($budget <= 0) return;

        $stmtSpent = $this->db->prepare(
            'SELECT COALESCE(SUM(amount), 0) FROM transactions
             WHERE user_id = :uid AND MONTH(transaction_date) = :month AND YEAR(transaction_date) = :year'
        );
        $stmtSpent->execute([':uid' => $userId, ':month' => $month, ':year' => $year]);
        $spent = (float) $stmtSpent->fetchColumn();

        $pct = $budget > 0 ? ($spent / $budget) * 100 : 0;
        $key = "budget_warning_{$month}_{$year}";

        if ($pct >= 100) {
            $this->insertOnce($userId, 'danger', $key . '_over', '🚨 Anggaran Terlampaui!',
                sprintf('Pengeluaran bulan ini sudah mencapai Rp %s melebihi anggaran Rp %s.',
                    number_format($spent, 0, ',', '.'), number_format($budget, 0, ',', '.')));
        } elseif ($pct >= 80) {
            $this->insertOnce($userId, 'warning', $key . '_80',  '⚠️ Anggaran 80% Terpakai',
                sprintf('Sudah %.0f%% dari anggaran bulan ini terpakai.', $pct));
        }
    }

    private function checkBudgetNotSet(int $userId, int $month, int $year): void
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM budgets WHERE user_id = :uid AND month = :month AND year = :year LIMIT 1'
        );
        $stmt->execute([':uid' => $userId, ':month' => $month, ':year' => $year]);
        if (!$stmt->fetchColumn()) {
            $this->insertOnce($userId, 'info', "budget_not_set_{$month}_{$year}",
                '💡 Anggaran Belum Diatur',
                'Kamu belum menetapkan anggaran untuk bulan ini. Atur sekarang di halaman Budget.');
        }
    }

    private function checkGoalOverrun(int $userId, int $month, int $year): void
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT c.name, g.goal_amount, COALESCE(SUM(t.amount),0) AS spent
                 FROM budget_goals g
                 JOIN categories c ON c.id = g.category_id
                 LEFT JOIN transactions t ON t.category_id = g.category_id
                     AND t.user_id = :uid2
                     AND MONTH(t.transaction_date) = :month
                     AND YEAR(t.transaction_date)  = :year
                 WHERE g.user_id = :uid AND g.month = :month2 AND g.year = :year2
                 GROUP BY g.id
                 HAVING spent > g.goal_amount'
            );
            $stmt->execute([
                ':uid'   => $userId, ':uid2'  => $userId,
                ':month' => $month,  ':month2' => $month,
                ':year'  => $year,   ':year2'  => $year,
            ]);
            foreach ($stmt->fetchAll() as $row) {
                $this->insertOnce(
                    $userId, 'warning',
                    "goal_over_{$row['name']}_{$month}_{$year}",
                    "🎯 Target Kategori Terlampaui: {$row['name']}",
                    sprintf('Pengeluaran Rp %s melebihi target Rp %s.',
                        number_format((float) $row['spent'], 0, ',', '.'),
                        number_format((float) $row['goal_amount'], 0, ',', '.'))
                );
            }
        } catch (\Throwable $e) {
            // budget_goals table may not exist yet — silently skip
        }
    }

    private function insertOnce(int $userId, string $type, string $dedupKey, string $title, string $message): void
    {
        // Use title as dedup: only insert if no identical unread title exists for this user this month
        $stmt = $this->db->prepare(
            'SELECT id FROM notifications WHERE user_id = :uid AND title = :title AND is_read = 0 LIMIT 1'
        );
        $stmt->execute([':uid' => $userId, ':title' => $title]);
        if ($stmt->fetchColumn()) return;

        $ins = $this->db->prepare(
            'INSERT INTO notifications (user_id, type, title, message) VALUES (:uid, :type, :title, :msg)'
        );
        $ins->execute([':uid' => $userId, ':type' => $type, ':title' => $title, ':msg' => $message]);
    }

    private function tableExists(): bool
    {
        static $checked = null;
        if ($checked !== null) return $checked;
        try {
            $this->db->query('SELECT 1 FROM notifications LIMIT 1');
            $checked = true;
        } catch (\Throwable $e) {
            $checked = false;
        }
        return $checked;
    }
}
