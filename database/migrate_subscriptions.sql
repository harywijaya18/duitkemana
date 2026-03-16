-- ================================================================
-- Subscription & Billing migration
-- ================================================================

USE duitkemana;

CREATE TABLE IF NOT EXISTS plans (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(40) NOT NULL,
    name VARCHAR(100) NOT NULL,
    price_monthly DECIMAL(12,2) NOT NULL DEFAULT 0,
    currency VARCHAR(10) NOT NULL DEFAULT 'IDR',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_plans_code (code),
    INDEX idx_plans_active_price (is_active, price_monthly)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscriptions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NULL,
    status ENUM('trial','active','grace','past_due','cancelled') NOT NULL DEFAULT 'trial',
    billing_cycle ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly',
    current_period_start DATE NULL,
    current_period_end DATE NULL,
    trial_ends_at DATE NULL,
    cancelled_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_subscriptions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_subscriptions_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_subscriptions_user (user_id),
    INDEX idx_subscriptions_status_period (status, current_period_end),
    INDEX idx_subscriptions_plan_status (plan_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoices (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    subscription_id BIGINT UNSIGNED NOT NULL,
    invoice_no VARCHAR(60) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'IDR',
    status ENUM('pending','paid','failed','void') NOT NULL DEFAULT 'pending',
    due_date DATE NOT NULL,
    paid_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_invoices_subscription FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_invoice_no (invoice_no),
    INDEX idx_invoices_sub_due (subscription_id, due_date),
    INDEX idx_invoices_status_due (status, due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO plans (code, name, price_monthly, currency, is_active) VALUES
('free', 'Free', 0, 'IDR', 1),
('pro', 'Pro', 49000, 'IDR', 1),
('business', 'Business', 149000, 'IDR', 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    price_monthly = VALUES(price_monthly),
    currency = VALUES(currency),
    is_active = VALUES(is_active);

INSERT INTO subscriptions (user_id, plan_id, status, billing_cycle, current_period_start, current_period_end, trial_ends_at)
SELECT
    u.id,
    (SELECT id FROM plans WHERE code = 'free' LIMIT 1),
    'active',
    'monthly',
    DATE_FORMAT(CURDATE(), '%Y-%m-01'),
    LAST_DAY(CURDATE()),
    NULL
FROM users u
WHERE NOT EXISTS (
    SELECT 1 FROM subscriptions s WHERE s.user_id = u.id
);
