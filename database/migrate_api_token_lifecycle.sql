-- ================================================================
-- API token lifecycle (access/refresh + expiry + revoke)
-- ================================================================

USE duitkemana;

ALTER TABLE api_tokens
    ADD COLUMN IF NOT EXISTS token_type ENUM('access','refresh') NOT NULL DEFAULT 'access' AFTER token_hash,
    ADD COLUMN IF NOT EXISTS expires_at DATETIME NULL AFTER token_type,
    ADD COLUMN IF NOT EXISTS revoked_at DATETIME NULL AFTER expires_at,
    ADD COLUMN IF NOT EXISTS parent_token_id BIGINT UNSIGNED NULL AFTER device_name;

ALTER TABLE api_tokens
    ADD INDEX idx_api_tokens_type_expires (token_type, expires_at),
    ADD INDEX idx_api_tokens_user_type (user_id, token_type),
    ADD INDEX idx_api_tokens_revoked (revoked_at);

UPDATE api_tokens
SET token_type = 'access'
WHERE token_type IS NULL OR token_type = '';

UPDATE api_tokens
SET expires_at = DATE_ADD(created_at, INTERVAL 7 DAY)
WHERE expires_at IS NULL;

ALTER TABLE api_tokens
    ADD CONSTRAINT fk_api_tokens_parent
        FOREIGN KEY (parent_token_id) REFERENCES api_tokens(id)
        ON DELETE SET NULL;
