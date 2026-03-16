-- ================================================================
-- API request and error logging migration
-- ================================================================

USE duitkemana;

CREATE TABLE IF NOT EXISTS api_request_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NULL,
    method VARCHAR(10) NOT NULL,
    path VARCHAR(255) NOT NULL,
    query_string VARCHAR(500) NULL,
    status_code SMALLINT UNSIGNED NOT NULL,
    error_code VARCHAR(80) NULL,
    duration_ms INT UNSIGNED NOT NULL DEFAULT 0,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_api_request_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_api_request_logs_created (created_at),
    INDEX idx_api_request_logs_status_created (status_code, created_at),
    INDEX idx_api_request_logs_path_created (path, created_at),
    INDEX idx_api_request_logs_error_created (error_code, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
