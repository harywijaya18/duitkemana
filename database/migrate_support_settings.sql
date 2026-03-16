-- ================================================================
-- Support Center + Admin Settings migration
-- ================================================================

USE duitkemana;

CREATE TABLE IF NOT EXISTS support_tickets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NULL,
    category VARCHAR(60) NOT NULL DEFAULT 'general',
    subject VARCHAR(180) NOT NULL,
    status ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
    priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    message_count INT UNSIGNED NOT NULL DEFAULT 1,
    first_response_at DATETIME NULL,
    last_message_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_support_tickets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_support_tickets_status_priority (status, priority),
    INDEX idx_support_tickets_last_message (last_message_at),
    INDEX idx_support_tickets_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS announcement_drafts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(180) NOT NULL,
    body MEDIUMTEXT NULL,
    audience VARCHAR(60) NOT NULL DEFAULT 'all_users',
    status ENUM('draft','scheduled','published','archived') NOT NULL DEFAULT 'draft',
    scheduled_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_announcement_drafts_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_announcement_status_sched (status, scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    key_name VARCHAR(120) NOT NULL,
    value_text TEXT NULL,
    updated_by INT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_admin_settings_key (key_name),
    CONSTRAINT fk_admin_settings_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO admin_settings (key_name, value_text) VALUES
('feature_enable_api_v1', '0'),
('feature_enable_support_center', '1'),
('feature_enable_recurring_auto', '1'),
('security_admin_session_timeout_min', '30'),
('security_max_failed_login', '5'),
('security_password_reset_ttl_min', '30')
ON DUPLICATE KEY UPDATE
    value_text = VALUES(value_text);

INSERT INTO support_tickets (user_id, category, subject, status, priority, message_count, last_message_at)
SELECT u.id, 'billing', 'Tagihan bulan ini belum terbaca', 'open', 'high', 2, NOW()
FROM users u
WHERE u.email = 'demo@duitkemana.com'
  AND NOT EXISTS (
      SELECT 1
      FROM support_tickets t
      WHERE t.subject = 'Tagihan bulan ini belum terbaca'
  )
LIMIT 1;

INSERT INTO support_tickets (user_id, category, subject, status, priority, message_count, first_response_at, last_message_at)
SELECT u.id, 'feature_request', 'Mohon fitur kategori custom icon', 'in_progress', 'normal', 3, NOW(), NOW()
FROM users u
WHERE u.email = 'demo@duitkemana.com'
  AND NOT EXISTS (
      SELECT 1
      FROM support_tickets t
      WHERE t.subject = 'Mohon fitur kategori custom icon'
  )
LIMIT 1;

INSERT INTO announcement_drafts (title, body, audience, status, scheduled_at, created_by)
SELECT 'Perubahan jadwal maintenance mingguan',
       'Kami akan melakukan maintenance rutin pada Sabtu pukul 23:00 WIB.',
       'all_users',
       'draft',
       DATE_ADD(NOW(), INTERVAL 2 DAY),
       u.id
FROM users u
WHERE u.email = 'admin@duitkemana.com'
  AND NOT EXISTS (
      SELECT 1
      FROM announcement_drafts d
      WHERE d.title = 'Perubahan jadwal maintenance mingguan'
  )
LIMIT 1;
