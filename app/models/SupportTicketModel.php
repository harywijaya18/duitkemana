<?php

class SupportTicketModel extends Model
{
    public function isAvailable(): bool
    {
        return $this->tableExists('support_tickets');
    }

    public function isEnabled(): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        if (!$this->tableExists('admin_settings')) {
            return true;
        }

        $stmt = $this->db->prepare('SELECT value_text FROM admin_settings WHERE key_name = :key_name LIMIT 1');
        $stmt->execute([':key_name' => 'feature_enable_support_center']);
        $value = $stmt->fetchColumn();
        return $value === false ? true : ((string) $value === '1');
    }

    public function createTicket(int $userId, string $category, string $subject, string $priority, string $initialMessage): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO support_tickets (user_id, category, subject, initial_message, status, priority, message_count, last_message_at)
             VALUES (:user_id, :category, :subject, :initial_message, :status, :priority, :message_count, NOW())'
        );

        return $stmt->execute([
            ':user_id' => $userId,
            ':category' => $category,
            ':subject' => $subject,
            ':initial_message' => $initialMessage,
            ':status' => 'open',
            ':priority' => $priority,
            ':message_count' => 1,
        ]);
    }

    public function paginateForUser(int $userId, int $page = 1, int $perPage = 10): array
    {
        if (!$this->isAvailable()) {
            return [
                'items' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => $perPage,
                'total_pages' => 1,
            ];
        }

        $page = max(1, $page);
        $perPage = max(5, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->db->prepare('SELECT COUNT(*) FROM support_tickets WHERE user_id = :user_id');
        $countStmt->execute([':user_id' => $userId]);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            'SELECT id, category, subject, initial_message, status, priority, message_count, first_response_at, last_message_at, created_at
             FROM support_tickets
             WHERE user_id = :user_id
             ORDER BY last_message_at DESC, id DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
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