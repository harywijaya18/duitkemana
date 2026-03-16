-- ================================================================
-- Recurring Bills feature migration
-- Run AFTER existing tables are in place (users, categories, transactions)
-- ================================================================

CREATE TABLE IF NOT EXISTS recurring_bills (
    id               INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    user_id          INT UNSIGNED      NOT NULL,
    category_id      INT UNSIGNED      NULL,
    name             VARCHAR(100)      NOT NULL,
    amount           DECIMAL(15,2)     NOT NULL DEFAULT 0.00,
    start_year       SMALLINT UNSIGNED NOT NULL,
    start_month      TINYINT UNSIGNED  NOT NULL,
    duration_months  SMALLINT UNSIGNED NULL     COMMENT 'NULL = indefinite or controlled by end_year/end_month',
    end_year         SMALLINT UNSIGNED NULL,
    end_month        TINYINT UNSIGNED  NULL,
    is_active        TINYINT(1)        NOT NULL DEFAULT 1,
    notes            TEXT              NULL,
    created_at       TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id)     REFERENCES users(id)       ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add recurring_bill_id to transactions (links auto-generated transactions back to the bill)
ALTER TABLE transactions
    ADD COLUMN recurring_bill_id INT UNSIGNED NULL AFTER description,
    ADD CONSTRAINT fk_tx_recurring_bill
        FOREIGN KEY (recurring_bill_id) REFERENCES recurring_bills(id)
        ON DELETE SET NULL;
