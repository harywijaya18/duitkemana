<?php

class AdminBillingModel extends Model
{
    public function isAvailable(): bool
    {
        return $this->tableExists('subscriptions') && $this->tableExists('plans');
    }

    public function listSubscriptions(array $filters, int $page = 1, int $perPage = 20): array
    {
        if (!$this->isAvailable()) {
            return [
                'items' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => $perPage,
                'total_pages' => 1,
                'summary' => [
                    'active' => 0,
                    'trial' => 0,
                    'past_due' => 0,
                    'failed_invoices' => 0,
                ],
                'plans' => [],
            ];
        }

        $page = max(1, $page);
        $perPage = max(5, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $hasInvoices = $this->tableExists('invoices');

        $where = [];
        $params = [];

        $status = trim((string) ($filters['status'] ?? ''));
        if (in_array($status, ['trial', 'active', 'grace', 'past_due', 'cancelled'], true)) {
            $where[] = 's.status = :status';
            $params[':status'] = $status;
        }

        $planId = (int) ($filters['plan_id'] ?? 0);
        if ($planId > 0) {
            $where[] = 's.plan_id = :plan_id';
            $params[':plan_id'] = $planId;
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(u.name LIKE :q OR u.email LIKE :q OR p.name LIKE :q2)';
            $params[':q'] = '%' . $q . '%';
            $params[':q2'] = '%' . $q . '%';
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countStmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM subscriptions s
             JOIN users u ON u.id = s.user_id
             LEFT JOIN plans p ON p.id = s.plan_id
             $whereSql"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $invoiceSelect = $hasInvoices
            ? 'inv.last_invoice_status, inv.last_invoice_due'
            : "NULL AS last_invoice_status, NULL AS last_invoice_due";

        $invoiceJoin = $hasInvoices
            ? "LEFT JOIN (
                    SELECT i.subscription_id,
                           SUBSTRING_INDEX(GROUP_CONCAT(i.status ORDER BY i.created_at DESC), ',', 1) AS last_invoice_status,
                           MAX(i.due_date) AS last_invoice_due
                    FROM invoices i
                    GROUP BY i.subscription_id
                ) inv ON inv.subscription_id = s.id"
            : '';

        $sql = "SELECT s.id, s.user_id, s.plan_id, s.status,
                       s.current_period_start, s.current_period_end,
                       s.trial_ends_at, s.cancelled_at,
                       s.updated_at,
                       u.name AS user_name, u.email AS user_email,
                       p.name AS plan_name, p.price_monthly, p.currency,
                       $invoiceSelect
                FROM subscriptions s
                JOIN users u ON u.id = s.user_id
                LEFT JOIN plans p ON p.id = s.plan_id
                $invoiceJoin
                $whereSql
                ORDER BY s.updated_at DESC, s.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => max(1, (int) ceil($total / $perPage)),
            'summary' => $this->summary(),
            'plans' => $this->plans(),
        ];
    }

    public function exportSubscriptionsCsv(array $filters): array
    {
        $data = $this->listSubscriptions($filters, 1, 1000);
        return $data['items'] ?? [];
    }

    private function plans(): array
    {
        if (!$this->tableExists('plans')) {
            return [];
        }

        return $this->db->query('SELECT id, name, code, price_monthly, currency, is_active FROM plans ORDER BY price_monthly ASC')->fetchAll();
    }

    private function summary(): array
    {
        $base = [
            'active' => 0,
            'trial' => 0,
            'past_due' => 0,
            'failed_invoices' => 0,
        ];

        if (!$this->tableExists('subscriptions')) {
            return $base;
        }

        $rows = $this->db->query('SELECT status, COUNT(*) AS cnt FROM subscriptions GROUP BY status')->fetchAll();
        foreach ($rows as $r) {
            $status = (string) ($r['status'] ?? '');
            if (array_key_exists($status, $base)) {
                $base[$status] = (int) ($r['cnt'] ?? 0);
            }
        }

        if ($this->tableExists('invoices')) {
            $base['failed_invoices'] = (int) ($this->db->query("SELECT COUNT(*) FROM invoices WHERE status = 'failed'")->fetchColumn() ?: 0);
        }

        return $base;
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name = :table'
        );
        $stmt->execute([':table' => $table]);
        return ((int) $stmt->fetchColumn()) > 0;
    }
}
