<?php

class AdminSettingsModel extends Model
{
    private array $defaults = [
        'feature_enable_api_v1' => '0',
        'feature_enable_support_center' => '1',
        'feature_enable_recurring_auto' => '1',
        'security_admin_session_timeout_min' => '30',
        'security_max_failed_login' => '5',
        'security_password_reset_ttl_min' => '30',
    ];

    public function isAvailable(): bool
    {
        return $this->tableExists('admin_settings');
    }

    public function snapshot(): array
    {
        $settings = $this->defaults;

        if ($this->isAvailable()) {
            $rows = $this->db->query('SELECT key_name, value_text FROM admin_settings')->fetchAll();
            foreach ($rows as $row) {
                $key = (string) ($row['key_name'] ?? '');
                if ($key !== '' && array_key_exists($key, $settings)) {
                    $settings[$key] = (string) ($row['value_text'] ?? '');
                }
            }
        }

        return [
            'available' => $this->isAvailable(),
            'settings' => $settings,
        ];
    }

    public function saveMany(array $values, int $adminUserId): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO admin_settings (key_name, value_text, updated_by)
             VALUES (:key_name, :value_text, :updated_by)
             ON DUPLICATE KEY UPDATE
                value_text = VALUES(value_text),
                updated_by = VALUES(updated_by),
                updated_at = CURRENT_TIMESTAMP'
        );

        foreach ($this->defaults as $key => $_default) {
            if (!array_key_exists($key, $values)) {
                continue;
            }
            $ok = $stmt->execute([
                ':key_name' => $key,
                ':value_text' => (string) $values[$key],
                ':updated_by' => $adminUserId > 0 ? $adminUserId : null,
            ]);
            if (!$ok) {
                return false;
            }
        }

        return true;
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
