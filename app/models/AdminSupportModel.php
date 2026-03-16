<?php

class AdminSupportModel extends Model
{
    public function isAvailable(): bool
    {
        return $this->tableExists('support_tickets') && $this->tableExists('announcement_drafts');
    }

    public function snapshot(array $filters, int $page = 1, int $perPage = 20): array
    {
        if (!$this->isAvailable()) {
            return [
                'tickets' => [
                    'items' => [],
                    'total' => 0,
                    'page' => 1,
                    'per_page' => $perPage,
                    'total_pages' => 1,
                ],
                'summary' => [
                    'open' => 0,
                    'in_progress' => 0,
                    'resolved' => 0,
                    'high_priority' => 0,
                ],
                'drafts' => [],
                'feedback' => [],
            ];
        }

        return [
            'tickets' => $this->tickets($filters, $page, $perPage),
            'summary' => $this->summary(),
            'drafts' => $this->drafts(8),
            'feedback' => $this->feedbackByCategory(8),
        ];
    }

    private function tickets(array $filters, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(5, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];

        $status = trim((string) ($filters['status'] ?? ''));
        if (in_array($status, ['open', 'in_progress', 'resolved', 'closed'], true)) {
            $where[] = 't.status = :status';
            $params[':status'] = $status;
        }

        $priority = trim((string) ($filters['priority'] ?? ''));
        if (in_array($priority, ['low', 'normal', 'high', 'urgent'], true)) {
            $where[] = 't.priority = :priority';
            $params[':priority'] = $priority;
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(t.subject LIKE :q OR t.category LIKE :q2 OR u.email LIKE :q3)';
            $params[':q'] = '%' . $q . '%';
            $params[':q2'] = '%' . $q . '%';
            $params[':q3'] = '%' . $q . '%';
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countStmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM support_tickets t
             LEFT JOIN users u ON u.id = t.user_id
             $whereSql"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT t.id, t.user_id, t.category, t.subject, t.status, t.priority,
                       t.last_message_at, t.created_at,
                       u.name AS user_name, u.email AS user_email
                FROM support_tickets t
                LEFT JOIN users u ON u.id = t.user_id
                $whereSql
                ORDER BY t.last_message_at DESC, t.id DESC
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
        ];
    }

    private function summary(): array
    {
        $base = [
            'open' => 0,
            'in_progress' => 0,
            'resolved' => 0,
            'high_priority' => 0,
        ];

        $rows = $this->db->query('SELECT status, COUNT(*) AS cnt FROM support_tickets GROUP BY status')->fetchAll();
        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');
            if (array_key_exists($status, $base)) {
                $base[$status] = (int) ($row['cnt'] ?? 0);
            }
        }

        $base['high_priority'] = $this->intValue("SELECT COUNT(*) FROM support_tickets WHERE priority IN ('high','urgent') AND status IN ('open','in_progress')");

        return $base;
    }

    private function drafts(int $limit): array
    {
        $stmt = $this->db->prepare(
            'SELECT d.id, d.title, d.audience, d.status, d.scheduled_at, d.updated_at,
                    u.email AS author_email
             FROM announcement_drafts d
             LEFT JOIN users u ON u.id = d.created_by
             ORDER BY d.updated_at DESC, d.id DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':lim', max(1, min(50, $limit)), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function feedbackByCategory(int $limit): array
    {
        $stmt = $this->db->prepare(
            'SELECT category, COUNT(*) AS total
             FROM support_tickets
             GROUP BY category
             ORDER BY total DESC, category ASC
             LIMIT :lim'
        );
        $stmt->bindValue(':lim', max(1, min(50, $limit)), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
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

    private function intValue(string $sql): int
    {
        return (int) ($this->db->query($sql)->fetchColumn() ?: 0);
    }
}
