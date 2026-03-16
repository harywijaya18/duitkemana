<?php

class ApiRequestLogModel extends Model
{
    private ?bool $available = null;

    public function logEvent(array $payload): void
    {
        if (!$this->isAvailable()) {
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO api_request_logs
             (user_id, method, path, query_string, status_code, error_code, duration_ms, ip_address, user_agent)
             VALUES
             (:user_id, :method, :path, :query_string, :status_code, :error_code, :duration_ms, :ip_address, :user_agent)'
        );

        $stmt->execute([
            ':user_id' => $payload['user_id'] ?? null,
            ':method' => (string) ($payload['method'] ?? 'GET'),
            ':path' => (string) ($payload['path'] ?? ''),
            ':query_string' => (string) ($payload['query_string'] ?? ''),
            ':status_code' => (int) ($payload['status_code'] ?? 200),
            ':error_code' => (string) ($payload['error_code'] ?? ''),
            ':duration_ms' => (int) ($payload['duration_ms'] ?? 0),
            ':ip_address' => (string) ($payload['ip_address'] ?? ''),
            ':user_agent' => (string) ($payload['user_agent'] ?? ''),
        ]);
    }

    private function isAvailable(): bool
    {
        if ($this->available !== null) {
            return $this->available;
        }

        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name = :table'
        );
        $stmt->execute([':table' => 'api_request_logs']);
        $this->available = ((int) $stmt->fetchColumn()) > 0;

        return $this->available;
    }
}
