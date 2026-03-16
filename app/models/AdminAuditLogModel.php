<?php

class AdminAuditLogModel extends Model
{
    public function log(int $adminUserId, string $action, ?int $targetUserId = null, array $details = []): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO admin_audit_logs (admin_user_id, target_user_id, action, details, ip_address, user_agent)
             VALUES (:admin_user_id, :target_user_id, :action, :details, :ip_address, :user_agent)'
        );

        return $stmt->execute([
            ':admin_user_id' => $adminUserId,
            ':target_user_id' => $targetUserId,
            ':action' => $action,
            ':details' => empty($details) ? null : json_encode($details, JSON_UNESCAPED_UNICODE),
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);
    }

    public function recent(int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));

        $stmt = $this->db->prepare(
            'SELECT aal.id, aal.action, aal.details, aal.created_at,
                    admin.email AS admin_email,
                    target.email AS target_email
             FROM admin_audit_logs aal
             JOIN users admin ON admin.id = aal.admin_user_id
             LEFT JOIN users target ON target.id = aal.target_user_id
             ORDER BY aal.created_at DESC, aal.id DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
